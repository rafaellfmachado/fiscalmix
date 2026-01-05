# FiscalMix - Resumo Executivo

## 🎯 Visão Geral

**FiscalMix** é uma plataforma SaaS B2B moderna que revoluciona a gestão de documentos fiscais eletrônicos no Brasil, permitindo que empresas, contabilidades e grupos empresariais:

- **Centralizem** todos os documentos fiscais (NF-e, NFC-e, CT-e, MDF-e, NFS-e) em uma única plataforma
- **Automatizem** sincronizações incrementais com fontes oficiais (SEFAZ, prefeituras)
- **Gerenciem** múltiplos CNPJs com controle granular de permissões
- **Simplifiquem** o uso de certificados digitais (A1 e A3)

---

## 🏗️ Stack Tecnológico

### Backend
- **Laravel 11** (PHP 8.3)
- **PostgreSQL 16** (banco relacional)
- **Redis 7** (cache + filas)
- **AWS S3** (armazenamento de XMLs/PDFs)
- **Laravel Horizon** (monitoramento de filas)

### Frontend
- **Vue 3** (Composition API + TypeScript)
- **PrimeVue 3.x** (UI components)
- **Vite 5** (build tool)
- **Pinia** (state management)

### Infraestrutura
- **Docker + Docker Compose**
- **Nginx** (reverse proxy)
- **GitHub Actions** (CI/CD)
- **Prometheus + Grafana** (monitoramento)

---

## ✨ Principais Features

### MVP (8 semanas)
1. **Multi-tenant** (Account → Companies → Users)
2. **Certificado A1** (upload PFX, criptografia KMS)
3. **Conector SEFAZ DF-e** (NF-e, CT-e, MDF-e via NSU incremental)
4. **Consulta Avançada** (filtros por período, tipo, status, CNPJ, chave)
5. **Download** (XML individual + ZIP em lote assíncrono)
6. **Geração de PDF** (DANFE, DACTE, DAMDFE a partir do XML)
7. **Logs e Auditoria** (sync runs, downloads, ações do usuário)
8. **UI Moderna** (PrimeVue, responsiva, dark mode)

### V1 (+12 semanas)
9. **NFS-e** (padrão nacional + 5 municípios: SP, RJ, BH, Curitiba, POA)
10. **Certificado A3** (FiscalMix Bridge - agente local Electron)
11. **RBAC** (permissões por empresa: viewer, operator, admin)
12. **Notificações** (e-mail + webhooks)
13. **Relatórios** (dashboards, exportação CSV/Excel)
14. **Billing** (Stripe, upgrade/downgrade)

### V2 (Roadmap Futuro)
15. Integrações contábeis (Domínio, Alterdata, Sage)
16. Regras de retenção e arquivamento (LGPD)
17. OCR e extração de dados (itens, impostos)
18. Validações fiscais (CFOP, NCM, CST)
19. Mobile app (iOS/Android)

---

## 🔐 Segurança

### Certificados A1
- **Envelope Encryption** (AWS KMS)
  - DEK (Data Encryption Key) criptografa o .pfx
  - KMS criptografa o DEK
  - Senha nunca armazenada

### Tenant Isolation
- **Row-Level Security (RLS)** no PostgreSQL
- Todas as queries filtram por `account_id`
- Storage S3 segregado: `s3://bucket/{account_id}/{company_id}/`

### RBAC
- Roles globais (admin, member)
- Permissões por empresa (viewer, operator, admin)

### Outros
- **Rate Limiting** (60 req/min login, 1000 req/min API)
- **HTTPS/TLS 1.3**
- **Secrets Management** (AWS Secrets Manager / Vault)

---

## 📊 Modelo de Dados (Principais Tabelas)

```
accounts (tenant)
├── users
├── companies (CNPJs)
│   ├── certificates (A1/A3)
│   ├── connector_configs (NFS-e município/provedor)
│   ├── fiscal_documents (NF-e, CT-e, MDF-e, NFS-e)
│   │   └── fiscal_events (cancelamento, CCe)
│   └── sync_runs (logs de sincronização)
├── audit_logs
└── export_jobs (ZIP assíncronos)
```

**Total**: 15+ tabelas com índices otimizados

---

