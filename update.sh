#!/usr/bin/env bash
#
# Bring a plain installation up to what the current branch holds.
#
# For installations that do not run in Docker; the container image does the same work from its
# entrypoint. Run it as the user the web server runs as - anything created here has to stay
# writable for it afterwards.
#
set -euo pipefail

cd "$(dirname "$0")"

# `git pull` would stop halfway through a tree somebody has edited by hand, leaving the code
# updated and the rest of this script unrun. Better to say so before touching anything.
if [ -n "$(git status --porcelain)" ]; then
    echo "The working tree has local changes; commit or discard them first." >&2
    exit 1
fi

git pull --ff-only

# `--no-interaction` matters: the post-install hook asks about folder permissions when it thinks
# somebody is watching, and over SSH it thinks so.
composer install --no-dev --no-interaction --optimize-autoloader

# The application and every plugin that carries migrations of its own.
composer migrations -- --no-lock

# Plugin assets are symlinked into the webroot so the web server serves them itself, rather than
# every request for them going through PHP.
composer plugin-assets

# What the previous code left cached, including the table schema the migrations just changed.
bin/cake cache clear_all

# Read the new schema once here rather than on somebody's first request.
composer schema-cache

# Compiled PHP is held in opcache. Where it is configured not to check file timestamps
# (`opcache.validate_timestamps=0`), the old code keeps being served until the pool is reloaded.
if [ -n "${PHP_FPM_SERVICE:-}" ]; then
    systemctl reload "${PHP_FPM_SERVICE}"
fi

echo "Updated to $(git rev-parse --short HEAD)."
