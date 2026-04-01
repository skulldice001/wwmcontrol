#!/bin/sh
set -e

# Only run init tasks for the web app process (supervisord).
# Queue workers and the scheduler re-use the same image but skip this block.
if echo "$*" | grep -q "supervisord"; then
    echo "[entrypoint] Running app initialisation..."

    # Create storage symlink (public/storage -> storage/app/public)
    php artisan storage:link --force 2>/dev/null || true

    # Run database migrations (idempotent)
    php artisan migrate --force

    # Cache config / routes / views for performance
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    echo "[entrypoint] Init complete."
fi

exec "$@"
