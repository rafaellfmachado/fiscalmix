# FiscalMix - Product Requirements Document (PRD)

## 1. Visão Geral do Produto

### 1.1 North Star

**FiscalMix** é uma plataforma SaaS B2B que revoluciona a gestão de documentos fiscais eletrônicos no Brasil, permitindo que empresas, contabilidades e grupos empresariais:

- **Centralizem** todos os documentos fiscais (NF-e, NFC-e, CT-e, MDF-e, NFS-e) em uma única plataforma
- **Automatizem** a sincronização incremental com fontes oficiais (SEFAZ, prefeituras)
- **Gerenciem** múltiplos CNPJs com controle granular de permissões
- **Simplifiquem** o uso de certificados digitais (A1 e A3)
- **Otimizem** workflows de compliance, auditoria e contabilidade

### 1.2 Problema

Atualmente, empresas e contabilidades enfrentam:

- **Fragmentação**: múltiplos portais (SEFAZ estaduais, prefeituras) com interfaces inconsistentes
- **Complexidade técnica**: certificados digitais, protocolos SOAP/REST variados
- **Trabalho manual**: downloads repetitivos, organização de arquivos
- **Falta de visibilidade**: dificuldade em rastrear status, falhas e histórico
- **Escalabilidade limitada**: contabilidades com centenas de CNPJs não conseguem operar eficientemente

### 1.3 Solução

Uma plataforma unificada que:

- **Abstrai a complexidade** dos conectores fiscais através de uma API única
- **Automatiza sincronizações** incrementais via NSU (Número Sequencial Único) ou períodos
- **Gerencia certificados** com segurança (criptografia, rotação, alertas)
- **Oferece busca avançada** com filtros poderosos e exportação em lote
- **Fornece observabilidade** completa (logs, auditoria, status em tempo real)

---

## 2. Personas e Casos de Uso

### 2.1 Personas

#### Persona 1: Gerente Financeiro (Empresa Média)
- **Objetivo**: Baixar todas as notas recebidas mensalmente para conciliação
- **Dor**: Acessa 5+ portais diferentes, processo manual leva horas
- **Ganho**: Filtro "mês atual + recebidas" → download ZIP → 5 minutos

#### Persona 2: Contador (Escritório Contábil)
- **Objetivo**: Gerenciar 80+ CNPJs de clientes diversos
- **Dor**: Certificados expirados, sincronizações falham sem aviso
- **Ganho**: Dashboard centralizado, alertas automáticos, sync agendado

#### Persona 3: Analista de Compliance (Grupo Empresarial)
- **Objetivo**: Auditar documentos de 20 filiais, gerar relatórios
- **Dor**: Sem trilha de auditoria, permissões mal definidas
- **Ganho**: RBAC por CNPJ, logs completos, exportação CSV/Excel

#### Persona 4: Desenvolvedor (Integração ERP)
- **Objetivo**: Consumir documentos via API para alimentar sistema interno
- **Dor**: APIs SEFAZ instáveis, sem webhooks
- **Ganho**: API REST padronizada, webhooks de novos documentos

### 2.2 Casos de Uso Principais

| ID | Caso de Uso | Ator | Fluxo |
|----|-------------|------|-------|
| UC-01 | Baixar NF-e do mês | Financeiro | Filtrar "NF-e + recebidas + mês atual" → Selecionar todas → Download ZIP |
| UC-02 | Buscar por chave | Contador | Colar chave de acesso → Ver detalhes → Baixar XML |
| UC-03 | Configurar sync automático | Admin | Cadastrar CNPJ → Upload certificado A1 → Agendar sync 6h |
| UC-04 | Auditar downloads | Compliance | Acessar logs → Filtrar por usuário/período → Exportar CSV |
| UC-05 | Reprocessar falhas | Operação | Ver sync runs com erro → Clicar "Reprocessar" → Monitorar progresso |
| UC-06 | Gerenciar NFS-e municipal | Contador | Configurar município/provedor → Sincronizar competência → Baixar XMLs |

