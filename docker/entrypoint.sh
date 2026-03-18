#!/bin/sh
set -e

# Cria link simbólico do storage se não existir
php artisan storage:link --force 2>/dev/null || true

# Roda as migrations
# php artisan migrate --force

# Otimiza para produção
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
