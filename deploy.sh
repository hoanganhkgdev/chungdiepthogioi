#!/usr/bin/env bash
# Chạy TRÊN SERVER: lấy code mới từ GitHub rồi làm mới ứng dụng.
# Không đụng tới .env, vendor/ (được cập nhật bằng composer), public/uploads, public/assets, storage/.
set -euo pipefail
cd "$(dirname "$0")"

PHP="${PHP_BIN:-/opt/alt/php84/usr/bin/php}"   # CLI mặc định của host là PHP 8.1, project cần >= 8.3
COMPOSER="$PHP $(command -v composer)"
BRANCH="${1:-main}"

echo "==> Lấy code mới ($BRANCH)"
git fetch --prune origin "$BRANCH"
git reset --hard "origin/$BRANCH"

$PHP artisan down --retry=30 >/dev/null 2>&1 || true
trap '$PHP artisan up >/dev/null 2>&1 || true' EXIT

echo "==> composer install"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "==> migrate"
$PHP artisan migrate --force

echo "==> Filament assets + cache"
$PHP artisan filament:upgrade --no-interaction
# Không dùng route:cache vì routes/web.php có closure.
$PHP artisan config:cache
$PHP artisan view:cache

echo "==> Xong: $(git rev-parse --short HEAD)"
