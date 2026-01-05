# FiscalMix - Arquitetura Técnica Detalhada

## 1. Stack Tecnológico

### Backend
- **Framework**: Laravel 11 (PHP 8.3)
- **Database**: PostgreSQL 16
- **Cache/Queue**: Redis 7
- **Storage**: AWS S3 (ou MinIO para dev)
- **Jobs**: Laravel Horizon (Redis-based queue)
- **Criptografia**: AWS KMS / Laravel Encryption

### Frontend
- **Framework**: Vue 3 (Composition API + TypeScript)
- **UI Library**: PrimeVue 3.x
- **Build**: Vite 5
- **State**: Pinia
- **HTTP**: Axios
- **Router**: Vue Router 4

### Infraestrutura
- **Containers**: Docker + Docker Compose
- **Reverse Proxy**: Nginx
- **Monitoring**: Prometheus + Grafana
- **Logs**: Laravel Log (JSON) → ELK Stack
- **CI/CD**: GitHub Actions

---

## 2. Diagrama de Componentes

```mermaid
graph TB
    subgraph "Frontend (Vue + PrimeVue)"
        UI[UI Components]
        Store[Pinia Store]
        Router[Vue Router]
    end

    subgraph "API Gateway (Laravel)"
        Auth[Auth Middleware]
        Routes[API Routes]
        Controllers[Controllers]
    end

    subgraph "Application Layer"
        Services[Services]
        Repositories[Repositories]
        Jobs[Queue Jobs]
    end

    subgraph "Domain Layer"
        Connectors[Fiscal Connectors]
        PDF[PDF Generator]
        Crypto[Certificate Manager]
    end

    subgraph "Infrastructure"
        DB[(PostgreSQL)]
        Cache[(Redis)]
        Queue[Redis Queue]
        S3[S3 Storage]
        KMS[AWS KMS]
    end

    subgraph "External"
        SEFAZ[SEFAZ WebServices]
        Prefeituras[Prefeituras APIs]
        Bridge[FiscalMix Bridge A3]
    end

    UI --> Router
    Router --> Store
    Store --> Auth
    Auth --> Routes
    Routes --> Controllers
    Controllers --> Services
    Services --> Repositories
    Services --> Jobs
    Services --> Connectors
    Services --> PDF
    Services --> Crypto
    Repositories --> DB
    Jobs --> Queue
    Queue --> Jobs
    Connectors --> SEFAZ
    Connectors --> Prefeituras
    Crypto --> KMS
    Services --> S3
    Services --> Cache
    Crypto --> Bridge
```

---

## 3. Fluxos de Dados Detalhados

### 3.1 Fluxo: Cadastro de Empresa + Upload Certificado A1

```mermaid
sequenceDiagram
    actor User
    participant UI as Frontend
    participant API as Laravel API
    participant Service as CompanyService
    participant Cert as CertificateService
    participant KMS as AWS KMS
    participant DB as PostgreSQL
    participant S3 as S3 Storage

    User->>UI: Preenche formulário (CNPJ, Razão Social)
    UI->>API: POST /companies {cnpj, razao_social, uf}
    API->>Service: createCompany(data)
    Service->>DB: INSERT INTO companies
    DB-->>Service: company_id
    Service-->>API: Company created
    API-->>UI: 201 Created {company}

    User->>UI: Upload certificado.pfx + senha
    UI->>API: POST /companies/{id}/certificates/a1<br/>{file, password}
    API->>Cert: uploadA1Certificate(company, file, password)
    
    Cert->>Cert: Validar PFX (OpenSSL)<br/>- Verificar validade<br/>- Extrair subject (CNPJ, CN)<br/>- Validar cadeia
    
    alt Certificado inválido
        Cert-->>API: ValidationException
        API-->>UI: 422 Unprocessable Entity
    end

    Cert->>Cert: Gerar DEK (Data Encryption Key)
    Cert->>Cert: Criptografar PFX com DEK (AES-256)
    Cert->>KMS: Encrypt DEK
    KMS-->>Cert: encrypted_dek
    
    Cert->>DB: INSERT INTO certificates<br/>{encrypted_blob, encrypted_dek, fingerprint, valid_from, valid_to}
    Cert->>S3: (Opcional) Backup encrypted_blob
    
    Cert-->>API: Certificate created
    API-->>UI: 201 Created {certificate}
    UI->>User: "Certificado configurado com sucesso!"
```

---

### 3.2 Fluxo: Sincronização SEFAZ DF-e (NSU Incremental)

