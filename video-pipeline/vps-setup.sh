#!/bin/bash
# =============================================================================
# Video Pipeline — VPS Setup Script
# Run this on the Hostinger VPS as root: bash vps-setup.sh
# VPS: 187.124.149.110 | n8n: https://n8n.mbusinessbrandingai.com
# =============================================================================

set -e

echo "======================================================"
echo " Mbusiness Video Pipeline — VPS Setup"
echo "======================================================"

# ── 1. Install FFmpeg ──────────────────────────────────────────────────────────
echo ""
echo "[1/5] Installing FFmpeg..."
apt-get update -qq && apt-get install -y -qq ffmpeg wget
echo "      ✅ FFmpeg installed: $(ffmpeg -version 2>&1 | head -1)"

# ── 2. Create file directories ─────────────────────────────────────────────────
echo ""
echo "[2/5] Creating file directories..."
mkdir -p /local-files/sessions
mkdir -p /local-files/music
mkdir -p /local-files/output
chmod -R 777 /local-files
echo "      ✅ Directories created at /local-files/"

# ── 3. Install fonts for FFmpeg text overlay ───────────────────────────────────
echo ""
echo "[3/5] Installing fonts for FFmpeg text overlay..."
apt-get install -y -qq fonts-dejavu-core fonts-dejavu
echo "      ✅ Fonts installed"

# ── 4. Test FFmpeg inside n8n Docker container ─────────────────────────────────
echo ""
echo "[4/5] Testing FFmpeg inside n8n container..."
CONTAINER_NAME=$(docker ps --format '{{.Names}}' | grep -i n8n | head -1)

if [ -z "$CONTAINER_NAME" ]; then
  echo "      ⚠️  Could not find n8n container by name. Listing all containers:"
  docker ps --format '{{.Names}}'
  echo ""
  echo "      Updating docker-compose.yml to mount FFmpeg..."
else
  echo "      Found n8n container: $CONTAINER_NAME"

  # Try running ffmpeg inside the container
  if docker exec "$CONTAINER_NAME" ffmpeg -version > /dev/null 2>&1; then
    echo "      ✅ FFmpeg accessible inside container — no changes needed"
  else
    echo "      ⚠️  FFmpeg NOT in container. Updating docker-compose to mount it..."
    COMPOSE_FILE="/docker/n8n/docker-compose.yml"

    if [ -f "$COMPOSE_FILE" ]; then
      # Backup original
      cp "$COMPOSE_FILE" "${COMPOSE_FILE}.bak"

      # Check if ffmpeg volume already mounted
      if grep -q "usr/bin/ffmpeg" "$COMPOSE_FILE"; then
        echo "      ✅ FFmpeg volume mount already present in docker-compose.yml"
      else
        echo "      Adding FFmpeg volume mounts to docker-compose.yml..."
        # Insert ffmpeg mounts after the local-files volume line
        sed -i '/local-files.*local-files/a\      - /usr/bin/ffmpeg:/usr/bin/ffmpeg\n      - /usr/lib/x86_64-linux-gnu/libavcodec.so.59:/usr/lib/x86_64-linux-gnu/libavcodec.so.59\n      - /usr/lib/x86_64-linux-gnu/libavformat.so.59:/usr/lib/x86_64-linux-gnu/libavformat.so.59\n      - /usr/lib/x86_64-linux-gnu/libavutil.so.57:/usr/lib/x86_64-linux-gnu/libavutil.so.57\n      - /usr/lib/x86_64-linux-gnu/libswresample.so.4:/usr/lib/x86_64-linux-gnu/libswresample.so.4\n      - /usr/lib/x86_64-linux-gnu/libswscale.so.6:/usr/lib/x86_64-linux-gnu/libswscale.so.6\n      - /usr/share/fonts:/usr/share/fonts:ro' "$COMPOSE_FILE"

        echo "      Restarting n8n container..."
        cd /docker/n8n && docker compose down && docker compose up -d
        sleep 10

        if docker exec "$(docker ps --format '{{.Names}}' | grep -i n8n | head -1)" ffmpeg -version > /dev/null 2>&1; then
          echo "      ✅ FFmpeg now accessible inside container"
        else
          echo "      ❌ FFmpeg still not accessible. Using SSH-based fallback in WF3."
          echo "         The WF3 workflow uses Execute Command which runs on the host via Docker socket."
        fi
      fi
    else
      echo "      ❌ docker-compose.yml not found at $COMPOSE_FILE"
      echo "         Please check your n8n Docker setup location."
    fi
  fi
fi

# ── 5. Set up nginx static file server for /output/ ───────────────────────────
echo ""
echo "[5/5] Setting up nginx static file server for video downloads..."

COMPOSE_FILE="/docker/n8n/docker-compose.yml"

if grep -q "nginx-files" "$COMPOSE_FILE" 2>/dev/null; then
  echo "      ✅ nginx-files service already in docker-compose.yml"
else
  echo "      Adding nginx-files service to docker-compose.yml..."

  # Append nginx service to docker-compose.yml
  cat >> "$COMPOSE_FILE" << 'NGINX_SERVICE'

  nginx-files:
    image: nginx:alpine
    restart: always
    volumes:
      - /local-files/output:/usr/share/nginx/html/output:ro
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.nginx-files.rule=Host(`n8n.mbusinessbrandingai.com`) && PathPrefix(`/output`)"
      - "traefik.http.routers.nginx-files.entrypoints=websecure"
      - "traefik.http.routers.nginx-files.tls.certresolver=mytlschallenge"
      - "traefik.http.services.nginx-files.loadbalancer.server.port=80"
      - "traefik.http.middlewares.strip-output.stripprefix.prefixes=/output"
      - "traefik.http.routers.nginx-files.middlewares=strip-output"
    networks:
      - traefik-public
NGINX_SERVICE

  echo "      Starting nginx-files container..."
  cd /docker/n8n && docker compose up -d nginx-files
  echo "      ✅ nginx-files started"
fi

# ── 6. Add cleanup cron job ─────────────────────────────────────────────────────
echo ""
echo "[Bonus] Adding cleanup cron job (delete output files older than 7 days)..."
(crontab -l 2>/dev/null | grep -v "local-files/output"; echo "0 3 * * * find /local-files/output -name '*.mp4' -mtime +7 -delete") | crontab -
echo "        ✅ Cron job added"

# ── Summary ────────────────────────────────────────────────────────────────────
echo ""
echo "======================================================"
echo " Setup Complete!"
echo "======================================================"
echo ""
echo " Directories:"
echo "   /local-files/sessions  — session data"
echo "   /local-files/music     — uploaded MP3 files"
echo "   /local-files/output    — processed videos"
echo ""
echo " Static file serving:"
echo "   https://n8n.mbusinessbrandingai.com/output/{uuid}_final.mp4"
echo ""
echo " Next steps:"
echo "   1. Import the 4 n8n workflow JSON files via n8n UI → Settings → Import"
echo "   2. In n8n, add WAVESPEED_API_KEY as an environment variable:"
echo "      Edit /docker/n8n/.env → add: WAVESPEED_API_KEY=c1a97f1ab80dc87908bd6f479d0d4ff97233c6dfd04b0e1e61db56f612e818b3"
echo "      Then restart n8n: cd /docker/n8n && docker compose restart n8n"
echo "   3. Upload page-video-studio.php to your WordPress theme:"
echo "      /var/www/html/wp-content/themes/{your-theme}/page-video-studio.php"
echo "   4. In WordPress admin: create a new Page, set Template = 'Video Studio'"
echo "   5. Password-protect the page in WordPress page settings"
echo ""
echo " Done! 🎬"
