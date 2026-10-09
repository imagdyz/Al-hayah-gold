#!/usr/bin/env bash
# Takes the site off the web or puts it back. Runs on the server.
#
#   MODE=off  public_html points at an empty page for every URL and the price
#             cron is removed. The app, .env, storage and database stay as they are.
#   MODE=on   public_html points back at alhayah-gold/public and the cron returns.
#
# Env: DOMAIN (required), MODE (off|on). A deploy also puts the site back on.
set -euo pipefail

ROOT="$HOME/domains/$DOMAIN"
APP="$ROOT/alhayah-gold"
OFF="$ROOT/offline"
PUB="$ROOT/public_html"

if [ ! -d "$APP/public" ]; then
    echo "$APP/public not found." >&2
    exit 1
fi
if [ -e "$PUB" ] && [ ! -L "$PUB" ]; then
    echo "$PUB is a real folder, not the deploy's symlink. Leaving it alone." >&2
    exit 1
fi

PHP=""
for candidate in php /opt/alt/php85/usr/bin/php /opt/alt/php84/usr/bin/php /opt/alt/php83/usr/bin/php; do
    if command -v "$candidate" >/dev/null 2>&1 && "$candidate" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);'; then
        PHP=$candidate
        break
    fi
done
CRON="* * * * * cd $APP && $PHP artisan schedule:run >> /dev/null 2>&1"

current_cron() { crontab -l 2>/dev/null || true; }

case "${MODE:-}" in
off)
    mkdir -p "$OFF"
    printf '<!doctype html><meta charset="utf-8"><meta name="robots" content="noindex"><title></title>\n' > "$OFF/index.html"
    cat > "$OFF/.htaccess" <<'HTACCESS'
DirectoryIndex index.html
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_URI} !^/index\.html$
RewriteRule ^ /index.html [L]
</IfModule>
ErrorDocument 404 /index.html
HTACCESS
    chmod 755 "$OFF"
    chmod 644 "$OFF/index.html" "$OFF/.htaccess"
    ls -la "$OFF"
    ln -sfn "$OFF" "$PUB"
    echo "public_html -> $(readlink "$PUB")"

    if command -v crontab >/dev/null 2>&1; then
        if current_cron | grep -qF "$APP && "; then
            if { current_cron | grep -vF "$APP && " || true; } | crontab - 2>/dev/null; then
                echo "Cron job removed."
            else
                echo "CRON: could not remove it. Delete it in hPanel > Advanced > Cron Jobs."
            fi
        else
            echo "No cron job for the site in crontab."
        fi
    else
        echo "CRON: no crontab over SSH. Delete the job in hPanel > Advanced > Cron Jobs."
    fi
    ;;
on)
    ln -sfn "$APP/public" "$PUB"
    echo "public_html -> $(readlink "$PUB")"

    if command -v crontab >/dev/null 2>&1 && [ -n "$PHP" ]; then
        if current_cron | grep -qF "$APP && "; then
            echo "Cron job already there."
        elif { current_cron; echo "$CRON"; } | crontab - 2>/dev/null; then
            echo "Cron job added."
        else
            echo "CRON: add this in hPanel > Advanced > Cron Jobs:"
            echo "  $CRON"
        fi
    fi
    ;;
*)
    echo "MODE must be off or on." >&2
    exit 1
    ;;
esac

echo "App kept at $APP"
ls -la "$ROOT/alhayah-shared/database.sqlite" 2>&1
echo "Crontab now:"
current_cron | sed 's/^/  /'
