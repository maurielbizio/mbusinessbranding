#!/usr/bin/env python3
"""
Video Pipeline API Server
-------------------------
Listens on localhost:5555. n8n POSTs a YouTube URL to /run,
the pipeline starts in the background, and OpenClaw notifies
Mauriel when clips are ready.

Usage:
    source ~/video_pipeline_venv/bin/activate
    python3 ~/video_api.py
"""

import json
import subprocess
import threading
import time
import urllib.request
from http.server import BaseHTTPRequestHandler, HTTPServer
from pathlib import Path
from urllib.parse import urlparse

VENV_ACTIVATE = "/home/mauriel-strawbridge/video_pipeline_venv/bin/activate"
PIPELINE_SCRIPT = "/home/mauriel-strawbridge/video_pipeline.py"
OUTPUT_DIR = Path("/home/mauriel-strawbridge/video_clips/output")
TELEGRAM_BOT_TOKEN = "8695877287:AAHkNW2P3QfwP04-1z3jNOc9LdgAcrSnm5o"
TELEGRAM_CHAT_ID = "5775812070"
PORT = 5555

# Shared pipeline state
state = {
    "status": "idle",   # idle | running | completed | failed
    "url": None,
    "started_at": None,
    "clips": [],
    "error": None,
}
state_lock = threading.Lock()


def _send_telegram(message: str) -> None:
    """Send a message to Mauriel via Telegram Bot API."""
    payload = json.dumps({"chat_id": TELEGRAM_CHAT_ID, "text": message}).encode()
    req = urllib.request.Request(
        f"https://api.telegram.org/bot{TELEGRAM_BOT_TOKEN}/sendMessage",
        data=payload,
        headers={"Content-Type": "application/json"},
        method="POST",
    )
    with urllib.request.urlopen(req, timeout=15) as resp:
        print(f"[API] Telegram notified: {resp.status}")


def run_pipeline(url: str) -> None:
    with state_lock:
        state["status"] = "running"
        state["url"] = url
        state["started_at"] = time.time()
        state["clips"] = []
        state["error"] = None

    try:
        cmd = (
            f"bash -lc 'source {VENV_ACTIVATE} && "
            f"python3 {PIPELINE_SCRIPT} {json.dumps(url)} 2>&1'"
        )
        result = subprocess.run(cmd, shell=True, capture_output=True, text=True, timeout=7200)

        if result.returncode != 0:
            raise RuntimeError(result.stdout[-500:] if result.stdout else "Unknown error")

        # Find the latest manifest
        manifests = sorted(
            OUTPUT_DIR.glob("manifest_*.json"),
            key=lambda f: f.stat().st_mtime,
            reverse=True,
        )
        if not manifests:
            raise RuntimeError("Pipeline finished but no manifest found.")

        manifest = json.loads(manifests[0].read_text())
        clips = manifest.get("clips", [])

        with state_lock:
            state["status"] = "completed"
            state["clips"] = clips

        # Notify via Telegram Bot API
        lines = [
            f"{i+1}. [{c['score']}/10] {c['title']} ({round(c['end_time'] - c['start_time'])}s)"
            for i, c in enumerate(clips)
        ]
        message = f"✅ Video Clips Ready!\n{len(clips)} clips from:\n{url}\n\n" + "\n".join(lines)
        _send_telegram(message)

    except Exception as e:
        with state_lock:
            state["status"] = "failed"
            state["error"] = str(e)
        try:
            _send_telegram(f"❌ Video pipeline FAILED:\n{str(e)[:300]}")
        except Exception:
            pass


class Handler(BaseHTTPRequestHandler):
    def log_message(self, format, *args):
        print(f"[API] {self.address_string()} - {format % args}")

    def send_json(self, code: int, data: dict) -> None:
        body = json.dumps(data).encode()
        self.send_response(code)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", len(body))
        self.end_headers()
        self.wfile.write(body)

    def do_GET(self):
        if urlparse(self.path).path == "/status":
            with state_lock:
                self.send_json(200, dict(state))
        else:
            self.send_json(404, {"error": "not found"})

    def do_POST(self):
        if urlparse(self.path).path != "/run":
            self.send_json(404, {"error": "not found"})
            return

        length = int(self.headers.get("Content-Length", 0))
        body = self.rfile.read(length)

        try:
            data = json.loads(body)
        except json.JSONDecodeError:
            self.send_json(400, {"error": "invalid JSON"})
            return

        url = data.get("url", "").strip()
        if not url:
            self.send_json(400, {"error": "url is required"})
            return

        with state_lock:
            if state["status"] == "running":
                self.send_json(409, {"error": "pipeline already running", "url": state["url"]})
                return

        thread = threading.Thread(target=run_pipeline, args=(url,), daemon=True)
        thread.start()

        self.send_json(200, {"status": "started", "url": url})


if __name__ == "__main__":
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    server = HTTPServer(("127.0.0.1", PORT), Handler)
    print(f"[API] Video Pipeline API running on http://127.0.0.1:{PORT}")
    print(f"[API] POST /run  {{\"url\": \"https://youtube.com/...\"}}  to start")
    print(f"[API] GET  /status  to check progress")
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\n[API] Shutting down.")