---

## 3. Objetivos e Métricas de Sucesso

### 3.1 Objetivos de Negócio

1. **Adoção**: 100 empresas ativas em 6 meses (MVP)
2. **Retenção**: Churn < 5% ao mês
3. **Escalabilidade**: Suportar contas com 500+ CNPJs
4. **Receita**: ARR de R$ 500k em 12 meses

### 3.2 Métricas de Produto (North Star Metrics)

| Métrica | Definição | Meta MVP | Meta V1 |
|---------|-----------|----------|---------|
| **Time to First Sync** | Tempo do cadastro até 1º sync bem-sucedido | < 10 min | < 5 min |
| **Sync Success Rate** | % de syncs sem erro (últimos 7 dias) | > 95% | > 98% |
| **Documents Synced** | Total de documentos sincronizados/mês | 100k | 1M |
| **Active CNPJs** | CNPJs com sync ativo | 200 | 2k |
| **User Satisfaction (NPS)** | Net Promoter Score | > 50 | > 70 |

### 3.3 Métricas Técnicas

- **API Latency (p95)**: < 500ms
- **Sync Job Duration (p95)**: < 5 min para 1 mês de dados
- **Uptime**: > 99.5%
- **Certificate Expiry Alerts**: 100% enviados 30 dias antes

---

## 4. Escopo e Priorização

### 4.1 MVP (Primeiro Entregável - 8 semanas)

**Objetivo**: Validar core value proposition com NF-e/CT-e/MDF-e

#### Features Incluídas
- ✅ Multi-tenant (Account → Companies → Users)
- ✅ Cadastro de empresas (CNPJ)
- ✅ Certificado A1 (upload PFX, validação, criptografia)
- ✅ Conector SEFAZ DF-e (NF-e, CT-e, MDF-e via Distribuição NSU)
- ✅ Sincronização manual e agendada (6h/3h/1h por plano)
- ✅ Consulta com filtros básicos (período, tipo, status, emitidas/recebidas)
- ✅ Download XML individual e em lote (ZIP assíncrono)
- ✅ Geração de PDF a partir do XML (DANFE, DACTE, DAMDFE)
- ✅ Logs de sync e auditoria básica
- ✅ UI moderna (PrimeVue) com tema claro/escuro

#### Features Excluídas (para V1)
- ❌ NFS-e (complexidade de conectores municipais)
- ❌ Certificado A3 (requer agente local)
- ❌ Permissões granulares por empresa (apenas admin global)
- ❌ Webhooks e notificações avançadas
- ❌ Billing/pagamentos (estrutura stub apenas)

### 4.2 V1 (12 semanas após MVP)

**Objetivo**: Expandir para contabilidades e grupos empresariais

#### Features Adicionadas
- ✅ NFS-e (padrão nacional + 5 municípios prioritários: SP, RJ, BH, Curitiba, Porto Alegre)
- ✅ Certificado A3 via FiscalMix Bridge (agente local)
- ✅ RBAC por empresa (roles: viewer, operator, admin)
- ✅ Notificações (e-mail + webhook)
- ✅ Relatórios e dashboards (volume, status, tendências)
- ✅ Exportação CSV/Excel
- ✅ Busca full-text (razão social, chave, número)

### 4.3 V2 (Roadmap Futuro)

- Integrações contábeis (Domínio, Alterdata, Sage)
- Regras de retenção e arquivamento (LGPD)
- OCR e extração de dados (itens, impostos)
- Validações fiscais (CFOP, NCM, CST)
- Multi-conta por usuário (account switch)
- Painel de compliance (divergências, alertas)
- Mobile app (iOS/Android)

---

## 5. Requisitos Funcionais Detalhados

### FR-01: Autenticação e Gestão de Conta

