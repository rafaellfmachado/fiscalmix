# FiscalMix - Guia de Uso Rápido

## 🚀 Iniciar o Projeto

### 1. Subir os serviços Docker

```bash
cd /Users/rafaelfmachado/.gemini/antigravity/scratch/fiscalmix
docker-compose up -d
```

### 2. Instalar dependências do backend

```bash
docker-compose exec backend composer install
docker-compose exec backend cp .env.example .env
docker-compose exec backend php artisan key:generate
docker-compose exec backend php artisan migrate
```

### 3. Instalar dependências do frontend

```bash
cd frontend
npm install
npm run dev
```

### 4. Acessar a aplicação

- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:8000/api

---

## 📱 Fluxo de Uso

### 1. Criar Conta

1. Acesse http://localhost:5173/register
2. Preencha:
   - Nome da Empresa
   - Seu Nome
   - E-mail
   - Senha (mínimo 8 caracteres)
3. Clique em "Criar Conta"

### 2. Fazer Login

1. Acesse http://localhost:5173/login
2. Digite e-mail e senha
3. Clique em "Entrar"

### 3. Cadastrar Empresa

1. No dashboard, clique em "Gerenciar Empresas"
2. Clique em "Nova Empresa"
3. Preencha:
   - CNPJ (14 dígitos, sem formatação)
   - Razão Social
   - UF (2 letras)
4. Clique em "Salvar"

### 4. Upload Certificado A1 (via API)

```bash
curl -X POST http://localhost:8000/api/companies/{company_id}/certificates/a1 \
  -H "Authorization: Bearer SEU_TOKEN" \
  -F "file=@certificado.pfx" \
  -F "password=senha_do_certificado"
```

### 5. Sincronizar Documentos (em breve)

Será possível sincronizar documentos fiscais diretamente pela interface.

---

## 🧪 Testar API com cURL

### Registro

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@exemplo.com",
    "password": "senha123",
    "password_confirmation": "senha123",
    "account_name": "Minha Empresa Ltda"
  }'
```

### Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "joao@exemplo.com",
    "password": "senha123"
  }'
```

Resposta:
```json
{
  "access_token": "1|abcdef...",
  "token_type": "Bearer",
  "user": { ... }
}
```

### Listar Empresas

```bash
curl -X GET http://localhost:8000/api/companies \
  -H "Authorization: Bearer SEU_TOKEN"
```

### Criar Empresa

```bash
curl -X POST http://localhost:8000/api/companies \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "cnpj": "12345678000190",
    "razao_social": "Empresa XYZ Ltda",
    "uf": "SP"
  }'
```

---

## 📁 Estrutura do Projeto

```
fiscalmix/
├── backend/                    # Laravel 11 API
│   ├── app/
│   │   ├── Http/Controllers/  # AuthController, CompanyController, CertificateController
│   │   ├── Models/            # Account, User, Company, Certificate, FiscalDocument
│   │   ├── Middleware/        # SetTenantContext (RLS)
│   │   ├── Contracts/         # FiscalConnector interface
│   │   └── Services/          # SefazDFeConnector mock
│   ├── database/migrations/   # 2 migrations (11 tabelas)
│   └── routes/api.php         # Rotas da API
├── frontend/                   # Vue 3 + PrimeVue
│   ├── src/
│   │   ├── views/             # LoginView, RegisterView, DashboardView, CompaniesView
│   │   ├── stores/            # auth.ts (Pinia)
│   │   ├── router/            # index.ts (guards)
│   │   └── main.ts
│   └── vite.config.ts
├── docker/                     # Nginx configs, PostgreSQL init
└── docker-compose.yml          # Orquestração de serviços
```

---

## 🔧 Comandos Úteis

### Backend

```bash
# Executar migrations
docker-compose exec backend php artisan migrate

# Criar migration
docker-compose exec backend php artisan make:migration create_table_name

# Criar controller
docker-compose exec backend php artisan make:controller NomeController

# Limpar cache
docker-compose exec backend php artisan cache:clear
docker-compose exec backend php artisan config:clear
```

### Frontend

```bash
# Rodar dev server
npm run dev

# Build para produção
npm run build

# Lint
npm run lint
```

### Docker

```bash
# Ver logs
docker-compose logs -f backend
docker-compose logs -f frontend

# Reiniciar serviços
docker-compose restart

# Parar tudo
docker-compose down

# Rebuild
docker-compose up -d --build
```

---

## 🐛 Troubleshooting

### Backend não conecta no PostgreSQL

```bash
docker-compose down
docker-compose up -d postgres
# Aguardar 10 segundos
docker-compose up -d backend
```

### Frontend não carrega

1. Verificar se backend está rodando: `docker-compose ps`
2. Verificar proxy no `vite.config.ts`
3. Limpar cache: `rm -rf node_modules && npm install`

### Erro de CORS

Adicionar no `.env` do backend:
```
SANCTUM_STATEFUL_DOMAINS=localhost:5173
SPA_URL=http://localhost:5173
```

---

## 📊 Status Atual

✅ **Implementado:**
- Autenticação (registro, login, logout)
- CRUD de empresas
- Upload de certificados A1
- Conector SEFAZ mock
- Frontend com Vue 3 + PrimeVue

⏳ **Em Desenvolvimento:**
- Sincronização de documentos
- Consulta e filtros de documentos
- Exportação ZIP
- Testes automatizados

---

**Versão**: 0.5.0 (MVP 49% concluído)  
**Última atualização**: 2026-01-05
