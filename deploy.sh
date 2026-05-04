#!/bin/bash
set -e

# Script de deploy — uso: ./deploy.sh
# Roda no servidor após git pull (ou upload do código)

echo "🐳 [1/5] Rebuildando imagem do app..."
docker compose build app

echo "🚀 [2/5] Recriando container..."
docker compose up -d --force-recreate app

echo "⏳ Aguardando container ficar saudável..."
sleep 5

echo "📦 [3/5] Rodando migrations..."
docker compose exec -T app php artisan migrate --force

# Seeder das listas roda só uma vez (updateOrInsert é idempotente, então pode sempre rodar sem duplicar)
echo "🌱 [4/5] Populando listas de domínio..."
docker compose exec -T app php artisan db:seed --class=ListasDominioSeeder --force

echo "🧹 [5/5] Regerando caches..."
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan optimize

echo "✅ Deploy concluído."
