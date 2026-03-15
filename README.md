# Sistema GED - Laravel + Blade

Projeto base de um sistema de cadastro online com:

- CRUD completo de usuários
- CRUD completo de clientes
- GED (Gestão Eletrônica de Documentos) por cliente
- Controle de acesso por níveis/perfis
- Layout moderno com Blade
- Upload, visualização e download de documentos

## Requisitos

- PHP 8.2+
- Composer
- Node.js 20+
- MySQL 8+

## Instalação

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan storage:link
npm install
npm run build
```

Configure o banco no `.env` e depois rode:

```bash
php artisan migrate --seed
php artisan serve
```

## Login inicial

O seeder cria um usuário administrador padrão:

- **E-mail:** admin@sistema.local
- **Senha:** password

## Perfis

- Administrador
- Operador
- Consulta

## Estrutura funcional

### Usuários
- listar
- criar
- editar
- excluir
- ativar/inativar
- atribuir perfil

### Clientes
- listar
- criar
- editar
- excluir
- buscar por nome/documento
- visualizar documentos vinculados

### GED
- upload por cliente
- metadados do documento
- download
- visualização
- exclusão
- filtro por cliente/título/tipo

## Pacotes usados

- Laravel Framework
- Spatie Laravel Permission

## Observações

- Este ZIP é um **starter pronto**. Após `composer install` e `npm install`, o sistema sobe normalmente.
- A autenticação foi implementada em controllers e views Blade próprias para manter o projeto simples de instalar.
- Se quiser, depois eu posso te entregar uma **versão 2** com:
  - dashboard com gráficos
  - auditoria de ações
  - notificações de vencimento
  - upload múltiplo
  - tema admin premium
  - API REST