```mermaid
sequenceDiagram
    actor User
    participant UI as Frontend
    participant API as Laravel API
    participant Job as SyncDFeJob
    participant Connector as SefazDFeConnector
    participant SEFAZ as SEFAZ WebService
    participant DB as PostgreSQL
    participant S3 as S3 Storage
    participant Queue as Redis Queue

    User->>UI: Clica "Sincronizar Agora"
    UI->>API: POST /companies/{id}/sync<br/>{doc_types: ['NFE', 'CTE']}
    
    API->>DB: INSERT INTO sync_runs<br/>{company_id, status: 'pending'}
    DB-->>API: sync_run_id
    
    API->>Queue: Dispatch SyncDFeJob(company_id, sync_run_id)
    API-->>UI: 202 Accepted {sync_run_id}
    UI->>User: "Sincronização iniciada..."

    Queue->>Job: Execute SyncDFeJob
    Job->>DB: SELECT last nsu_end FROM sync_runs
    DB-->>Job: nsu_start = 123456
    
    Job->>DB: SELECT certificate FROM certificates
    Job->>Connector: connect(certificate)
    
    loop Até ultNSU alcançado
        Connector->>SEFAZ: nfeDistDFeInteresse<br/>(CNPJ, NSU, maxNSU=50)
        SEFAZ-->>Connector: {documents[], events[], ultNSU}
        
        loop Para cada documento
            Connector->>S3: Upload XML<br/>s3://bucket/{account_id}/{company_id}/xml/{access_key}.xml
            Connector->>DB: INSERT INTO fiscal_documents<br/>ON CONFLICT (access_key) DO UPDATE
        end
        
        loop Para cada evento
            Connector->>S3: Upload evento XML
            Connector->>DB: INSERT INTO fiscal_events
        end
        
        Job->>DB: UPDATE sync_runs SET nsu_end = ultNSU
    end
    
    Job->>DB: UPDATE sync_runs<br/>{status: 'completed', finished_at, docs_found, docs_saved}
    
    Job-->>UI: (WebSocket/Polling) Sync completed
    UI->>User: "Sincronização concluída! 150 documentos processados"
```

---

### 3.3 Fluxo: Exportação ZIP em Lote

```mermaid
sequenceDiagram
    actor User
    participant UI as Frontend
    participant API as Laravel API
    participant Job as ExportZipJob
    participant DB as PostgreSQL
    participant S3 as S3 Storage
    participant Queue as Redis Queue

    User->>UI: Seleciona filtros + clica "Exportar ZIP"
    UI->>API: POST /exports<br/>{filters: {company_id, doc_type, from, to}}
    
    API->>DB: INSERT INTO export_jobs<br/>{account_id, filters, status: 'pending'}
    DB-->>API: export_id
    
    API->>Queue: Dispatch ExportZipJob(export_id)
    API-->>UI: 202 Accepted {export_id}
    
    UI->>User: "Exportação iniciada..."
    
    loop Polling a cada 3s
        UI->>API: GET /exports/{export_id}
        API->>DB: SELECT status, progress FROM export_jobs
        DB-->>API: {status: 'processing', progress: 45}
        API-->>UI: {status, progress}
        UI->>User: Progress bar 45%
    end

    Queue->>Job: Execute ExportZipJob
    Job->>DB: SELECT COUNT(*) FROM fiscal_documents WHERE filters
    DB-->>Job: total = 1000
    
    Job->>Job: Criar ZIP temporário (/tmp/export_{id}.zip)
    
    loop Paginação (100 docs por vez)
        Job->>DB: SELECT storage_xml_path FROM fiscal_documents<br/>LIMIT 100 OFFSET {offset}
        DB-->>Job: [paths]
        
        loop Para cada path
            Job->>S3: Download XML
            S3-->>Job: xml_content
            Job->>Job: Adicionar ao ZIP<br/>(nome: {access_key}.xml)
        end
        
        Job->>DB: UPDATE export_jobs SET progress = {offset/total * 100}
    end
    
    Job->>S3: Upload ZIP final<br/>s3://bucket/{account_id}/exports/{export_id}.zip
    Job->>Job: Deletar ZIP temporário
    
    Job->>DB: UPDATE export_jobs<br/>{status: 'completed', storage_zip_path, finished_at}
    
    Job->>API: (Opcional) Enviar e-mail com link
    
    UI->>API: GET /exports/{export_id}
    API-->>UI: {status: 'completed', download_url}
    UI->>User: "Exportação concluída! Baixar ZIP"
    
    User->>UI: Clica "Baixar"
    UI->>API: GET /exports/{export_id}/download
    API->>S3: Generate presigned URL (1h)
    S3-->>API: signed_url
    API-->>UI: Redirect to signed_url
    UI->>S3: Download ZIP
```

