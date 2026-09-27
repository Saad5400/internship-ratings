#!/bin/bash
# Railpack runs this as the container's start command (it replaces Railpack's
# default start-container.sh). It ports the old Nixpacks start.sh: prepare
# Laravel, then hand PID 1 to supervisord, which runs Octane (FrankenPHP), the
# queue worker and the scheduler (deploy/supervisord.conf).
set -e

cd /app

# Laravel writable paths (the storage volume may be empty on first boot)
mkdir -p /app/storage/framework/{cache,sessions,views} \
         /app/storage/logs \
         /app/storage/app/{private,private/livewire-tmp,public} \
         /app/bootstrap/cache
chmod -R 777 /app/storage /app/bootstrap/cache

php artisan storage:link || true

php artisan view:clear || true

# Re-cache config, routes, events and views against the live runtime env
# BEFORE migrating: the build step's config:cache holds whatever env the build
# saw, and a migrate that reads it can hit the wrong database (with no build
# env it is the default sqlite file). Nixpacks ran these the other way round.
php artisan optimize

php artisan migrate --force || true

exec supervisord -c /app/deploy/supervisord.conf -n
