#!/bin/sh
set -e

# Cria link simbólico do storage se não existir
php artisan storage:link --force 2>/dev/null || true

# Limpa caches velhos antes de regerar (importante após rebuild com código novo)
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Roda as migrations (idempotente — só aplica o que falta)
php artisan migrate --force

# Otimiza para produção
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
