#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# deploy.sh — WWMControl production deployment helper
# Usage: bash deploy.sh [--skip-build] [--down]
# ─────────────────────────────────────────────────────────────────────────────
set -e

COMPOSE="docker compose -f docker-compose.prod.yml"

# ── Parse flags ──────────────────────────────────────────────────────────────
SKIP_BUILD=0
DO_DOWN=0
for arg in "$@"; do
  case $arg in
    --skip-build) SKIP_BUILD=1 ;;
    --down)       DO_DOWN=1 ;;
  esac
done

# ── Tear down (optional) ─────────────────────────────────────────────────────
if [ "$DO_DOWN" -eq 1 ]; then
  echo "[deploy] Stopping all services..."
  $COMPOSE down
  exit 0
fi

# ── Verify .env ──────────────────────────────────────────────────────────────
if [ ! -f ".env" ]; then
  if [ -f ".env.docker.example" ]; then
    cp .env.docker.example .env
    echo ""
    echo "[!] .env was created from .env.docker.example."
    echo "    Please edit .env and fill in all required values, then re-run this script."
    echo ""
    exit 1
  else
    echo "[!] .env file not found. Create it before deploying."
    exit 1
  fi
fi

# ── Generate APP_KEY if missing ───────────────────────────────────────────────
APP_KEY_VAL=$(grep '^APP_KEY=' .env | cut -d= -f2 | tr -d ' ')
if [ -z "$APP_KEY_VAL" ]; then
  echo "[deploy] Generating APP_KEY..."
  GENERATED_KEY=$(docker run --rm php:8.2-cli php -r "echo 'base64:' . base64_encode(random_bytes(32));")
  sed -i "s|^APP_KEY=.*|APP_KEY=${GENERATED_KEY}|" .env
  echo "[deploy] APP_KEY set."
fi

# ── Load env for build args ───────────────────────────────────────────────────
set -o allexport
# shellcheck disable=SC1091
source .env
set +o allexport

# ── Build Docker image ────────────────────────────────────────────────────────
if [ "$SKIP_BUILD" -eq 0 ]; then
  echo "[deploy] Building Docker image..."
  $COMPOSE build \
    --build-arg VITE_APP_NAME="${APP_NAME:-TheZoo}" \
    --build-arg VITE_REVERB_APP_KEY="${VITE_REVERB_APP_KEY:-}" \
    --build-arg VITE_REVERB_HOST="${VITE_REVERB_HOST:-}" \
    --build-arg VITE_REVERB_PORT="${VITE_REVERB_PORT:-8080}" \
    --build-arg VITE_REVERB_SCHEME="${VITE_REVERB_SCHEME:-https}" \
    app
  echo "[deploy] Build complete."
fi

# ── Start / restart services ──────────────────────────────────────────────────
echo "[deploy] Starting services..."
$COMPOSE up -d --remove-orphans

# ── Health check ─────────────────────────────────────────────────────────────
echo "[deploy] Waiting for app to be ready..."
RETRIES=15
until docker compose -f docker-compose.prod.yml exec -T app php artisan --version > /dev/null 2>&1; do
  RETRIES=$((RETRIES - 1))
  if [ "$RETRIES" -le 0 ]; then
    echo "[!] App container did not become ready in time."
    $COMPOSE logs app | tail -30
    exit 1
  fi
  sleep 2
done

echo ""
echo "[deploy] ✓ Deployment complete."
echo "[deploy]   App URL : ${APP_URL:-http://localhost:${APP_PORT:-80}}"
echo "[deploy]   Reverb  : ws://${VITE_REVERB_HOST:-localhost}:${REVERB_SERVER_PORT:-8080}"
echo ""
echo "Useful commands:"
echo "  View logs      : docker compose -f docker-compose.prod.yml logs -f"
echo "  Shell into app : docker compose -f docker-compose.prod.yml exec app bash"
echo "  Stop all       : bash deploy.sh --down"
