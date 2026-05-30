# Watcher CRM

[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

CRM for Internet Service Providers, built on [CakePHP](https://github.com/cakephp/cakephp).

## Description

- Customer Relationship Management system aimed at small and mid-size ISPs
  (customer / contract / billing / IP address management).
- Generates invoices in dBase format for the POHODA Stormware bookkeeping software.
- Integrates with optional services for RADIUS accounting, RouterOS device
  inventory, the [geo-addresses-postgis](https://github.com/Mapiiik/geo-addresses-postgis)
  registry lookup API (CZ RUIAN / HR DGU), and a few SaaS providers
  (Eurofaktura, SledovaniTV, Android SMS Gateway, …).

## Requirements

- PHP 8.2 or newer
- PostgreSQL
- Redis
- PECL `dbase` extension (only when using the POHODA invoice export)
- [Watcher Agent](https://github.com/Mapiiik/Watcher-Agent) — required only for
  host reachability checks (ping) and RADIUS session disconnect. It is a
  separate service (run it in Docker, even on the same host; it supports the
  PROXY protocol). These actions no longer run locally, so the app container
  needs no `ping` binary.
- [geo-addresses-postgis](https://github.com/Mapiiik/geo-addresses-postgis) —
  required only for address validation and registry lookups (CZ RUIAN / HR DGU).
  It is a separate service exposing a REST API; point the app at it via
  `ADDRESSES_API_URL` / `ADDRESSES_API_KEY`.

The Docker Compose stack below provides PostgreSQL and Redis out of the box,
so on a fresh host you only need Docker.

## Installation

Two install paths are supported. Docker Compose is recommended.

### Option A — Docker Compose (recommended)

```bash
git clone https://github.com/Mapiiik/Watcher-CRM.git
cd Watcher-CRM
cp config/.env.example config/.env
# edit config/.env — set APP_NAME and any integration URLs / API keys
docker compose -f compose.production.yaml up -d
```

The production image runs `composer run-script migrations` and rebuilds the
schema cache automatically on container start, so the app is reachable at
`http://localhost` (and `https://localhost` with a self-signed cert) once the
container is healthy. Set `SERVER_NAME` in the compose environment to a real
domain to enable Let's Encrypt issuance via the bundled `acme.sh`.

### Option B — Bare-metal (host nginx + PHP-FPM, FrankenPHP, …)

For hosts already running their own PHP webserver:

```bash
git clone https://github.com/Mapiiik/Watcher-CRM.git
cd Watcher-CRM
composer install --no-dev
cp config/.env.example config/.env
# edit config/.env — at minimum DATABASE_URL and CACHE_*_URL

composer run-script migrations
composer run-script schema-cache
```

Point your webserver's document root at the `webroot/` directory. Install the
PECL `dbase` extension if you plan to use the POHODA invoice export in dBASE format.

## Configuration

Runtime settings live in `config/.env` (or are passed in as environment
variables — see the `environment:` blocks in the compose files for the keys
read at boot). `config/.env.example` is the canonical list; common groups are:

- **Database / cache:** `DATABASE_URL`, `CACHE_*_URL`
- **Integrations:** `WATCHER_NMS_URL/_KEY`, `WATCHER_AGENT_URL/_KEY`,
  `ADDRESSES_API_URL/_KEY`, `EUROFAKTURA_API_URL`, `ANDROID_SMS_GATEWAY_URL`, …

### Customizing the compose stack

If `compose.production.yaml` doesn't fit your environment, copy it to
`compose.yaml` and customize there — `compose.yaml` is git-ignored, so
`git pull` won't overwrite your changes.

```bash
cp compose.production.yaml compose.yaml
# edit compose.yaml as needed
docker compose up -d
```

Typical reasons to override: pointing services at infrastructure already
running on the host (e.g. an existing PostgreSQL instance, external Redis,
reverse proxy), removing bundled containers you don't need, or tweaking
volumes / networks.

## Development

Two compose files target local development:

- `compose.dev-frankenphp.yaml` — FrankenPHP (HTTP/1.1, HTTP/2, HTTP/3)
- `compose.dev-nginx.yaml` — classic nginx + PHP-FPM

Both bind-mount the working tree into the container and place `vendor/`,
`tmp/`, `logs/`, `node_modules/`, plus the PostgreSQL data directory and
Redis data on tmpfs — fast iteration and disposable state, but everything in
those paths is lost when the stack is torn down.

```bash
docker compose -f compose.dev-frankenphp.yaml up
```

The Postgres and Redis ports are exposed to the host (`5432`, `6379`) so you
can connect with local clients while the stack is running.

## License

Watcher CRM is licensed under the GNU Affero General Public License v3.0.
Copyright (c) 2026 Martin Patočka.

### What this means

You are free to use, modify and run this software. If you modify it and make
it available to others (including as a network service), you must also make
your modifications available under the same license.
