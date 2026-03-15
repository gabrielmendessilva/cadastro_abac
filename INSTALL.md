# Como usar este ZIP

Este ZIP contém o **código-fonte do sistema** em Laravel.

## Opção 1 — Subir em um Laravel limpo

```bash
composer create-project laravel/laravel sistema-ged
cd sistema-ged
composer require spatie/laravel-permission
npm install
```

Depois, copie os arquivos deste ZIP por cima do projeto Laravel criado.

Rode:

```bash
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
npm run build
php artisan serve
```

## Opção 2 — Usar este ZIP diretamente

Se você já tiver um ambiente Laravel configurado localmente, extraia o ZIP e instale as dependências:

```bash
composer install
npm install
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
npm run build
php artisan serve
```

## Acesso inicial

- E-mail: `admin@sistema.local`
- Senha: `password`
