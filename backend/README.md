# FiscalMix Backend - Setup Guide

## Pré-requisitos

- Docker + OrbStack (ou Docker Desktop)
- PHP 8.3+ (opcional, para desenvolvimento local)
- Composer (opcional, para desenvolvimento local)

## Setup com Docker

### 1. Iniciar serviços

```bash
cd /Users/rafaelfmachado/.gemini/antigravity/scratch/fiscalmix
docker-compose up -d
```

### 2. Instalar dependências do Laravel

```bash
docker-compose exec backend composer install
```

### 3. Configurar .env

```bash
docker-compose exec backend cp .env.example .env
docker-compose exec backend php artisan key:generate
```

### 4. Executar migrations

```bash
docker-compose exec backend php artisan migrate
```

### 5. Criar tabelas do Sanctum

```bash
docker-compose exec backend php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
docker-compose exec backend php artisan migrate
```

## Testar API

### Registro de usuário

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Admin",
    "email": "admin@fiscalmix.com",
    "password": "senha123",
    "password_confirmation": "senha123",
    "account_name": "FiscalMix Demo"
  }'
```

### Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@fiscalmix.com",
    "password": "senha123"
  }'
```

### Criar empresa (com token)

```bash
curl -X POST http://localhost:8000/api/companies \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -d '{
    "cnpj": "12345678000190",
    "razao_social": "Empresa XYZ Ltda",
    "uf": "SP"
  }'
```

## Estrutura Criada

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/AuthController.php
│   │   │   └── CompanyController.php
│   │   └── Middleware/
│   │       └── SetTenantContext.php
│   ├── Models/
│   │   ├── Account.php
│   │   ├── User.php
│   │   ├── Company.php
│   │   ├── Certificate.php
│   │   ├── FiscalDocument.php
│   │   └── SyncRun.php
│   └── Providers/
│       └── AppServiceProvider.php
├── database/
│   └── migrations/
│       ├── 2024_01_01_000001_create_core_tables.php
│       └── 2024_01_01_000002_create_fiscal_tables.php
└── routes/
    └── api.php
```

## Próximos Passos

1. Implementar CertificateController (upload A1)
2. Implementar DocumentController (consulta e filtros)
3. Implementar SyncController e SyncDFeJob
4. Implementar ExportController e ExportZipJob
5. Criar testes unitários e de integração