## 🔄 Fluxos Principais

### 1. Sincronização SEFAZ DF-e (NSU Incremental)

```
User → UI: "Sincronizar Agora"
  ↓
API: Criar sync_run → Dispatch SyncDFeJob
  ↓
Worker: Buscar último NSU → Conectar SEFAZ
  ↓
Loop: nfeDistDFeInteresse (NSU atual → ultNSU)
  ↓
Para cada documento:
  - Upload XML para S3
  - INSERT ON CONFLICT (deduplicação)
  - Atualizar NSU
  ↓
Finalizar: sync_run.status = 'completed'
```

### 2. Exportação ZIP em Lote

```
User → UI: Selecionar filtros + "Exportar ZIP"
  ↓
API: Criar export_job → Dispatch ExportZipJob
  ↓
Worker: Buscar documentos (paginado 100/vez)
  ↓
Loop: Download XML do S3 → Adicionar ao ZIP
  ↓
Upload ZIP final para S3 → Gerar URL assinada (1h)
  ↓
Notificar usuário (e-mail + UI)
```

---

## 🎨 UX/UI

### Design Principles
- **Simplicidade**: Interface limpa, sem complexidade
- **Eficiência**: Reduzir cliques (filtros rápidos, ações em lote)
- **Feedback**: Loading states, toasts, progress bars
- **Responsividade**: Mobile-first (sidebar → bottom nav)

### Componentes Principais
- **DocumentCard**: Card com badge de tipo, status, ações (XML, PDF, Ver)
- **FilterPanel**: Filtros rápidos + avançados (período, tipo, status, busca)
- **SyncRunsTable**: Logs de sincronização com status e reprocessamento
- **ExportJobsTable**: Progresso de exportações com download

### Telas (8 principais)
1. Login
2. Dashboard (resumo, saúde conectores, certificados)
3. Empresas (lista + detalhe)
4. Documentos (consulta + detalhe drawer)
5. Sincronizações (logs)
6. Exportações
7. Usuários
8. Configurações

---

## 🧪 Testes

### Pirâmide
- **Unit (60%)**: Services, Repositories, Helpers
- **Integration (30%)**: Conectores, Jobs, API
- **E2E (10%)**: Fluxos críticos (Cypress)

### Cenários Críticos
1. **Sync Incremental NSU**: Não duplicar documentos
2. **Deduplicação**: Atualizar status (ex: cancelamento)
3. **Envelope Encryption**: Criptografar/descriptografar A1
4. **Tenant Isolation**: RLS impede acesso cross-tenant

### CI/CD (GitHub Actions)
- **Backend**: PHPUnit (coverage > 80%)
- **Frontend**: Vitest + Cypress
- **Deploy**: Automático em staging/production

---

## 📈 Métricas de Sucesso

### North Star Metrics

| Métrica | Meta MVP | Meta V1 |
|---------|----------|---------|
| **Time to First Sync** | < 10 min | < 5 min |
| **Sync Success Rate** | > 95% | > 98% |
| **Documents Synced/mês** | 100k | 1M |
| **Active CNPJs** | 200 | 2k |
| **NPS** | > 50 | > 70 |

### Métricas Técnicas
- **API Latency (p95)**: < 500ms
- **Sync Job Duration (p95)**: < 5 min (1 mês de dados)
- **Uptime**: > 99.5%
- **Cobertura de Testes**: > 80%

---

## 🗓️ Roadmap e Cronograma

### MVP (8 semanas)

| Semana | Milestone | Entregáveis |
|--------|-----------|-------------|
| 1-2 | Setup + Auth | Infra, autenticação, multi-tenant |
| 3-4 | Empresas + Certificados | CRUD empresas, upload A1, criptografia |
| 5-6 | Conector SEFAZ + Sync | DF-e NSU, sync manual/agendado |
| 7 | Consulta + Download | Filtros, listagem, XML/PDF, ZIP |
| 8 | UI + Testes | PrimeVue, logs, E2E |

### V1 (+12 semanas)