#### FR-01.1: Cadastro
- **Input**: Nome, e-mail, senha (mín. 8 chars, 1 maiúscula, 1 número)
- **Validação**: E-mail único, formato válido
- **Fluxo**: Enviar e-mail de verificação → Usuário clica link → Conta ativada
- **Regras**: Conta inativa até verificação

#### FR-01.2: Login
- **Input**: E-mail, senha
- **Output**: JWT (access token 1h, refresh token 7 dias)
- **Segurança**: Rate limit 5 tentativas/min, bloqueio após 10 falhas
- **2FA (opcional)**: TOTP via app autenticador

#### FR-01.3: Recuperação de Senha
- **Fluxo**: Solicitar reset → E-mail com token (válido 1h) → Definir nova senha

---

### FR-02: Multi-tenant (Account → Companies → Users)

#### Estrutura Hierárquica
```
Account (tenant_id)
├── Users (N)
│   ├── Global Role (admin | member)
│   └── Company Permissions (per-company roles)
└── Companies (N CNPJs)
    ├── Certificates
    ├── Connector Configs
    └── Documents
```

#### Regras de Isolamento
- **Tenant Isolation**: Todas as queries filtram por `account_id`
- **Storage Isolation**: S3 paths → `s3://bucket/{account_id}/{company_id}/...`
- **URL Signing**: Downloads com assinatura temporária (1h)

---

### FR-03: Cadastro de Empresas (CNPJ)

#### Campos Obrigatórios
- CNPJ (14 dígitos, validação de dígitos verificadores)
- Razão Social
- UF

#### Campos Opcionais
- Nome Fantasia
- Inscrição Estadual
- Município (obrigatório para NFS-e)
- CNAE
- Tags/Grupos (ex: "Matriz", "Filial SP", "Cliente X")

#### Validações
- CNPJ único por account
- Consulta opcional à Receita Federal (API pública) para preencher dados

#### Status
- `active`: sincronização habilitada
- `inactive`: pausada (não consome cota)

---

### FR-04: Certificado Digital (A1 e A3)

#### FR-04.1: Certificado A1

**Upload**
- **Input**: Arquivo .pfx + senha
- **Validação**:
  - Leitura do certificado (OpenSSL)
  - Verificar validade (not_before, not_after)
  - Extrair subject (CN, CNPJ)
  - Validar cadeia (se aplicável)

**Armazenamento**
- **Criptografia**: Envelope encryption (KMS)
  - Gerar DEK (Data Encryption Key) aleatória
  - Criptografar .pfx com DEK (AES-256)
  - Criptografar DEK com KMS (AWS KMS, Google Cloud KMS)
  - Armazenar: `encrypted_blob` (pfx criptografado) + `encrypted_dek`
- **Senha**: NUNCA armazenar; usuário informa a cada operação (ou cache em memória por sessão)

**Rotação**
- Permitir upload de novo certificado
- Manter histórico (audit trail)
- Alertas: 30/15/7 dias antes da expiração

#### FR-04.2: Certificado A3

**Arquitetura**
- **FiscalMix Bridge**: Aplicação desktop (Electron ou Go) instalada localmente
- **Pareamento**:
  - Usuário instala Bridge
  - Gera token de pareamento no FiscalMix (QR code ou código)
  - Bridge conecta via WebSocket seguro (WSS + autenticação mútua)
  
