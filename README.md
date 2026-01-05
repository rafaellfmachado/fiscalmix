# FiscalMix

> Plataforma SaaS B2B moderna para gestão de documentos fiscais eletrônicos no Brasil

## 🚀 Stack Tecnológico

- **Backend**: Laravel 11 (PHP 8.3) + PostgreSQL 16 + Redis 7
- **Frontend**: Vue 3 + TypeScript + PrimeVue 3.x
- **Infraestrutura**: Docker + Nginx + AWS S3

## 📋 Pré-requisitos

- Docker 24+ e Docker Compose 2+
- Git
- Node.js 20+ (para desenvolvimento frontend local)

## 🛠️ Setup Rápido

### 1. Clone o repositório

```bash
git clone https://github.com/SEU_USUARIO/fiscalmix.git
cd fiscalmix
```

### 2. Inicie os serviços

```bash
docker-compose up -d
```

### 3. Acesse a aplicação

- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:8000/api
- **PostgreSQL**: localhost:5432
- **Redis**: localhost:6379

## 📁 Estrutura do Projeto

```
fiscalmix/
├── backend/              # Laravel 11 API
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Repositories/
│   │   └── Jobs/
│   ├── database/migrations/
│   ├── routes/api.php
│   └── tests/
├── frontend/             # Vue 3 + PrimeVue
│   ├── src/
│   │   ├── components/
│   │   ├── views/
│   │   ├── stores/
│   │   └── router/
│   └── package.json
├── docker/               # Configurações Docker
│   ├── nginx/
│   └── postgres/
└── docker-compose.yml
```

## 📚 Documentação

- [PRD (Product Requirements)](docs/prd_fiscalmix.md)
- [Arquitetura Técnica](docs/architecture.md)
- [UX/UI Design System](docs/ux_ui_design.md)
- [Estratégia de Testes](docs/testing_strategy.md)

## 🎯 Roadmap

### MVP (8 semanas) - Em Desenvolvimento
- [x] Setup inicial do projeto
- [ ] Autenticação (JWT)
- [ ] Multi-tenant (Account → Companies → Users)
- [ ] Certificados A1
- [ ] Conector SEFAZ DF-e (NF-e, CT-e, MDF-e)
- [ ] Consulta e filtros avançados
- [ ] Download XML/PDF + Export ZIP
- [ ] UI PrimeVue

### V1 (+12 semanas)
- [ ] NFS-e (5 municípios)
- [ ] Certificado A3 (Bridge)
- [ ] RBAC por empresa
- [ ] Notificações (e-mail + webhook)
- [ ] Billing (Stripe)

## 🧪 Testes

```bash
# Backend (PHPUnit)
docker-compose exec backend php artisan test

# Frontend (Vitest + Cypress)
cd frontend
npm run test:unit
npm run test:e2e
```

## 📝 Licença

Proprietary - Todos os direitos reservados

## 👥 Time

- **Product Manager**: [Nome]
- **Tech Lead**: [Nome]
- **Backend**: [Nome]
- **Frontend**: [Nome]

---

**Versão**: 0.1.0 (MVP em desenvolvimento)  
**Última atualização**: Janeiro 2026
