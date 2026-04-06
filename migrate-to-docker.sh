#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# migrate-to-docker.sh — Migrate production server from bare PHP-FPM to Docker
# Run ON the server: bash migrate-to-docker.sh
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

APP_DIR="/var/www/wwmcontrol"
NGINX_SITE="/etc/nginx/sites-enabled/thezootopia.online"
LOG="/var/log/wwmcontrol-migrate.log"

log() { echo "[$(date '+%H:%M:%S')] $*" | tee -a "$LOG"; }
die() { log "✗ ERROR: $*"; exit 1; }

log "════════════════════════════════════════"
log "Migration: bare PHP-FPM → Docker"
log "════════════════════════════════════════"

# ── 1. Install Docker ─────────────────────────────────────────────────────────
if ! command -v docker &>/dev/null; then
    log "[1/8] Installing Docker..."
    curl -fsSL https://get.docker.com | bash
    systemctl enable docker
    systemctl start docker
    log "      Docker installed: $(docker --version)"
else
    log "[1/8] Docker already installed: $(docker --version)"
fi

# ── 2. Pull latest code ───────────────────────────────────────────────────────
log "[2/8] Pulling latest code..."
cd "$APP_DIR"
git pull --ff-only origin "$(git rev-parse --abbrev-ref HEAD)"

# ── 3. Create .env for Docker ─────────────────────────────────────────────────
log "[3/8] Creating Docker .env..."
# Read current server values
CURRENT_ENV="$APP_DIR/.env"

get_env() { grep "^${1}=" "$CURRENT_ENV" | cut -d= -f2- | tr -d '"' | tr -d "'"; }

cat > "$APP_DIR/.env.docker" << EOF
APP_NAME=$(get_env APP_NAME)
APP_ENV=production
APP_KEY=$(get_env APP_KEY)
APP_DEBUG=false
APP_URL=$(get_env APP_URL)
FRONTEND_URL=$(get_env APP_URL)

LOG_CHANNEL=stack
LOG_LEVEL=error

# Database (host PostgreSQL — DO NOT change DB_HOST)
DB_CONNECTION=pgsql
DB_HOST=host.docker.internal
DB_PORT=$(get_env DB_PORT)
DB_DATABASE=$(get_env DB_DATABASE)
DB_USERNAME=$(get_env DB_USERNAME)
DB_PASSWORD=$(get_env DB_PASSWORD)

# Drivers (Redis in Docker)
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_DOMAIN=$(get_env SESSION_DOMAIN)
SESSION_SECURE_COOKIE=true
CACHE_STORE=redis

# Reverb WebSocket
REVERB_APP_ID=$(get_env REVERB_APP_ID)
REVERB_APP_KEY=$(get_env REVERB_APP_KEY)
REVERB_APP_SECRET=$(get_env REVERB_APP_SECRET)
REVERB_SERVER_PORT=8080

# Vite build args (baked into JS at build time)
VITE_REVERB_HOST=thezootopia.online
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=https

# Sanctum
SANCTUM_STATEFUL_DOMAINS=$(get_env SESSION_DOMAIN)

# Discord
DISCORD_CLIENT_ID=$(get_env DISCORD_CLIENT_ID)
DISCORD_CLIENT_SECRET=$(get_env DISCORD_CLIENT_SECRET)
DISCORD_REDIRECT_URI=$(get_env DISCORD_REDIRECT_URI)
DISCORD_BOT_TOKEN=$(get_env DISCORD_BOT_TOKEN)
DISCORD_GUILD_ID=$(get_env DISCORD_GUILD_ID)
DISCORD_SSL_VERIFY=false
EOF

log "      .env.docker created"

# ── 4. Allow PostgreSQL to accept connections from Docker ─────────────────────
log "[4/8] Configuring PostgreSQL to accept Docker connections..."
PG_HBA="/etc/postgresql/14/main/pg_hba.conf"
PG_HBA_ALT=$(find /etc/postgresql -name pg_hba.conf 2>/dev/null | head -1)
PG_HBA="${PG_HBA_ALT:-$PG_HBA}"

if [ -f "$PG_HBA" ]; then
    # Docker bridge network is typically 172.17.0.0/16
    if ! grep -q "172.17.0.0/16" "$PG_HBA"; then
        echo "host    all             all             172.17.0.0/16           md5" >> "$PG_HBA"
        # Also allow docker compose bridge range
        echo "host    all             all             172.16.0.0/12           md5" >> "$PG_HBA"
        systemctl reload postgresql || service postgresql reload || true
        log "      pg_hba.conf updated — Docker IPs allowed"
    else
        log "      pg_hba.conf already allows Docker IPs"
    fi