**Fluxo de Assinatura**
1. FiscalMix precisa assinar XML
2. Envia XML para Bridge via WebSocket
3. Bridge acessa token A3 (via PKCS#11)
4. Retorna XML assinado
5. FiscalMix envia para SEFAZ

**Monitoramento**
- Status do Bridge: online/offline (heartbeat a cada 30s)
- Logs: indisponibilidade → alerta no painel

---

### FR-05: Conectores Fiscais (Arquitetura Pluggável)

#### Interface Abstrata `FiscalConnector`

```php
interface FiscalConnector {
    // Conectar e autenticar
    public function connect(Certificate $cert): bool;
    
    // Verificar saúde do conector
    public function healthcheck(): HealthStatus;
    
    // Sincronizar documentos
    public function sync(SyncRequest $request): SyncResult;
    // SyncRequest: { period: {from, to}, nsu_start, doc_types }
    // SyncResult: { documents[], events[], nsu_end, errors[] }
    
    // Buscar documento específico
    public function fetchDocument(string $key): Document;
    
    // Buscar eventos (cancelamento, CCe)
    public function fetchEvents(string $key): Event[];
    
    // Renderizar PDF
    public function renderPDF(string $xml): string; // base64 ou path
}
```

#### Conectores Mínimos (MVP)

**1. SEFAZ DF-e (Distribuição)**
- **Documentos**: NF-e, NFC-e, CT-e, MDF-e
- **Método**: WebService `NFeDistribuicaoDFe` (SOAP)
- **Estratégia**: Incremental por NSU
- **Endpoint**: Ambiente Nacional (AN) - `https://www1.nfe.fazenda.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx`

**Fluxo de Sync**
1. Consultar último NSU armazenado (`sync_runs.nsu_end`)
2. Chamar `nfeDistDFeInteresse` com NSU inicial
3. Processar lote (max 50 docs por chamada)
4. Salvar XMLs, eventos, atualizar NSU
5. Repetir até `ultNSU` retornado = NSU atual

**2. NFS-e (V1)**
- **Padrão Nacional**: ABRASF 2.03 (quando disponível)
- **Conectores Municipais**: Plug-ins por provedor
  - SP: ISS.net (SOAP)
  - RJ: Nota Carioca (REST)
  - BH: BHISS Digital (SOAP)
  - Curitiba: ISSCuritiba (SOAP)
  - Porto Alegre: NFSe.POA (REST)

**Estratégia de Sync**
- Por competência (mês/ano) ou período
- Sem NSU: full scan por período
- Deduplicação por `numero + serie + cnpj_prestador`

#### Logs Padronizados

Cada conector deve logar:
- `connector_name`, `operation`, `timestamp`
- `request` (sanitizado, sem senhas)
- `response_status`, `response_time`
- `errors` (código, mensagem)

---

### FR-06: Consulta e Filtros

#### Filtros Rápidos (UI)
- **Mês atual**: `issue_date >= first_day_of_month AND issue_date <= today`
- **Mês anterior**: `issue_date >= first_day_of_last_month AND issue_date < first_day_of_month`
- **Últimos 7/30/90 dias**: `issue_date >= today - N days`
- **Intervalo personalizado**: date picker

#### Filtros Avançados (Query Builder)

| Campo | Tipo | Operadores | Exemplo |
|-------|------|------------|---------|
| `doc_type` | Enum | IN | `['NFE', 'CTE']` |
| `direction` | Enum | = | `'received'` |
| `status` | Enum | IN | `['authorized', 'cancelled']` |
| `issuer_cnpj` | String | LIKE | `'12345678%'` |
| `recipient_cnpj` | String | = | `'12345678000190'` |
| `access_key` | String | = | `'35210...'` |
| `number` | Integer | BETWEEN | `[1000, 2000]` |
| `series` | Integer | = | `1` |
| `total_value` | Decimal | >= | `1000.00` |
| `uf` | String | = | `'SP'` |
| `municipio` | String | = | `'São Paulo'` |

#### Resultado (API)

```json
{
  "data": [
    {
      "id": "uuid",
      "doc_type": "NFE",
      "access_key": "35210...",
      "number": 12345,
      "series": 1,
      "issue_date": "2024-01-15T10:30:00Z",
      "issuer": {
        "cnpj": "12345678000190",
        "name": "Empresa XYZ Ltda"
      },
      "recipient": {
        "cnpj": "98765432000100",
        "name": "Minha Empresa"
      },
      "total_value": 1500.50,
      "status": "authorized",
      "has_xml": true,
      "has_pdf": true
    }
  ],
  "pagination": {
    "page": 1,
    "page_size": 50,
    "total": 1234,
    "total_pages": 25
  }
}
```

#### Ordenação
- Campos: `issue_date`, `total_value`, `number`
- Direção: `asc`, `desc`
- Default: `issue_date DESC`

#### Seleção Múltipla
- Checkbox por linha
- "Selecionar todos" (página atual ou todas as páginas com confirmação)
- Ações em lote: Download ZIP, Exportar CSV

---

### FR-07: Download e Visualização

#### FR-07.1: Download XML

**Individual**
- Endpoint: `GET /documents/{id}/xml`
- Response: `Content-Type: application/xml`, `Content-Disposition: attachment; filename="NFe35210....xml"`

**Em Lote (ZIP)**
- Endpoint: `POST /exports` com filtros
- Fluxo:
  1. Criar `export_job` (status: `pending`)
  2. Enfileirar job assíncrono
  3. Worker:
     - Buscar documentos (paginado)
     - Copiar XMLs do S3 para ZIP temporário
     - Upload ZIP final para S3
     - Atualizar `export_job` (status: `completed`, `storage_zip_path`)
  4. Notificar usuário (e-mail + UI)
  5. Endpoint: `GET /exports/{id}/download` → URL assinada (válida 1h)

**Progresso**
- Polling: `GET /exports/{id}` retorna `{ status, progress: 0-100 }`
- WebSocket (opcional V1): push de updates

#### FR-07.2: Visualização PDF

**Fonte 1: PDF da SEFAZ**
- Alguns documentos vêm com PDF (raro)
- Armazenar em `storage_pdf_path`

**Fonte 2: Geração Interna**
- Biblioteca: `nfephp-org/sped-da` (PHP) ou equivalente
- Renderizar DANFE/DACTE/DAMDFE a partir do XML
- Cache: gerar sob demanda, armazenar no S3

**Endpoint**
- `GET /documents/{id}/pdf`
- Response: `Content-Type: application/pdf`, inline ou download

#### FR-07.3: Detalhes do Documento

**Tela de Detalhes (UI)**

Abas:
1. **Geral**: emitente, destinatário, valores, datas
2. **Itens** (NF-e): tabela com produtos/serviços
3. **Impostos**: ICMS, IPI, PIS, COFINS (resumo)
4. **Eventos**: cancelamento, CCe, manifestação
5. **Histórico**: quando foi sincronizado, quem baixou

---

### FR-08: Sincronização (Manual e Automática)

#### FR-08.1: Sincronização Manual

**Trigger**
- Botão "Sincronizar Agora" por empresa
- Parâmetros: `doc_types[]`, `period` (opcional)

**Fluxo**
1. Validar certificado (validade, disponibilidade)
2. Criar `sync_run` (status: `running`)
3. Enfileirar job
4. Worker executa conector
5. Atualizar `sync_run` (status: `completed|failed`, estatísticas)

#### FR-08.2: Sincronização Agendada

**Configuração por Empresa**
- Frequência: `1h`, `3h`, `6h`, `12h`, `24h` (limitado por plano)
- Janela: `00:00-23:59` ou horário comercial `08:00-18:00`
- Tipos de documento: `['NFE', 'CTE']` (seleção múltipla)

**Scheduler (Laravel)**
- Cron job a cada 1h (ou menor intervalo)
- Buscar empresas com sync agendado
- Verificar última execução + frequência
- Enfileirar jobs

#### FR-08.3: Estratégia Incremental

**SEFAZ DF-e (NSU)**
- Sempre incremental
- Armazenar `nsu_end` em `sync_runs`
- Próximo sync inicia em `nsu_end + 1`

**NFS-e (sem NSU)**
- Por competência ou período
- Deduplicação por chave única (`external_id`)
- Atualizar documentos existentes (eventos, status)

#### FR-08.4: Deduplicação

**Chave Única**
- NF-e/CT-e/MDF-e: `access_key`
- NFS-e: `external_id` (composição: `numero + serie + cnpj_prestador + municipio`)

**Regras**
- `INSERT ... ON CONFLICT (access_key) DO UPDATE`
- Atualizar: `status`, `updated_at`, `storage_xml_path` (se mudou)
- Eventos: sempre adicionar (não duplicar por `event_type + protocol`)

#### FR-08.5: Monitor de Falhas

**Retentativas**
- Backoff exponencial: 1min, 5min, 15min, 1h
- Max retries: 5

**Dead-Letter Queue (DLQ)**
- Após 5 falhas → mover para DLQ
- Alerta para admin
- Botão "Reprocessar" (manual)

**Logs de Erro**
- Armazenar em `sync_runs.error_summary` (JSON)
- Exemplo:
```json
{
  "error_code": "CERT_EXPIRED",
  "message": "Certificado expirado em 2024-01-01",
  "connector": "sefaz_dfe",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

---

### FR-09: Logs, Auditoria e Observabilidade

#### FR-09.1: Logs de Sincronização

**Tabela: `sync_runs`**

Campos:
- `started_at`, `finished_at`, `duration`
- `docs_found`, `docs_saved`, `events_saved`
- `nsu_start`, `nsu_end`
- `status`: `running`, `completed`, `failed`
- `error_summary` (JSON)
- `logs_path` (S3, logs detalhados)

**UI**
- Tabela com filtros (empresa, status, período)
- Detalhes: timeline, erros, estatísticas

#### FR-09.2: Logs por Documento

**Tabela: `fiscal_document`**

Campos de rastreamento:
- `created_at`: quando foi capturado
- `updated_at`: última atualização
- `hash_sha256`: hash do XML (integridade)
- `storage_xml_path`: origem

**Auditoria de Downloads**
- Registrar em `audit_log`:
  - `action`: `download_xml`, `download_pdf`, `view_document`
  - `user_id`, `document_id`, `timestamp`

#### FR-09.3: Auditoria de Ações do Usuário

**Tabela: `audit_logs`**

Eventos rastreados:
- Autenticação: `login`, `logout`, `mfa_enabled`
- Empresas: `company_created`, `company_updated`, `company_deleted`
- Certificados: `certificate_uploaded`, `certificate_deleted`
- Downloads: `xml_downloaded`, `pdf_downloaded`, `export_created`
- Configurações: `connector_configured`, `sync_scheduled`

**Campos**
- `user_id`, `account_id`
- `action`, `entity_type`, `entity_id`
- `metadata` (JSON): detalhes adicionais
- `ip_address`, `user_agent`
- `created_at`

**Retenção**
- MVP: 90 dias
- V1: configurável por plano (1 ano, 3 anos)

#### FR-09.4: Painel de Status (Dashboard)

**Widgets**

1. **Saúde dos Conectores**
   - Verde: último sync OK (< 24h)
   - Amarelo: último sync OK (24-72h)
   - Vermelho: falhas ou > 72h

2. **Certificados**
   - Lista com validade
   - Alertas: expira em < 30 dias

3. **Bridge A3**
   - Status: online/offline
   - Último heartbeat

4. **Estatísticas Recentes**
   - Documentos sincronizados (7 dias)
   - Syncs bem-sucedidos vs. falhos
   - Tempo médio de sync

---

### FR-10: Notificações

#### Canais (MVP: E-mail)

**Alertas Implementados**

1. **Certificado Expirando**
   - Trigger: 30, 15, 7 dias antes
   - Destinatário: admins da empresa
   - Conteúdo: CNPJ, validade, link para renovar

2. **Sync Falhou Repetidamente**
   - Trigger: 3 falhas consecutivas
   - Conteúdo: empresa, erro, link para logs

3. **Bridge A3 Offline**
   - Trigger: sem heartbeat por 5 min
   - Conteúdo: empresa, instruções para reconectar

**Template de E-mail**
- HTML responsivo
- Botão CTA (ex: "Ver Detalhes")
- Branding FiscalMix

#### Canais Futuros (V1)

- **Webhook**: POST para URL configurada pelo usuário
- **Telegram/WhatsApp** (V2): integração via bot

---

### FR-11: Planos e Billing (Estrutura Stub no MVP)

#### Planos Propostos

| Feature | Free | Starter | Professional | Enterprise |
|---------|------|---------|--------------|------------|
| **Empresas** | 1 | 5 | 20 | Ilimitado |
| **Usuários** | 2 | 5 | 20 | Ilimitado |
| **Sync Freq.** | 6h | 3h | 1h | 30min |
| **Armazenamento** | 1 GB | 10 GB | 100 GB | Ilimitado |
| **Exportações/mês** | 10 | 100 | Ilimitado | Ilimitado |
| **Suporte** | E-mail | E-mail | Prioritário | Dedicado |
| **Preço/mês** | R$ 0 | R$ 99 | R$ 499 | Custom |

#### Implementação MVP
- Tabela `account.plan` (enum: `free`, `starter`, `professional`, `enterprise`)
- Validações: limites hard-coded
- Sem gateway de pagamento (manual)

#### V1: Billing Completo
- Integração Stripe/Pagar.me
- Upgrade/downgrade self-service
- Invoices automáticas

---

## 6. Requisitos Não Funcionais (NFR)

### NFR-01: Performance

- **Listagem**: 10k+ documentos com paginação (< 500ms p95)
- **Sync Job**: processar 1 mês de dados (< 5 min p95)
- **Export ZIP**: 1k documentos (< 2 min)
- **Índices**: `(company_id, issue_date)`, `(access_key)`, `(account_id, doc_type, status)`

### NFR-02: Disponibilidade

- **Uptime**: > 99.5% (SLA)
- **Workers**: resilientes, retries, idempotência
- **Queue**: Redis/RabbitMQ com persistência

### NFR-03: Segurança

- **Criptografia em trânsito**: HTTPS/TLS 1.3
- **Criptografia em repouso**: S3 SSE-S3, DB encryption
- **Certificados A1**: envelope encryption (KMS)
- **RBAC**: tenant isolation, permissões granulares
- **Secrets**: nunca em código, usar env vars + Vault

### NFR-04: Escalabilidade

- **Horizontal**: workers stateless, escalar por demanda
- **Vertical**: DB read replicas, cache (Redis)
- **Storage**: S3 (ilimitado)

### NFR-05: Observabilidade

- **Logs**: estruturados (JSON), centralizados (ELK/CloudWatch)
- **Métricas**: Prometheus + Grafana (latência, throughput, erros)
- **Tracing**: OpenTelemetry (opcional V1)

### NFR-06: UX

- **Feedback imediato**: skeleton loading, toasts
- **Estados vazios**: ilustrações + CTAs
- **Busca rápida**: autocomplete, debounce
- **Responsivo**: desktop-first, mobile-friendly

---

## 7. Fora do Escopo (Não Fazer no MVP)

- ❌ Integração com ERPs (V2)
- ❌ OCR de documentos (V2)
- ❌ Validações fiscais avançadas (CFOP, NCM) (V2)
- ❌ Mobile app nativo (V2)
- ❌ Multi-idioma (apenas PT-BR no MVP)
- ❌ Importação manual de XMLs (V1)
- ❌ Regras de retenção customizadas (V1)

---

## 8. Dependências e Riscos

### Dependências Externas

| Dependência | Criticidade | Mitigação |
|-------------|-------------|-----------|
| SEFAZ WebServices | Alta | Cache, retries, fallback para consulta manual |
| Certificados Digitais | Alta | Alertas, documentação clara |
| S3/Storage | Alta | Multi-region, backups |

### Riscos

| Risco | Probabilidade | Impacto | Mitigação |
|-------|---------------|---------|-----------|
| SEFAZ instável | Alta | Alto | Retries, queue, logs detalhados |
| Certificados expirados | Média | Alto | Alertas 30 dias antes, UI clara |
| Complexidade NFS-e | Alta | Médio | Começar com padrão nacional, plug-ins incrementais |
| Adoção lenta | Média | Alto | Marketing, onboarding simplificado, free tier |

---

## 9. Cronograma e Milestones

### MVP (8 semanas)

| Semana | Milestone | Entregáveis |
|--------|-----------|-------------|
| 1-2 | Setup + Auth | Infra (Laravel, DB, S3), autenticação, multi-tenant |
| 3-4 | Empresas + Certificados | CRUD empresas, upload A1, criptografia |
| 5-6 | Conector SEFAZ + Sync | DF-e NSU, sync manual/agendado, deduplicação |
| 7 | Consulta + Download | Filtros, listagem, download XML/PDF, export ZIP |
| 8 | UI + Testes | PrimeVue, logs, auditoria, testes E2E |

### V1 (+12 semanas)

| Semana | Milestone | Entregáveis |
|--------|-----------|-------------|
| 9-10 | NFS-e | Conectores municipais (5 cidades) |
| 11-12 | Certificado A3 | FiscalMix Bridge (Electron), pareamento |
| 13-14 | RBAC | Permissões por empresa, roles |
| 15-16 | Notificações + Relatórios | Webhooks, dashboards, CSV export |
| 17-18 | Billing | Stripe, upgrade/downgrade |
| 19-20 | Polish + Launch | Performance, docs, marketing |

---

## 10. Critérios de Aceitação (Definition of Done)

### MVP
- [ ] Usuário consegue cadastrar conta, verificar e-mail, fazer login
- [ ] Usuário consegue cadastrar 1+ empresas (CNPJ)
- [ ] Usuário consegue fazer upload de certificado A1 e ver validade
- [ ] Sistema sincroniza NF-e/CT-e/MDF-e via SEFAZ DF-e (NSU)
- [ ] Usuário consegue filtrar documentos por período, tipo, status
- [ ] Usuário consegue baixar XML individual e em lote (ZIP)
- [ ] Sistema gera PDF (DANFE) a partir do XML
- [ ] Logs de sync e auditoria estão visíveis na UI
- [ ] Testes E2E cobrem fluxos críticos (cadastro → sync → download)
- [ ] Documentação de API e onboarding estão completas

### V1
- [ ] NFS-e funciona para 5 municípios prioritários
- [ ] Certificado A3 via Bridge está operacional
- [ ] RBAC por empresa está implementado e testado
- [ ] Notificações (e-mail + webhook) estão funcionando
- [ ] Billing com Stripe está integrado
- [ ] Performance atende NFRs (< 500ms p95 listagem)
- [ ] Uptime > 99.5% por 30 dias consecutivos

---

## 11. Anexos

### A. Glossário

- **NSU**: Número Sequencial Único (identificador incremental de documentos na SEFAZ)
- **DF-e**: Documento Fiscal Eletrônico
- **DANFE**: Documento Auxiliar da Nota Fiscal Eletrônica
- **CCe**: Carta de Correção Eletrônica
- **A1/A3**: Tipos de certificado digital (A1 = arquivo, A3 = token/cartão)
- **RBAC**: Role-Based Access Control

### B. Referências

- [Portal NF-e](http://www.nfe.fazenda.gov.br/)
- [Manual de Integração DF-e](http://www.nfe.fazenda.gov.br/portal/listaConteudo.aspx?tipoConteudo=tW+YMyk/50s=)
- [ABRASF - Padrão NFS-e](https://www.abrasf.org.br/arquivos/downloads/)
- [nfephp-org (biblioteca PHP)](https://github.com/nfephp-org)