---

### 3.4 Fluxo: Certificado A3 via FiscalMix Bridge

```mermaid
sequenceDiagram
    actor User
    participant UI as Frontend
    participant API as Laravel API
    participant Bridge as FiscalMix Bridge (Local)
    participant Token as Token A3 (USB/Cartão)
    participant DB as PostgreSQL

    User->>Bridge: Instala e inicia Bridge
    Bridge->>Bridge: Gera pairing_token (UUID)
    Bridge->>UI: Exibe QR Code com pairing_token

    User->>UI: Escaneia QR Code
    UI->>API: POST /companies/{id}/certificates/a3/pair<br/>{pairing_token}
    API->>DB: INSERT INTO certificates<br/>{type: 'A3', pairing_token, status: 'pending'}
    API-->>UI: 201 Created

    Bridge->>API: WebSocket connect (WSS)<br/>Authorization: Bearer {pairing_token}
    API->>DB: Validate pairing_token
    DB-->>API: certificate_id
    API->>Bridge: Connection accepted
    
    Bridge->>Token: Detectar certificado (PKCS#11)
    Token-->>Bridge: {subject, issuer, valid_to}
    
    Bridge->>API: WS Message: certificate_info<br/>{subject, issuer, valid_to}
    API->>DB: UPDATE certificates<br/>{metadata: {subject, issuer}, valid_to, status: 'active'}
    API-->>UI: (Push) Certificate paired
    UI->>User: "Certificado A3 conectado!"

    Note over Bridge,API: Heartbeat a cada 30s
    loop Heartbeat
        Bridge->>API: WS Message: heartbeat
        API->>DB: UPDATE certificates SET last_heartbeat = NOW()
    end

    Note over API,Token: Quando precisar assinar XML
    API->>Bridge: WS Message: sign_xml<br/>{xml_content, request_id}
    Bridge->>Token: Assinar XML (PKCS#11)
    Token-->>Bridge: signed_xml
    Bridge->>API: WS Message: signed_xml<br/>{request_id, signed_xml}
    API->>API: Enviar para SEFAZ
```

---

## 4. Modelo de Dados (PostgreSQL)

### 4.1 Diagrama ER

```mermaid
erDiagram
    accounts ||--o{ users : has
    accounts ||--o{ companies : has
    accounts ||--o{ audit_logs : generates
    accounts ||--o{ export_jobs : creates
    
    companies ||--o{ certificates : has
    companies ||--o{ connector_configs : has
    companies ||--o{ fiscal_documents : owns
    companies ||--o{ sync_runs : executes
    
    users ||--o{ company_user_permissions : has
    users ||--o{ audit_logs : performs
    
    fiscal_documents ||--o{ fiscal_events : has
    
    sync_runs ||--o{ fiscal_documents : syncs

    accounts {
        uuid id PK
        string name
        enum plan
        enum status
        timestamp created_at
    }

    users {
        uuid id PK
        uuid account_id FK
        string name
        string email UK
        string password_hash
        boolean mfa_enabled
        string mfa_secret
        timestamp created_at
    }

    companies {
        uuid id PK
        uuid account_id FK
        string cnpj UK
        string razao_social
        string nome_fantasia
        string ie
        string uf
        string municipio
        jsonb tags
        enum status
        timestamp created_at
    }

    certificates {
        uuid id PK
        uuid company_id FK
        enum type
        text encrypted_blob
        text encrypted_dek
        string fingerprint
        jsonb metadata
        timestamp valid_from
        timestamp valid_to
        timestamp last_heartbeat
        enum status
        timestamp created_at
    }

    connector_configs {
        uuid id PK
        uuid company_id FK
        enum doc_type
        jsonb config
        enum status
        timestamp created_at
    }

    fiscal_documents {
        uuid id PK
        uuid account_id FK
        uuid company_id FK
        enum doc_type
        enum direction
        string access_key UK
        string external_id UK
        integer number
        integer series
        timestamp issue_date
        date competence
        string issuer_cnpj_cpf
        string issuer_name
        string recipient_cnpj_cpf
        string recipient_name
        decimal total_value
        enum status
        string storage_xml_path
        string storage_pdf_path
        string hash_sha256
        timestamp created_at
        timestamp updated_at
    }

    fiscal_events {
        uuid id PK
        uuid fiscal_document_id FK
        enum event_type
        timestamp event_date
        string protocol
        string storage_xml_path
        timestamp created_at
    }

    sync_runs {
        uuid id PK
        uuid company_id FK
        enum doc_type
        timestamp started_at
        timestamp finished_at
        enum status
        bigint nsu_start
        bigint nsu_end
        integer docs_found
        integer docs_saved
        integer events_saved
        jsonb error_summary
        string logs_path
    }

    company_user_permissions {
        uuid id PK
        uuid company_id FK
        uuid user_id FK
        enum role
        jsonb permissions
        timestamp created_at
    }

    audit_logs {
        uuid id PK
        uuid account_id FK
        uuid user_id FK
        string action
        string entity_type
        uuid entity_id
        jsonb metadata
        string ip_address
        string user_agent
        timestamp created_at
    }

    export_jobs {
        uuid id PK
        uuid account_id FK
        uuid company_id FK
        uuid user_id FK
        jsonb filters
        enum status
        integer progress
        string storage_zip_path
        timestamp created_at
        timestamp finished_at
    }
```