else
    log "      WARNING: pg_hba.conf not found at $PG_HBA — verify manually"
fi

# Also ensure PostgreSQL listens on all interfaces (or at least docker bridge)
PG_CONF=$(find /etc/postgresql -name postgresql.conf 2>/dev/null | head -1)
if [ -f "$PG_CONF" ]; then
    if grep -q "^#listen_addresses\|^listen_addresses = 'localhost'" "$PG_CONF"; then
        sed -i "s/^#*listen_addresses.*/listen_addresses = '*'/" "$PG_CONF"
        systemctl reload postgresql || service postgresql reload || true
        log "      postgresql.conf: listen_addresses set to '*'"
    fi
fi

# ── 5. Build Docker image ─────────────────────────────────────────────────────
log "[5/8] Building Docker image (this takes a few minutes)..."
cd "$APP_DIR"
REVERB_KEY=$(get_env REVERB_APP_KEY)

docker compose -f docker-compose.prod.yml \
    --env-file .env.docker \
    build \
    --build-arg VITE_APP_NAME="TheZoo" \
    --build-arg VITE_REVERB_APP_KEY="$REVERB_KEY" \
    --build-arg VITE_REVERB_HOST="thezootopia.online" \
    --build-arg VITE_REVERB_PORT="8080" \
    --build-arg VITE_REVERB_SCHEME="https" \
    app

log "      Image built successfully"

# ── 6. Stop old services ──────────────────────────────────────────────────────
log "[6/8] Stopping PHP-FPM, Supervisor and old Reverb..."
systemctl stop php8.2-fpm  2>/dev/null || true
supervisorctl stop all     2>/dev/null || true
# Kill any bare php reverb process
pkill -f "artisan reverb:start" 2>/dev/null || true
# Remove old crontab scheduler entry if any
crontab -l 2>/dev/null | grep -v "schedule:run" | crontab - 2>/dev/null || true
log "      Old services stopped"

# ── 7. Start Docker services ──────────────────────────────────────────────────
log "[7/8] Starting Docker services..."
cd "$APP_DIR"
# Symlink .env.docker → .env for docker compose to pick up
cp .env .env.bare-backup
cp .env.docker .env

docker compose -f docker-compose.prod.yml up -d --remove-orphans

# Wait for app to be ready
log "      Waiting for app container to be healthy..."
RETRIES=20
until docker compose -f docker-compose.prod.yml exec -T app php artisan --version > /dev/null 2>&1; do
    RETRIES=$((RETRIES - 1))
    [ "$RETRIES" -le 0 ] && die "App container failed to start. Run: docker compose -f docker-compose.prod.yml logs app"
    sleep 3
done
log "      Docker services running"

# ── 8. Switch Nginx to reverse proxy ─────────────────────────────────────────
log "[8/8] Switching Nginx to reverse proxy mode..."
# Backup current config
cp "$NGINX_SITE" "${NGINX_SITE}.pre-docker.bak"
log "      Nginx backup: ${NGINX_SITE}.pre-docker.bak"

# Install new config
cp "$APP_DIR/docker/nginx-host.conf" "$NGINX_SITE"

nginx -t || die "Nginx config test failed. Restoring backup..."
nginx -s reload
log "      Nginx reloaded with reverse proxy config"

# ── Health check ──────────────────────────────────────────────────────────────
log "Running final health check..."
sleep 2
STATUS=$(curl -sk -o /dev/null -w "%{http_code}" "https://thezootopia.online/up" 2>/dev/null || echo "000")
if [ "$STATUS" = "200" ]; then
    log "✓ Health check passed (HTTP 200)"
else
    log "⚠ Health check returned HTTP $STATUS"
    log "  Check: docker compose -f docker-compose.prod.yml logs"
    log "  Rollback Nginx: cp ${NGINX_SITE}.pre-docker.bak $NGINX_SITE && nginx -s reload"
fi

# ── Done ──────────────────────────────────────────────────────────────────────
log "════════════════════════════════════════"
log "✓ Migration complete!"
log ""
log "Useful commands:"
log "  View logs  : docker compose -f /var/www/wwmcontrol/docker-compose.prod.yml logs -f"
log "  App shell  : docker compose -f /var/www/wwmcontrol/docker-compose.prod.yml exec app bash"
log "  Redeploy   : bash /var/www/wwmcontrol/deploy.sh"
log "  Rollback   : bash /var/www/wwmcontrol/deploy.sh --rollback"
log "════════════════════════════════════════"
