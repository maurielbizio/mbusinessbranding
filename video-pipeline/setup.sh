#!/usr/bin/env bash
# =============================================================================
# Video Clipping Pipeline — Ubuntu Setup Script
# Run once on mauriel-strawbridge@mauriel-openclaw
#
# Usage:
#   chmod +x setup.sh
#   ./setup.sh
# =============================================================================

set -euo pipefail

BOLD="\033[1m"
GREEN="\033[0;32m"
YELLOW="\033[1;33m"
RED="\033[0;31m"
RESET="\033[0m"

log()    { echo -e "${GREEN}[SETUP]${RESET} $*"; }
warn()   { echo -e "${YELLOW}[WARN]${RESET}  $*"; }
error()  { echo -e "${RED}[ERROR]${RESET} $*"; exit 1; }
header() { echo -e "\n${BOLD}$*${RESET}"; }

# ── 0. Sanity checks ──────────────────────────────────────────────────────────
header "=== Video Clipping Pipeline — Dependency Setup ==="

# Confirm Ubuntu / Debian
if ! command -v apt-get &>/dev/null; then
    error "This script requires apt-get (Ubuntu/Debian). Aborting."
fi

# Must NOT be root (pip install as root is unsafe)
if [[ "$EUID" -eq 0 ]]; then
    warn "Running as root. It is safer to run as a regular user."
    warn "Continuing anyway — press Ctrl+C to abort, or wait 5 seconds..."
    sleep 5
fi

# ── 1. System packages ────────────────────────────────────────────────────────
header "── Step 1/5: System packages ──"
log "Updating package list..."
sudo apt-get update -y -q

log "Installing system dependencies..."
sudo apt-get install -y -q \
    python3 \
    python3-pip \
    python3-venv \
    ffmpeg \
    git \
    curl \
    wget \
    build-essential \
    libssl-dev \
    libffi-dev \
    python3-dev

log "System packages installed."

# ── 2. Verify FFmpeg ──────────────────────────────────────────────────────────
header "── Step 2/5: Verify FFmpeg ──"
if command -v ffmpeg &>/dev/null; then
    FFMPEG_VER=$(ffmpeg -version 2>&1 | head -1)
    log "FFmpeg found: $FFMPEG_VER"
else
    error "FFmpeg not found after installation. Check your apt mirrors."
fi

# ── 3. Python virtual environment ─────────────────────────────────────────────
header "── Step 3/5: Python virtual environment ──"
VENV_DIR="$HOME/video_pipeline_venv"

if [[ -d "$VENV_DIR" ]]; then
    warn "Virtual environment already exists at $VENV_DIR — skipping creation."
else
    log "Creating virtual environment at $VENV_DIR..."
    python3 -m venv "$VENV_DIR"
    log "Virtual environment created."
fi

# Activate venv for the rest of this script
# shellcheck source=/dev/null
source "$VENV_DIR/bin/activate"

log "Upgrading pip..."
pip install --upgrade pip --quiet

# ── 4. Python packages ────────────────────────────────────────────────────────
header "── Step 4/5: Python packages ──"

log "Installing yt-dlp..."
pip install yt-dlp --quiet

log "Installing openai-whisper..."
# Whisper depends on torch — this is the heavy one; grab a coffee ☕
pip install openai-whisper --quiet

log "Installing anthropic SDK..."
pip install anthropic --quiet

log "Installing ffmpeg-python..."
pip install ffmpeg-python --quiet

log "All Python packages installed."

# Verify imports
log "Verifying imports..."
python3 - <<'PYCHECK'
import yt_dlp
import whisper
import anthropic
import ffmpeg
print("  yt_dlp:    OK")
print("  whisper:   OK")
print("  anthropic: OK")
print("  ffmpeg:    OK")
PYCHECK

# ── 5. Output directory & environment ────────────────────────────────────────
header "── Step 5/5: Output directory & environment ──"
OUTPUT_DIR="$HOME/video_clips/output"
mkdir -p "$OUTPUT_DIR"
log "Output directory ready: $OUTPUT_DIR"

# Check for ANTHROPIC_API_KEY
if [[ -z "${ANTHROPIC_API_KEY:-}" ]]; then
    warn "ANTHROPIC_API_KEY is NOT set."
    warn "Add it to your shell profile so the pipeline can call Claude:"
    echo ""
    echo "    echo 'export ANTHROPIC_API_KEY=\"sk-ant-...\"' >> ~/.bashrc"
    echo "    source ~/.bashrc"
    echo ""
else
    log "ANTHROPIC_API_KEY is set. ✓"
fi

# ── Launcher script ───────────────────────────────────────────────────────────
LAUNCHER="$HOME/run_video_pipeline.sh"
PIPELINE_SCRIPT="$(realpath "$(dirname "$0")")/video_pipeline.py"

cat > "$LAUNCHER" <<LAUNCHER_EOF
#!/usr/bin/env bash
# Auto-generated launcher for video_pipeline.py
# Usage: ./run_video_pipeline.sh "https://youtube.com/..."
source "$VENV_DIR/bin/activate"
python3 "$PIPELINE_SCRIPT" "\$@"
LAUNCHER_EOF

chmod +x "$LAUNCHER"
log "Launcher created: $LAUNCHER"

# ── Done ──────────────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}${BOLD}=== Setup complete! ===${RESET}"
echo ""
echo "To run the pipeline:"
echo ""
echo "  Option A — use the launcher (handles venv automatically):"
echo "    ~/run_video_pipeline.sh \"https://youtube.com/watch?v=...\""
echo "    ~/run_video_pipeline.sh \"/path/to/local/video.mp4\""
echo ""
echo "  Option B — activate venv manually then run Python:"
echo "    source $VENV_DIR/bin/activate"
echo "    python3 $PIPELINE_SCRIPT \"https://youtube.com/watch?v=...\""
echo ""
echo "Clips will be saved to: $OUTPUT_DIR"
echo ""
