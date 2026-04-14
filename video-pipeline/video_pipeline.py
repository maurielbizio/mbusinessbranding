#!/usr/bin/env python3
"""
Video Clipping Pipeline
-----------------------
Takes a YouTube URL or local file path, transcribes it with Whisper,
uses Claude AI to identify the 5 best 60-120 second clips, and cuts
them with FFmpeg.

Usage:
    python video_pipeline.py "https://youtube.com/watch?v=..."
    python video_pipeline.py "/path/to/local/video.mp4"

Requirements:
    ANTHROPIC_API_KEY environment variable must be set.
"""

import sys
import os
import json
import re
import subprocess
import tempfile
import shutil
from pathlib import Path
from datetime import datetime

# ── Third-party imports ──────────────────────────────────────────────────────
try:
    import yt_dlp
except ImportError:
    print("[ERROR] yt-dlp not installed. Run: pip install yt-dlp")
    sys.exit(1)

try:
    import whisper
except ImportError:
    print("[ERROR] openai-whisper not installed. Run: pip install openai-whisper")
    sys.exit(1)

try:
    import ffmpeg
except ImportError:
    print("[ERROR] ffmpeg-python not installed. Run: pip install ffmpeg-python")
    sys.exit(1)

import urllib.request
import urllib.error


# ── Config ───────────────────────────────────────────────────────────────────
OUTPUT_DIR = Path.home() / "video_clips" / "output"
WHISPER_MODEL = "base"
OLLAMA_MODEL = "llama3.1:8b"       # change to any model you have pulled
OLLAMA_BASE_URL = "http://localhost:11434"
MIN_CLIP_SECONDS = 60
MAX_CLIP_SECONDS = 120
NUM_CLIPS = 5


# ── Helpers ──────────────────────────────────────────────────────────────────

def log(step: str, message: str) -> None:
    """Print a formatted progress message."""
    print(f"\n[{step}] {message}")


def is_youtube_url(source: str) -> bool:
    """Return True if the source string looks like a YouTube URL."""
    youtube_patterns = [
        r"youtube\.com/watch",
        r"youtu\.be/",
        r"youtube\.com/shorts/",
        r"youtube\.com/live/",
    ]
    return any(re.search(p, source) for p in youtube_patterns)


def is_google_drive_url(source: str) -> bool:
    """Return True if the source string looks like a Google Drive URL."""
    return "drive.google.com" in source


def sanitize_filename(name: str) -> str:
    """Strip characters that are unsafe in file names."""
    safe = re.sub(r'[^\w\s\-]', '', name)
    safe = re.sub(r'\s+', '_', safe.strip())
    return safe[:60]  # cap length


