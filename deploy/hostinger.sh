#!/usr/bin/env bash
# Installs release.tgz (built by .github/workflows/deploy.yml) on a Hostinger
# shared-hosting account. Runs on the server in the home directory.
#
#   ~/domains/<domain>/alhayah-gold    the app (replaced on every deploy)
#   ~/domains/<domain>/alhayah-shared  .env, storage/ and database.sqlite (kept)
#   ~/domains/<domain>/public_html     symlink to alhayah-gold/public; whatever
#                                      was there first is moved to public_html.backup-*
#
# Env: DOMAIN (optional when the account has one domain), SEED=true to load the
# demo data again. The admin password for seeding is read from stdin.
set -euo pipefail

ADMIN_PASSWORD=""
read -r ADMIN_PASSWORD || true

# Laravel 13 needs PHP 8.3+. Hostinger keeps several versions under /opt/alt.
PHP=""
for candidate in php /opt/alt/php85/usr/bin/php /opt/alt/php84/usr/bin/php /opt/alt/php83/usr/bin/php; do
    if command -v "$candidate" >/dev/null 2>&1 && "$candidate" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);'; then
        PHP=$candidate
        break
    fi
done
if [ -z "$PHP" ]; then
    echo "No PHP 8.3+ found. Pick PHP 8.3 or newer for the site in hPanel and run the deploy again." >&2
    exit 1
fi
echo "PHP: $PHP ($("$PHP" -r 'echo PHP_VERSION;'))"

if [ -z "${DOMAIN:-}" ]; then
    mapfile -t domains < <(ls -1 "$HOME/domains" 2>/dev/null)
    if [ "${#domains[@]}" -ne 1 ]; then
        echo "Domains on this account:" >&2
        printf '  - %s\n' "${domains[@]}" >&2
        echo "Run the deploy again with the domain input set to one of them." >&2
        exit 1
    fi
    DOMAIN=${domains[0]}
fi

ROOT="$HOME/domains/$DOMAIN"
if [ ! -d "$ROOT" ]; then
    echo "$ROOT does not exist. Domains:" >&2
    ls -1 "$HOME/domains" >&2 || true
    exit 1
fi
echo "Domain: $DOMAIN"

APP="$ROOT/alhayah-gold"
NEW="$ROOT/alhayah-gold.new"
OLD="$ROOT/alhayah-gold.old"
SHARED="$ROOT/alhayah-shared"

rm -rf "$NEW"
mkdir -p "$NEW" "$SHARED"
tar -xzf "$HOME/release.tgz" -C "$NEW"

# storage/ and the database live outside the release so deploys keep them.
if [ ! -d "$SHARED/storage" ]; then
    cp -a "$NEW/storage" "$SHARED/storage"
fi
mkdir -p "$SHARED/storage/logs" "$SHARED/storage/app/public" \
    "$SHARED/storage/framework/cache/data" "$SHARED/storage/framework/sessions" "$SHARED/storage/framework/views"
rm -rf "$NEW/storage"
ln -s "$SHARED/storage" "$NEW/storage"
touch "$SHARED/database.sqlite"

FIRST=false
if [ ! -f "$SHARED/.env" ]; then
    FIRST=true
    cp "$NEW/.env.example" "$SHARED/.env"
    sed -i \
        -e 's|^APP_ENV=.*|APP_ENV=production|' \
        -e 's|^APP_DEBUG=.*|APP_DEBUG=false|' \
        -e "s|^APP_URL=.*|APP_URL=https://$DOMAIN|" \
        -e 's|^LOG_LEVEL=.*|LOG_LEVEL=warning|' \
        -e 's|^OTP_SHOW_CODE=.*|OTP_SHOW_CODE=true|' \
        "$SHARED/.env"
    echo "DB_DATABASE=$SHARED/database.sqlite" >> "$SHARED/.env"
    chmod 600 "$SHARED/.env"
fi
ln -sfn "$SHARED/.env" "$NEW/.env"

# Swap the release in, then build caches at its final path.
rm -rf "$OLD"
if [ -d "$APP" ]; then
    mv "$APP" "$OLD"
fi
mv "$NEW" "$APP"
cd "$APP"

if [ "$FIRST" = true ]; then
    "$PHP" artisan key:generate --force
fi
"$PHP" artisan config:clear
"$PHP" artisan migrate --force

if [ "$FIRST" = true ] || [ "${SEED:-false}" = true ]; then
    if [ -z "$ADMIN_PASSWORD" ]; then
        echo "Seeding needs the ADMIN_PASSWORD secret (the admin panel password)." >&2
        exit 1
    fi
    SEED_ADMIN_PASSWORD="$ADMIN_PASSWORD" "$PHP" artisan db:seed --force
fi

"$PHP" artisan storage:link --force >/dev/null 2>&1 || true
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache

# Point the web root at public/. Keep whatever was there before.
PUB="$ROOT/public_html"
if [ -e "$PUB" ] && [ ! -L "$PUB" ]; then
    BACKUP="$ROOT/public_html.backup-$(date +%Y%m%d%H%M%S)"
    mv "$PUB" "$BACKUP"
    echo "Old public_html moved to $BACKUP"
fi
ln -sfn "$APP/public" "$PUB"

rm -rf "$OLD" "$HOME/release.tgz" "$HOME/hostinger.sh"
echo "Deployed to https://$DOMAIN"
