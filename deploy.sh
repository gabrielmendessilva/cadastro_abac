#!/bin/bash
set -e

# Script de deploy — uso: ./deploy.sh
# Roda no servidor após git pull (ou upload do código)

echo "🐳 [1/6] Rebuildando imagem do app..."
docker compose build app

echo "🚀 [2/6] Recriando container..."
docker compose up -d --force-recreate app

echo "⏳ Aguardando container ficar saudável..."
sleep 5

echo "🎨 [3/6] Sincronizando assets buildados (Vite) do container para o host..."
# O nginx serve ./public do host; o Vite buildou dentro do container.
# Sem este passo, o navegador recebe 404 nos arquivos JS/CSS hashados.
rm -rf ./public/build
docker compose cp app:/var/www/public/build ./public/build

echo "📦 [4/6] Rodando migrations..."
docker compose exec -T app php artisan migrate --force

echo "🌱 [5/6] Populando listas de domínio (idempotente)..."
docker compose exec -T app php artisan db:seed --class=ListasDominioSeeder --force

echo "🧹 [6/6] Regerando caches..."
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan optimize

echo "✅ Deploy concluído."