def seconds_to_hhmmss(seconds: float) -> str:
    """Convert float seconds to HH:MM:SS.mmm string for FFmpeg."""
    h = int(seconds // 3600)
    m = int((seconds % 3600) // 60)
    s = seconds % 60
    return f"{h:02d}:{m:02d}:{s:06.3f}"


# ── Step 1 — Download / Validate source ─────────────────────────────────────

def download_youtube(url: str, download_dir: Path) -> Path:
    """Download a YouTube video with yt-dlp and return the local file path."""
    log("DOWNLOAD", f"Fetching video from YouTube: {url}")

    ydl_opts = {
        "format": "bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best",
        "outtmpl": str(download_dir / "%(title)s.%(ext)s"),
        "merge_output_format": "mp4",
        "quiet": False,
        "no_warnings": False,
    }

    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
        info = ydl.extract_info(url, download=True)
        filename = ydl.prepare_filename(info)
        # yt-dlp may change the extension after merging
        base = Path(filename).with_suffix("")
        for ext in [".mp4", ".mkv", ".webm"]:
            candidate = Path(str(base) + ext)
            if candidate.exists():
                log("DOWNLOAD", f"Saved to: {candidate}")
                return candidate
        # Fallback: find the newest file in the directory
        files = sorted(download_dir.glob("*"), key=lambda f: f.stat().st_mtime, reverse=True)
        if files:
            log("DOWNLOAD", f"Saved to: {files[0]}")
            return files[0]

    raise FileNotFoundError("yt-dlp finished but no output file was found.")


def resolve_source(source: str, work_dir: Path) -> Path:
    """Return a local Path to the video file, downloading if necessary."""
    if is_google_drive_url(source):
        log("INPUT", "Google Drive URLs are not yet supported for direct download.")
        log("INPUT", "Download the file manually and pass the local path instead.")
        sys.exit(1)

    if is_youtube_url(source):
        return download_youtube(source, work_dir)

    # Assume local file
    local = Path(source)
    if not local.exists():
        print(f"[ERROR] File not found: {local}")
        sys.exit(1)
    log("INPUT", f"Using local file: {local}")
    return local


# ── Step 2 — Transcribe with Whisper ─────────────────────────────────────────

def transcribe(video_path: Path) -> list[dict]:
    """
    Transcribe the video with OpenAI Whisper.
    Returns a list of segment dicts: {start, end, text}.
    """
    log("TRANSCRIBE", f"Loading Whisper model '{WHISPER_MODEL}' — this may take a moment on first run...")
    model = whisper.load_model(WHISPER_MODEL)

    log("TRANSCRIBE", f"Transcribing: {video_path.name}")
    result = model.transcribe(str(video_path), verbose=False)

    segments = result.get("segments", [])
    log("TRANSCRIBE", f"Got {len(segments)} transcript segments.")

    if not segments:
        print("[ERROR] Whisper returned no segments. The video may have no audio or be too short.")
        sys.exit(1)

    return segments


def segments_to_text(segments: list[dict]) -> str:
    """Convert Whisper segments into a readable timestamped transcript string."""
    lines = []
    for seg in segments:
        start = seconds_to_hhmmss(seg["start"])
        end = seconds_to_hhmmss(seg["end"])
        text = seg["text"].strip()
        lines.append(f"[{start} --> {end}] {text}")
    return "\n".join(lines)


# ── Step 3 — Claude clip selection ───────────────────────────────────────────

def select_clips_with_ollama(transcript: str) -> list[dict]:
    """
    Send the transcript to a local Ollama model and ask it to identify the 5 best clips.
    Returns a list of clip dicts with start_time, end_time, title, reason, score.
    """
    log("OLLAMA", f"Sending transcript to {OLLAMA_MODEL} for clip selection...")

    system_prompt = (
        "You are a social media video editor with expertise in identifying viral, "
        "high-engagement video clips for platforms like TikTok, Instagram Reels, and YouTube Shorts. "
        "You analyze transcripts and select the most compelling segments. "
        "Always respond with valid JSON only — no explanation, no markdown fences."
    )

    user_prompt = f"""Analyze the following video transcript and identify the 5 BEST clips for social media.

REQUIREMENTS:
- Each clip must be between {MIN_CLIP_SECONDS} and {MAX_CLIP_SECONDS} seconds long
- Prioritize: strong hooks, key insights, emotional moments, quotable lines, surprising facts, storytelling peaks
- Avoid: mid-sentence cuts, weak transitions, filler content

TRANSCRIPT:
{transcript}

Return ONLY valid JSON with this exact structure (no markdown, no explanation outside the JSON):
{{
  "clips": [
    {{
      "start_time": 45.2,
      "end_time": 112.8,
      "title": "Short punchy clip title",
      "reason": "Why this clip has high engagement potential",
      "score": 9.2
    }}
  ]
}}

Rules:
- start_time and end_time must be in seconds (float)
- end_time - start_time must be between {MIN_CLIP_SECONDS} and {MAX_CLIP_SECONDS}
- score is 1.0-10.0 (engagement potential)
- Return exactly {NUM_CLIPS} clips, sorted by score descending
"""

    payload = json.dumps({
        "model": OLLAMA_MODEL,
        "messages": [
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": user_prompt},
        ],
        "stream": False,
    }).encode("utf-8")

    req = urllib.request.Request(
        f"{OLLAMA_BASE_URL}/api/chat",
        data=payload,
        headers={"Content-Type": "application/json"},
        method="POST",
    )

    try:
        with urllib.request.urlopen(req, timeout=180) as resp:
            result = json.loads(resp.read().decode("utf-8"))
    except urllib.error.URLError as e:
        print(f"[ERROR] Could not connect to Ollama at {OLLAMA_BASE_URL}: {e}")
        print("Make sure Ollama is running: ollama serve")
        sys.exit(1)

    response_text = result["message"]["content"].strip()

    # Strip markdown code fences if the model wrapped the JSON
    response_text = re.sub(r'^```(?:json)?\s*', '', response_text)
    response_text = re.sub(r'\s*```$', '', response_text)

    try:
        data = json.loads(response_text)
    except json.JSONDecodeError as e:
        print(f"[ERROR] Ollama returned invalid JSON: {e}")
        print(f"Raw response:\n{response_text}")
        sys.exit(1)

    clips = data.get("clips", [])
    if not clips:
        print("[ERROR] Ollama returned no clips.")
        sys.exit(1)

    log("OLLAMA", f"Model identified {len(clips)} clips.")
    for i, clip in enumerate(clips, 1):
        duration = clip["end_time"] - clip["start_time"]
        print(
            f"  {i}. [{clip['score']}/10] \"{clip['title']}\" "
            f"({clip['start_time']:.1f}s - {clip['end_time']:.1f}s, {duration:.0f}s)"
        )
        print(f"     Reason: {clip['reason']}")

    return clips


# ── Step 4 — Cut clips with FFmpeg ───────────────────────────────────────────

def cut_clips(video_path: Path, clips: list[dict], output_dir: Path) -> list[Path]:
    """
    Cut each clip from the source video using FFmpeg.
    Returns a list of output file paths.
    """
    output_dir.mkdir(parents=True, exist_ok=True)

    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    output_files = []

    log("FFMPEG", f"Cutting {len(clips)} clips to: {output_dir}")

    for i, clip in enumerate(clips, 1):
        start = clip["start_time"]
        end = clip["end_time"]
        duration = end - start
        title_safe = sanitize_filename(clip["title"])
        out_filename = f"clip_{i:02d}_score{clip['score']}_{title_safe}_{timestamp}.mp4"
        out_path = output_dir / out_filename

        log("FFMPEG", f"Cutting clip {i}/{len(clips)}: {out_filename}")
        print(f"  Start: {seconds_to_hhmmss(start)}  End: {seconds_to_hhmmss(end)}  Duration: {duration:.1f}s")

        try:
            (
                ffmpeg
                .input(str(video_path), ss=start, t=duration)
                .output(
                    str(out_path),
                    vcodec="libx264",
                    acodec="aac",
                    preset="fast",
                    crf=23,
                    movflags="+faststart",
                )
                .overwrite_output()
                .run(quiet=True)
            )
            output_files.append(out_path)
            print(f"  Saved: {out_path}")
        except ffmpeg.Error as e:
            stderr = e.stderr.decode("utf-8") if e.stderr else "unknown error"
            print(f"  [WARNING] FFmpeg failed for clip {i}: {stderr}")
            # Continue with remaining clips rather than aborting entirely

    return output_files


# ── Step 5 — Save manifest ───────────────────────────────────────────────────

def save_manifest(clips: list[dict], output_files: list[Path], output_dir: Path, source: str) -> None:
    """Write a JSON manifest summarising the run."""
    manifest = {
        "source": source,
        "run_at": datetime.now().isoformat(),
        "clips": []
    }
    for clip, path in zip(clips, output_files):
        manifest["clips"].append({
            **clip,
            "output_file": str(path),
        })

    manifest_path = output_dir / f"manifest_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
    manifest_path.write_text(json.dumps(manifest, indent=2))
    log("MANIFEST", f"Run manifest saved: {manifest_path}")


# ── Main ─────────────────────────────────────────────────────────────────────

def main() -> None:
    if len(sys.argv) < 2:
        print("Usage: python video_pipeline.py <YouTube URL or local file path>")
        print("Examples:")
        print('  python video_pipeline.py "https://youtube.com/watch?v=dQw4w9WgXcQ"')
        print('  python video_pipeline.py "/path/to/video.mp4"')
        sys.exit(1)

    source = sys.argv[1].strip()

    print("\n" + "=" * 60)
    print("  VIDEO CLIPPING PIPELINE")
    print("=" * 60)
    print(f"  Source : {source}")
    print(f"  Output : {OUTPUT_DIR}")
    print(f"  Model  : {OLLAMA_MODEL} (local)")
    print(f"  Whisper: {WHISPER_MODEL}")
    print("=" * 60)

    # Create a temporary working directory for downloads
    with tempfile.TemporaryDirectory(prefix="video_pipeline_") as tmp_str:
        work_dir = Path(tmp_str)

        # ── 1. Resolve source video ──────────────────────────────────────────
        video_path = resolve_source(source, work_dir)

        # ── 2. Transcribe ────────────────────────────────────────────────────
        segments = transcribe(video_path)
        transcript_text = segments_to_text(segments)

        # Optionally save transcript for debugging
        transcript_out = OUTPUT_DIR / f"transcript_{datetime.now().strftime('%Y%m%d_%H%M%S')}.txt"
        OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
        transcript_out.write_text(transcript_text)
        log("TRANSCRIBE", f"Transcript saved: {transcript_out}")

        # ── 3. Claude clip selection ─────────────────────────────────────────
        clips = select_clips_with_ollama(transcript_text)

        # Validate clip boundaries against video duration
        try:
            probe = ffmpeg.probe(str(video_path))
            video_duration = float(probe["format"]["duration"])
            log("VALIDATE", f"Video duration: {video_duration:.1f}s")
            for clip in clips:
                if clip["end_time"] > video_duration:
                    log("VALIDATE", f"  Adjusting clip end time {clip['end_time']:.1f}s -> {video_duration:.1f}s")
                    clip["end_time"] = video_duration
                if clip["start_time"] < 0:
                    clip["start_time"] = 0.0
                duration = clip["end_time"] - clip["start_time"]
                if duration < 5:
                    log("VALIDATE", f"  Skipping clip '{clip['title']}' — duration too short after adjustment ({duration:.1f}s)")
        except ffmpeg.Error:
            log("VALIDATE", "Could not probe video duration — skipping boundary check.")

        # ── 4. Cut clips ─────────────────────────────────────────────────────
        # If the source was a downloaded temp file, copy it to a stable location
        # before the temp dir is cleaned up (clips are cut inside the with block)
        output_files = cut_clips(video_path, clips, OUTPUT_DIR)

    # ── 5. Manifest ──────────────────────────────────────────────────────────
    if output_files:
        save_manifest(clips, output_files, OUTPUT_DIR, source)

    print("\n" + "=" * 60)
    print(f"  DONE — {len(output_files)} clip(s) saved to:")
    print(f"  {OUTPUT_DIR}")
    print("=" * 60 + "\n")


if __name__ == "__main__":
    main()
