#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# deploy-server.sh — Direct server deployment with auto-revert on failure
# Run this ON the production server (not locally)
# Usage: bash deploy-server.sh [--force-migrate] [--skip-npm]
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

APP_DIR="/var/www/wwmcontrol"
PHP="/usr/bin/php"
COMPOSER="/usr/bin/composer"
LOG_FILE="/var/log/wwmcontrol/deploy.log"
HEALTH_URL="http://localhost/up"          # Laravel /up health endpoint
HEALTH_RETRIES=12
PHP_FPM_SERVICE="php8.2-fpm"             # adjust if different version

FORCE_MIGRATE=0
SKIP_NPM=0
for arg in "$@"; do
  case $arg in
    --force-migrate) FORCE_MIGRATE=1 ;;
    --skip-npm)      SKIP_NPM=1 ;;
  esac
done

# ─── Helpers ─────────────────────────────────────────────────────────────────
log()  { echo "[$(date '+%H:%M:%S')] $*" | tee -a "$LOG_FILE"; }
fail() { log "✗ $*"; exit 1; }

rollback() {
  log "⚠ DEPLOY FAILED — rolling back to $PREV_COMMIT"
  cd "$APP_DIR"

  git reset --hard "$PREV_COMMIT"
  "$PHP" "$COMPOSER" install --no-dev --no-interaction --quiet 2>>"$LOG_FILE" || true
  "$PHP" artisan config:cache      >> "$LOG_FILE" 2>&1 || true
  "$PHP" artisan route:cache       >> "$LOG_FILE" 2>&1 || true
  "$PHP" artisan view:cache        >> "$LOG_FILE" 2>&1 || true
  sudo systemctl restart "$PHP_FPM_SERVICE" >> "$LOG_FILE" 2>&1 || true
  supervisorctl restart wwmcontrol-worker:* >> "$LOG_FILE" 2>&1 || true

  log "✓ Rolled back to $PREV_COMMIT"
  log "  Run 'git log --oneline -5' to confirm."
  exit 1
}

# ─── Start ───────────────────────────────────────────────────────────────────
mkdir -p "$(dirname "$LOG_FILE")"
log "═══════════════════════════════════"
log "Deploy started"
cd "$APP_DIR"

# 1. Save current state for rollback
PREV_COMMIT=$(git rev-parse HEAD)
log "Current commit: $PREV_COMMIT"

# Register rollback trap — fires on any error after this point
trap rollback ERR

# 2. Put site into maintenance mode
log "Enabling maintenance mode..."
"$PHP" artisan down --retry=10 --refresh=15

# 3. Pull latest code
log "Pulling latest code..."
git pull --ff-only origin "$(git rev-parse --abbrev-ref HEAD)"
NEW_COMMIT=$(git rev-parse HEAD)
log "New commit: $NEW_COMMIT"

if [ "$PREV_COMMIT" = "$NEW_COMMIT" ]; then
  log "No new commits. Exiting maintenance mode."
  "$PHP" artisan up
  trap - ERR
  exit 0
fi

# 4. Install PHP dependencies
log "Installing Composer dependencies..."
"$COMPOSER" install --no-dev --no-interaction --prefer-dist --optimize-autoloader 2>>"$LOG_FILE"

# 5. Build frontend assets (optional)
if [ "$SKIP_NPM" -eq 0 ] && [ -f "package.json" ]; then
  log "Building frontend assets..."
  npm ci --silent 2>>"$LOG_FILE"
  npm run build  2>>"$LOG_FILE"
fi

# 6. Run migrations (with safety check)
if [ "$FORCE_MIGRATE" -eq 1 ]; then
  log "Running migrations (--force)..."
  "$PHP" artisan migrate --force
else
  PENDING=$("$PHP" artisan migrate:status --no-ansi 2>/dev/null | grep -c 'Pending' || true)
  if [ "$PENDING" -gt 0 ]; then
    log "Running $PENDING pending migration(s)..."
    "$PHP" artisan migrate --force
  else
    log "No pending migrations."
  fi
fi

# 7. Clear and rebuild caches
log "Rebuilding caches..."
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache
"$PHP" artisan event:cache

# 8. Restart services
log "Restarting PHP-FPM and queue workers..."
sudo systemctl restart "$PHP_FPM_SERVICE"
supervisorctl restart wwmcontrol-worker:*

# 9. Bring site back up
log "Disabling maintenance mode..."
"$PHP" artisan up

# 10. Health check
log "Running health check on $HEALTH_URL ..."
for i in $(seq 1 $HEALTH_RETRIES); do
  STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$HEALTH_URL" 2>/dev/null || echo "000")
  if [ "$STATUS" = "200" ]; then
    log "✓ Health check passed (HTTP 200)"
    break
  fi
  log "  Attempt $i/$HEALTH_RETRIES — HTTP $STATUS, retrying..."
  if [ "$i" -eq "$HEALTH_RETRIES" ]; then
    fail "Health check failed after $HEALTH_RETRIES attempts (last status: $STATUS)"
  fi
  sleep 3
done

# ─── Success ──────────────────────────────────────────────────────────────────
trap - ERR
log "═══════════════════════════════════"
log "✓ Deploy complete: $PREV_COMMIT → $NEW_COMMIT"
log "═══════════════════════════════════"
