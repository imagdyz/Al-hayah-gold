#!/usr/bin/env bash
# Read-only health report for the site on a Hostinger account. Changes nothing.
# Env: DOMAIN (required).
set -u
ROOT="$HOME/domains/$DOMAIN"
APP="$ROOT/alhayah-gold"
SHARED="$ROOT/alhayah-shared"

section() { printf '\n===== %s =====\n' "$1"; }

section "Domain folder"
ls -la "$ROOT" 2>&1
echo "public_html -> $(readlink "$ROOT/public_html" 2>&1)"
ls -la "$ROOT/public_html/" 2>&1 | head -20

section "App"
ls -la "$APP" 2>&1 | head -30
ls -la "$APP/public" 2>&1
ls -la "$APP/bootstrap/cache" 2>&1

section "Shared storage and database"
ls -la "$SHARED" 2>&1
ls -la "$SHARED/storage" "$SHARED/storage/framework" "$SHARED/storage/logs" 2>&1
grep -E '^(APP_ENV|APP_DEBUG|APP_URL|DB_CONNECTION|GOLD_PRICE_SOURCE|CACHE_STORE|SESSION_DRIVER)=' "$SHARED/.env" 2>&1

section "Disk"
df -h "$HOME" 2>&1 | tail -2
quota -s 2>&1 | tail -3 || true
du -sh "$SHARED" "$APP" 2>&1

section "PHP"
for p in php /opt/alt/php83/usr/bin/php /opt/alt/php84/usr/bin/php /opt/alt/php85/usr/bin/php; do
    command -v "$p" >/dev/null 2>&1 && echo "$p: $("$p" -r 'echo PHP_VERSION;' 2>&1)"
done
for f in "$ROOT/public_html/.htaccess" "$APP/public/.htaccess" "$ROOT/.htaccess" "$HOME/.htaccess" "$ROOT/public_html/.user.ini" "$ROOT/public_html/php.ini"; do
    [ -f "$f" ] && { echo "--- $f"; grep -inE 'php|handler|AddType' "$f" | head -10; }
done

section "Laravel errors (first line of the last 15)"
grep -hE '^\[[0-9-]+ [0-9:]+\] [a-z]+\.(ERROR|CRITICAL|ALERT|EMERGENCY)' "$SHARED"/storage/logs/*.log 2>/dev/null | tail -15 | cut -c1-400

section "Hostinger error logs"
for d in "$ROOT/logs" "$HOME/logs"; do
    [ -d "$d" ] && for f in "$d"/*; do
        [ -f "$f" ] && { echo "--- $f"; tail -15 "$f" | cut -c1-400; }
    done
done

section "artisan about"
PHP=/opt/alt/php85/usr/bin/php
[ -x "$PHP" ] || PHP=php
cd "$APP" 2>/dev/null && "$PHP" artisan about --only=environment,cache,drivers 2>&1 | head -40

section "Render the home page from the CLI"
cd "$APP" 2>/dev/null && "$PHP" -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$res = $kernel->handle(Illuminate\Http\Request::create("/", "GET"));
echo "status ", $res->getStatusCode(), "\n";
if ($res->getStatusCode() >= 500 && isset($res->exception)) { echo get_class($res->exception), ": ", $res->exception->getMessage(), "\n"; }
' 2>&1 | head -20

section "Crontab"
crontab -l 2>&1 | head -5