| Semana | Milestone | Entregáveis |
|--------|-----------|-------------|
| 9-10 | NFS-e | Conectores municipais (5 cidades) |
| 11-12 | Certificado A3 | FiscalMix Bridge (Electron) |
| 13-14 | RBAC | Permissões por empresa |
| 15-16 | Notificações + Relatórios | Webhooks, dashboards |
| 17-18 | Billing | Stripe, upgrade/downgrade |
| 19-20 | Polish + Launch | Performance, docs, marketing |

### V2 (Roadmap Futuro)
- Integrações contábeis
- OCR e validações fiscais
- Mobile app
- Multi-conta por usuário

---

## 📚 Documentação Completa

Este projeto inclui 4 documentos detalhados:

1. **[PRD (Product Requirements Document)](prd_fiscalmix.md)**
   - Visão do produto, personas, casos de uso
   - Requisitos funcionais detalhados (FR-01 a FR-11)
   - Requisitos não funcionais (performance, segurança, escalabilidade)
   - Escopo MVP/V1/V2

2. **[Arquitetura Técnica](architecture.md)**
   - Stack tecnológico completo
   - Diagramas de componentes e fluxos (Mermaid)
   - Modelo de dados (15+ tabelas SQL)
   - API REST (endpoints + exemplos JSON)
   - Jobs e filas (Laravel Horizon)
   - Segurança (envelope encryption, RLS, rate limiting)
   - Deployment (Docker Compose)

3. **[UX/UI Design System](ux_ui_design.md)**
   - Princípios de design (simplicidade, eficiência, feedback)
   - Identidade visual (cores, tipografia, espaçamento)
   - Componentes PrimeVue customizados
   - Wireframes textuais (8 telas principais)
   - Estados da UI (loading, empty, error)
   - Responsividade e acessibilidade (WCAG 2.1 AA)
   - Dark mode

4. **[Estratégia de Testes](testing_strategy.md)**
   - Testes unitários (PHPUnit)
   - Testes de integração (conectores, jobs, API)
   - Testes E2E (Cypress)
   - Cenários críticos (sync NSU, deduplicação, encryption, RLS)
   - Cobertura de código (> 80%)
   - CI/CD (GitHub Actions)
   - Checklist de aceitação MVP/V1

---

## 🚀 Próximos Passos

### Para Começar a Implementação

1. **Setup do Projeto**
   ```bash
   # Backend
   composer create-project laravel/laravel fiscalmix-api
   cd fiscalmix-api
   composer require aws/aws-sdk-php league/flysystem-aws-s3-v3
   
   # Frontend
   npm create vite@latest fiscalmix-ui -- --template vue-ts
   cd fiscalmix-ui
   npm install primevue primeicons pinia axios
   ```

2. **Configurar Banco de Dados**
   - Criar database PostgreSQL
   - Executar migrations (usar SQL do `architecture.md`)
   - Configurar `.env` (DB, Redis, S3, KMS)

3. **Implementar MVP (Ordem Sugerida)**
   - Semana 1-2: Autenticação + Multi-tenant
   - Semana 3-4: Empresas + Certificados A1
   - Semana 5-6: Conector SEFAZ DF-e + Sync
   - Semana 7: Consulta + Download
   - Semana 8: UI + Testes

4. **Validar com Usuários Beta**
   - Onboarding de 5-10 empresas
   - Coletar feedback
   - Iterar antes do V1

---

## 💡 Diferenciais Competitivos

1. **Arquitetura Moderna**: Laravel 11 + Vue 3 + PrimeVue (stack atual)
2. **Segurança Robusta**: Envelope encryption KMS + RLS + RBAC
3. **Escalabilidade**: Filas assíncronas, workers horizontais, S3 ilimitado
4. **UX Excepcional**: Interface limpa, filtros poderosos, feedback imediato
5. **Multi-tenant Nativo**: Isolamento completo, suporta contabilidades com centenas de CNPJs
6. **Observabilidade**: Logs estruturados, métricas Prometheus, Horizon dashboard

---

## 📞 Contato e Suporte

Para dúvidas sobre a especificação ou implementação:
- **Documentação**: Consulte os 4 documentos detalhados
- **Issues**: Abra issues no repositório para discussões técnicas
- **Roadmap**: Acompanhe o progresso no GitHub Projects

---

**Versão**: 1.0  
**Data**: Janeiro 2026  
**Status**: Especificação Completa ✅