### 4.2 Tabelas Detalhadas (SQL)

```sql
-- accounts
CREATE TABLE accounts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    plan VARCHAR(50) NOT NULL DEFAULT 'free' CHECK (plan IN ('free', 'starter', 'professional', 'enterprise')),
    status VARCHAR(50) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'suspended', 'cancelled')),
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_accounts_status ON accounts(status);

-- users
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    account_id UUID NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    mfa_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    mfa_secret VARCHAR(255),
    email_verified_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_users_account_id ON users(account_id);
CREATE INDEX idx_users_email ON users(email);

-- companies
CREATE TABLE companies (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    account_id UUID NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
    cnpj VARCHAR(14) NOT NULL,
    razao_social VARCHAR(255) NOT NULL,
    nome_fantasia VARCHAR(255),
    ie VARCHAR(50),
    uf CHAR(2) NOT NULL,
    municipio VARCHAR(100),
    tags JSONB DEFAULT '[]',
    status VARCHAR(50) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(account_id, cnpj)
);

CREATE INDEX idx_companies_account_id ON companies(account_id);
CREATE INDEX idx_companies_cnpj ON companies(cnpj);
CREATE INDEX idx_companies_status ON companies(status);

-- certificates
CREATE TABLE certificates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    type VARCHAR(10) NOT NULL CHECK (type IN ('A1', 'A3')),
    encrypted_blob TEXT, -- A1 only
    encrypted_dek TEXT, -- A1 only
    fingerprint VARCHAR(255),
    pairing_token VARCHAR(255), -- A3 only
    metadata JSONB DEFAULT '{}', -- {subject, issuer, etc}
    valid_from TIMESTAMP NOT NULL,
    valid_to TIMESTAMP NOT NULL,
    last_heartbeat TIMESTAMP, -- A3 only
    status VARCHAR(50) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'expired', 'revoked', 'pending')),
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_certificates_company_id ON certificates(company_id);
CREATE INDEX idx_certificates_valid_to ON certificates(valid_to);
CREATE INDEX idx_certificates_status ON certificates(status);

-- connector_configs
CREATE TABLE connector_configs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    doc_type VARCHAR(10) NOT NULL CHECK (doc_type IN ('NFE', 'NFCE', 'CTE', 'MDFE', 'NFSE')),
    config JSONB NOT NULL DEFAULT '{}', -- {municipio, provedor, etc}
    status VARCHAR(50) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(company_id, doc_type)
);

CREATE INDEX idx_connector_configs_company_id ON connector_configs(company_id);

-- fiscal_documents
CREATE TABLE fiscal_documents (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    account_id UUID NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    doc_type VARCHAR(10) NOT NULL CHECK (doc_type IN ('NFE', 'NFCE', 'CTE', 'MDFE', 'NFSE')),
    direction VARCHAR(10) NOT NULL CHECK (direction IN ('issued', 'received')),
    access_key VARCHAR(44), -- NF-e/CT-e/MDF-e
    external_id VARCHAR(255), -- NFS-e ou outros
    number INTEGER NOT NULL,
    series INTEGER,
    issue_date TIMESTAMP NOT NULL,
    competence DATE, -- NFS-e
    issuer_cnpj_cpf VARCHAR(14) NOT NULL,
    issuer_name VARCHAR(255),
    recipient_cnpj_cpf VARCHAR(14),
    recipient_name VARCHAR(255),
    total_value DECIMAL(15,2) NOT NULL,
    status VARCHAR(50) NOT NULL, -- authorized, cancelled, denied, etc
    storage_xml_path VARCHAR(500) NOT NULL,
    storage_pdf_path VARCHAR(500),
    hash_sha256 VARCHAR(64) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(access_key),
    UNIQUE(account_id, external_id, doc_type)
);

CREATE INDEX idx_fiscal_documents_account_company ON fiscal_documents(account_id, company_id);
CREATE INDEX idx_fiscal_documents_issue_date ON fiscal_documents(issue_date DESC);
CREATE INDEX idx_fiscal_documents_doc_type_status ON fiscal_documents(doc_type, status);
CREATE INDEX idx_fiscal_documents_direction ON fiscal_documents(direction);
CREATE INDEX idx_fiscal_documents_issuer ON fiscal_documents(issuer_cnpj_cpf);
CREATE INDEX idx_fiscal_documents_recipient ON fiscal_documents(recipient_cnpj_cpf);
CREATE INDEX idx_fiscal_documents_access_key ON fiscal_documents(access_key);
CREATE INDEX idx_fiscal_documents_external_id ON fiscal_documents(external_id);

-- fiscal_events
CREATE TABLE fiscal_events (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    fiscal_document_id UUID NOT NULL REFERENCES fiscal_documents(id) ON DELETE CASCADE,
    event_type VARCHAR(50) NOT NULL, -- cancelamento, cce, manifestacao, etc
    event_date TIMESTAMP NOT NULL,
    protocol VARCHAR(50),
    storage_xml_path VARCHAR(500),
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_fiscal_events_document_id ON fiscal_events(fiscal_document_id);
CREATE INDEX idx_fiscal_events_type ON fiscal_events(event_type);

-- sync_runs
CREATE TABLE sync_runs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    doc_type VARCHAR(10) NOT NULL,
    started_at TIMESTAMP NOT NULL DEFAULT NOW(),
    finished_at TIMESTAMP,
    status VARCHAR(50) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'running', 'completed', 'failed')),
    nsu_start BIGINT,
    nsu_end BIGINT,
    docs_found INTEGER DEFAULT 0,
    docs_saved INTEGER DEFAULT 0,
    events_saved INTEGER DEFAULT 0,
    error_summary JSONB,
    logs_path VARCHAR(500),
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_sync_runs_company_id ON sync_runs(company_id);
CREATE INDEX idx_sync_runs_status ON sync_runs(status);
CREATE INDEX idx_sync_runs_started_at ON sync_runs(started_at DESC);

-- company_user_permissions
CREATE TABLE company_user_permissions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(50) NOT NULL CHECK (role IN ('viewer', 'operator', 'admin')),
    permissions JSONB DEFAULT '{}',
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(company_id, user_id)
);

CREATE INDEX idx_company_user_permissions_company ON company_user_permissions(company_id);
CREATE INDEX idx_company_user_permissions_user ON company_user_permissions(user_id);

-- audit_logs
CREATE TABLE audit_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    account_id UUID NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id UUID,
    metadata JSONB DEFAULT '{}',
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_audit_logs_account_id ON audit_logs(account_id);
CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_action ON audit_logs(action);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at DESC);

-- export_jobs
CREATE TABLE export_jobs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    account_id UUID NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
    company_id UUID REFERENCES companies(id) ON DELETE SET NULL,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    filters JSONB NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
    progress INTEGER DEFAULT 0 CHECK (progress >= 0 AND progress <= 100),
    storage_zip_path VARCHAR(500),
    error_message TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    finished_at TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_export_jobs_account_id ON export_jobs(account_id);
CREATE INDEX idx_export_jobs_user_id ON export_jobs(user_id);
CREATE INDEX idx_export_jobs_status ON export_jobs(status);
CREATE INDEX idx_export_jobs_created_at ON export_jobs(created_at DESC);
```

---

## 5. Segurança e Deployment

Consulte o PRD para detalhes completos de segurança (envelope encryption, RLS, rate limiting) e configuração de deployment (Docker Compose, CI/CD).

---

## 6. Próximos Passos

1. **Implementar MVP** seguindo esta arquitetura
2. **Testes**: Unit (Services), Integration (Connectors), E2E (Cypress)
3. **CI/CD**: GitHub Actions para deploy automático
4. **Monitoramento**: Configurar Prometheus + Grafana
5. **Documentação**: OpenAPI/Swagger para API
