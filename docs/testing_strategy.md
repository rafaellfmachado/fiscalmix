# FiscalMix - Estratégia de Testes e Checklist

## 1. Pirâmide de Testes

```
        /\
       /E2E\       (10% - Fluxos críticos completos)
      /──────\
     /Integr.\    (30% - APIs, Conectores, Jobs)
    /──────────\
   /   Unit     \  (60% - Services, Repositories, Helpers)
  /──────────────\
```

---

## 2. Testes Unitários (PHPUnit)

### 2.1 Services

#### CertificateServiceTest
```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CertificateService;
use App\Services\CertificateEncryptionService;
use App\Models\Company;
use Illuminate\Http\UploadedFile;

class CertificateServiceTest extends TestCase
{
    public function test_upload_a1_certificate_success()
    {
        $company = Company::factory()->create();
        $pfxFile = UploadedFile::fake()->create('cert.pfx', 10);
        $password = 'senha123';

        $service = app(CertificateService::class);
        $certificate = $service->uploadA1Certificate($company, $pfxFile, $password);

        $this->assertNotNull($certificate->encrypted_blob);
        $this->assertNotNull($certificate->encrypted_dek);
        $this->assertEquals('A1', $certificate->type);
        $this->assertEquals('active', $certificate->status);
    }

    public function test_upload_a1_certificate_invalid_pfx()
    {
        $this->expectException(\InvalidArgumentException::class);

        $company = Company::factory()->create();
        $invalidFile = UploadedFile::fake()->create('invalid.txt', 10);

        $service = app(CertificateService::class);
        $service->uploadA1Certificate($company, $invalidFile, 'senha123');
    }

    public function test_upload_a1_certificate_expired()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Certificado expirado');

        // Mock de certificado expirado
        // ...
    }
}
```

#### SyncServiceTest
```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SyncService;
use App\Models\Company;
use App\Models\SyncRun;

class SyncServiceTest extends TestCase
{
    public function test_get_last_nsu_returns_zero_when_no_previous_sync()
    {
        $company = Company::factory()->create();
        $service = app(SyncService::class);

        $lastNsu = $service->getLastNsu($company, 'NFE');

        $this->assertEquals(0, $lastNsu);
    }

    public function test_get_last_nsu_returns_previous_nsu_end()
    {
        $company = Company::factory()->create();
        SyncRun::factory()->create([
            'company_id' => $company->id,
            'doc_type' => 'NFE',
            'nsu_end' => 123456,
            'status' => 'completed',
        ]);

        $service = app(SyncService::class);
        $lastNsu = $service->getLastNsu($company, 'NFE');

        $this->assertEquals(123456, $lastNsu);
    }
}
```

---

### 2.2 Repositories

#### FiscalDocumentRepositoryTest
```php
<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\FiscalDocumentRepository;
use App\Models\FiscalDocument;
use App\Models\Company;

class FiscalDocumentRepositoryTest extends TestCase
{
    public function test_find_by_access_key()
    {
        $doc = FiscalDocument::factory()->create([
            'access_key' => '35240112345678000190550010000123451000123456',
        ]);

        $repo = app(FiscalDocumentRepository::class);
        $found = $repo->findByAccessKey($doc->access_key);

        $this->assertEquals($doc->id, $found->id);
    }

    public function test_deduplication_on_conflict()
    {
        $company = Company::factory()->create();
        $accessKey = '35240112345678000190550010000123451000123456';

        $repo = app(FiscalDocumentRepository::class);

        // Primeira inserção
        $doc1 = $repo->upsert([
            'company_id' => $company->id,
            'access_key' => $accessKey,
            'number' => 12345,
            'total_value' => 1000.00,
            'status' => 'authorized',
        ]);

        // Segunda inserção (mesmo access_key)
        $doc2 = $repo->upsert([
            'company_id' => $company->id,
            'access_key' => $accessKey,
            'number' => 12345,
            'total_value' => 1500.00, // Valor atualizado
            'status' => 'cancelled',
        ]);

        $this->assertEquals($doc1->id, $doc2->id);
        $this->assertEquals(1500.00, $doc2->total_value);
        $this->assertEquals('cancelled', $doc2->status);
    }
}
```

---

### 2.3 Helpers

#### CnpjValidatorTest
```php
<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Helpers\CnpjValidator;

class CnpjValidatorTest extends TestCase
{
    public function test_valid_cnpj()
    {
        $this->assertTrue(CnpjValidator::validate('12.345.678/0001-90'));
        $this->assertTrue(CnpjValidator::validate('12345678000190'));
    }

    public function test_invalid_cnpj()
    {
        $this->assertFalse(CnpjValidator::validate('12.345.678/0001-99'));
        $this->assertFalse(CnpjValidator::validate('00000000000000'));
        $this->assertFalse(CnpjValidator::validate('123'));
    }
}
```

---

## 3. Testes de Integração

### 3.1 Conectores (Mock SEFAZ)

#### SefazDFeConnectorTest
```php
<?php

namespace Tests\Integration\Connectors;

use Tests\TestCase;
use App\Services\Connectors\SefazDFeConnector;
use App\Models\Company;
use App\Models\Certificate;
use Illuminate\Support\Facades\Http;

class SefazDFeConnectorTest extends TestCase
{
    public function test_sync_fetches_documents_from_sefaz()
    {
        // Mock da resposta SEFAZ
        Http::fake([
            'https://www1.nfe.fazenda.gov.br/*' => Http::response([
                'ultNSU' => 123606,
                'documents' => [
                    ['access_key' => '35240112345678000190550010000123451000123456', 'xml' => '...'],
                    ['access_key' => '35240112345678000190550010000123461000123457', 'xml' => '...'],
                ],
            ], 200),
        ]);

        $company = Company::factory()->create();
        $certificate = Certificate::factory()->create(['company_id' => $company->id]);

        $connector = app(SefazDFeConnector::class);
        $result = $connector->sync($company, 123456, ['NFE']);

        $this->assertEquals(123606, $result->ultNsu);
        $this->assertCount(2, $result->documents);
    }

    public function test_sync_handles_sefaz_error()
    {
        Http::fake([
            'https://www1.nfe.fazenda.gov.br/*' => Http::response(['error' => 'Certificado inválido'], 401),
        ]);

        $this->expectException(\RuntimeException::class);

        $company = Company::factory()->create();
        $connector = app(SefazDFeConnector::class);
        $connector->sync($company, 0, ['NFE']);
    }
}
```

---

### 3.2 Jobs (Redis Queue)

#### SyncDFeJobTest
```php
<?php

namespace Tests\Integration\Jobs;

use Tests\TestCase;
use App\Jobs\SyncDFeJob;
use App\Models\Company;
use App\Models\SyncRun;
use Illuminate\Support\Facades\Queue;

class SyncDFeJobTest extends TestCase
{
    public function test_job_is_dispatched_to_queue()
    {
        Queue::fake();

        $company = Company::factory()->create();
        $syncRun = SyncRun::factory()->create(['company_id' => $company->id]);

        SyncDFeJob::dispatch($company, $syncRun);

        Queue::assertPushed(SyncDFeJob::class);
    }

    public function test_job_updates_sync_run_on_success()
    {
        $company = Company::factory()->create();
        $syncRun = SyncRun::factory()->create([
            'company_id' => $company->id,
            'status' => 'pending',
        ]);

        // Mock conector
        // ...

        $job = new SyncDFeJob($company, $syncRun);
        $job->handle(app(\App\Services\Connectors\SefazDFeConnector::class));

        $syncRun->refresh();
        $this->assertEquals('completed', $syncRun->status);
        $this->assertNotNull($syncRun->finished_at);
    }
}
```

---

### 3.3 API Endpoints

#### DocumentsApiTest
```php
<?php

namespace Tests\Integration\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\FiscalDocument;

class DocumentsApiTest extends TestCase
{
    public function test_list_documents_requires_authentication()
    {
        $response = $this->getJson('/api/documents');
        $response->assertStatus(401);
    }

    public function test_list_documents_returns_paginated_results()
    {
        $user = User::factory()->create();
        FiscalDocument::factory()->count(100)->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->getJson('/api/documents?page=1&page_size=50');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'doc_type', 'access_key', 'total_value'],
            ],
            'pagination' => ['page', 'page_size', 'total', 'total_pages'],
        ]);
        $response->assertJsonCount(50, 'data');
    }

    public function test_list_documents_filters_by_company()
    {
        $user = User::factory()->create();
        $company1 = Company::factory()->create(['account_id' => $user->account_id]);
        $company2 = Company::factory()->create(['account_id' => $user->account_id]);

        FiscalDocument::factory()->count(10)->create(['company_id' => $company1->id]);
        FiscalDocument::factory()->count(5)->create(['company_id' => $company2->id]);

        $response = $this->actingAs($user)->getJson("/api/documents?company_id={$company1->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
    }
}
```

---

## 4. Testes E2E (Cypress)

### 4.1 Fluxo Completo: Cadastro → Sync → Download

```javascript
// cypress/e2e/complete_flow.cy.js

describe('Fluxo Completo: Cadastro → Sync → Download', () => {
  beforeEach(() => {
    cy.login('admin@empresa.com', 'senha123');
  });

  it('deve cadastrar empresa, configurar certificado, sincronizar e baixar documentos', () => {
    // 1. Cadastrar empresa
    cy.visit('/empresas');
    cy.get('[data-cy=btn-nova-empresa]').click();
    cy.get('[data-cy=input-cnpj]').type('12345678000190');
    cy.get('[data-cy=input-razao-social]').type('Empresa XYZ Ltda');
    cy.get('[data-cy=select-uf]').select('SP');
    cy.get('[data-cy=btn-salvar]').click();
    cy.contains('Empresa cadastrada com sucesso!').should('be.visible');

    // 2. Upload certificado A1
    cy.get('[data-cy=tab-certificados]').click();
    cy.get('[data-cy=input-certificado]').attachFile('certificado.pfx');
    cy.get('[data-cy=input-senha]').type('senha123');
    cy.get('[data-cy=btn-upload]').click();
    cy.contains('Certificado configurado com sucesso!').should('be.visible');

    // 3. Sincronizar
    cy.get('[data-cy=tab-conectores]').click();
    cy.get('[data-cy=btn-sincronizar-nfe]').click();
    cy.contains('Sincronização iniciada').should('be.visible');

    // Aguardar conclusão (polling)
    cy.wait(5000);
    cy.contains('Sincronização concluída').should('be.visible');

    // 4. Listar documentos
    cy.visit('/documentos');
    cy.get('[data-cy=document-row]').should('have.length.greaterThan', 0);

    // 5. Baixar XML
    cy.get('[data-cy=document-row]').first().within(() => {
      cy.get('[data-cy=btn-download-xml]').click();
    });

    // Verificar download
    cy.readFile('cypress/downloads/NFe35240112345678000190550010000123451000123456.xml').should('exist');
  });
});
```

---

### 4.2 Fluxo: Exportação ZIP

```javascript
// cypress/e2e/export_zip.cy.js

describe('Exportação ZIP', () => {
  beforeEach(() => {
    cy.login('admin@empresa.com', 'senha123');
    cy.seedDocuments(100); // Helper para criar 100 docs
  });

  it('deve exportar documentos em ZIP', () => {
    cy.visit('/documentos');

    // Selecionar todos
    cy.get('[data-cy=checkbox-select-all]').check();

    // Exportar
    cy.get('[data-cy=btn-exportar-zip]').click();
    cy.contains('Exportação iniciada').should('be.visible');

    // Aguardar progresso
    cy.get('[data-cy=progress-bar]', { timeout: 30000 }).should('have.attr', 'aria-valuenow', '100');

    // Baixar ZIP
    cy.get('[data-cy=btn-download-zip]').click();
    cy.readFile('cypress/downloads/export_*.zip').should('exist');
  });
});
```

---

## 5. Cenários de Teste Críticos

### 5.1 Sincronização Incremental (NSU)

**Cenário**: Sync incremental não deve duplicar documentos

**Passos**:
1. Executar sync inicial (NSU 0 → 100)
2. Verificar que 100 documentos foram salvos
3. Executar sync incremental (NSU 100 → 150)
4. Verificar que apenas 50 novos documentos foram salvos
5. Total de documentos = 150 (sem duplicatas)

**Asserções**:
- `SyncRun.nsu_start` = 100
- `SyncRun.nsu_end` = 150
- `FiscalDocument.count()` = 150

---

### 5.2 Deduplicação por Chave de Acesso

**Cenário**: Documento atualizado (ex: cancelamento) não deve criar duplicata

**Passos**:
1. Sincronizar NF-e #12345 (status: authorized)
2. Sincronizar evento de cancelamento da mesma NF-e
3. Verificar que existe apenas 1 registro no banco
4. Verificar que status foi atualizado para "cancelled"

**Asserções**:
- `FiscalDocument.where('access_key', $key)->count()` = 1
- `FiscalDocument.status` = 'cancelled'
- `FiscalEvent.where('fiscal_document_id', $id)->count()` = 1

---

### 5.3 Envelope Encryption (Certificado A1)

**Cenário**: Certificado A1 deve ser criptografado e descriptografado corretamente

**Passos**:
1. Upload certificado.pfx
2. Verificar que `encrypted_blob` e `encrypted_dek` estão preenchidos
3. Descriptografar e usar certificado para assinar XML
4. Verificar que assinatura é válida

**Asserções**:
- `Certificate.encrypted_blob` != null
- `Certificate.encrypted_dek` != null
- `openssl_verify($xml, $signature, $publicKey)` = 1

---

### 5.4 Tenant Isolation (RLS)

**Cenário**: Usuário de uma conta não deve ver documentos de outra conta

**Passos**:
1. Criar Account A e Account B
2. Criar documentos para cada conta
3. Autenticar como usuário da Account A
4. Listar documentos
5. Verificar que apenas documentos da Account A são retornados

**Asserções**:
- `GET /api/documents` retorna apenas docs onde `account_id = A`
- Tentativa de acessar `GET /api/documents/{doc_id_da_account_B}` retorna 404

---

## 6. Cobertura de Código

### Meta
- **Unit**: > 80%
- **Integration**: > 60%
- **E2E**: Fluxos críticos (5-10 cenários)

### Ferramentas
- **PHPUnit**: Coverage report (HTML/Clover)
- **Cypress**: Code coverage plugin

### Comando
```bash
# Backend
php artisan test --coverage --min=80

# Frontend
npm run test:coverage
```

---

## 7. CI/CD (GitHub Actions)

### Workflow: .github/workflows/test.yml

```yaml
name: Tests

on: [push, pull_request]

jobs:
  backend-tests:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_DB: fiscalmix_test
          POSTGRES_PASSWORD: secret
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

      redis:
        image: redis:7
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          extensions: pdo_pgsql, redis

      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Run Migrations
        run: php artisan migrate --env=testing

      - name: Run Tests
        run: php artisan test --coverage --min=80

  frontend-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: 20

      - name: Install Dependencies
        run: npm ci

      - name: Run Unit Tests
        run: npm run test:unit

      - name: Run E2E Tests
        run: npm run test:e2e:ci
```

---

## 8. Checklist de Aceitação

### MVP
- [ ] **Autenticação**
  - [ ] Cadastro com verificação de e-mail
  - [ ] Login com JWT
  - [ ] Recuperação de senha

- [ ] **Empresas**
  - [ ] CRUD completo
  - [ ] Validação de CNPJ

- [ ] **Certificados A1**
  - [ ] Upload e validação
  - [ ] Criptografia (envelope encryption)
  - [ ] Alertas de expiração

- [ ] **Sincronização SEFAZ DF-e**
  - [ ] Sync manual (NF-e, CT-e, MDF-e)
  - [ ] Sync agendado
  - [ ] Deduplicação por access_key
  - [ ] Logs de sync

- [ ] **Consulta e Download**
  - [ ] Filtros (período, tipo, status)
  - [ ] Download XML individual
  - [ ] Download ZIP em lote
  - [ ] Geração de PDF (DANFE)

- [ ] **UI/UX**
  - [ ] Responsivo (mobile/desktop)
  - [ ] Loading states (skeleton)
  - [ ] Empty states
  - [ ] Error handling (toasts)

- [ ] **Segurança**
  - [ ] Tenant isolation (RLS)
  - [ ] Rate limiting
  - [ ] HTTPS

- [ ] **Testes**
  - [ ] Unit: > 80% coverage
  - [ ] Integration: conectores, jobs
  - [ ] E2E: fluxo completo

---

### V1
- [ ] **NFS-e**
  - [ ] Conectores municipais (5 cidades)
  - [ ] Sync por competência

- [ ] **Certificado A3**
  - [ ] FiscalMix Bridge (Electron)
  - [ ] Pareamento via QR code
  - [ ] Heartbeat

- [ ] **RBAC**
  - [ ] Permissões por empresa
  - [ ] Roles (viewer, operator, admin)

- [ ] **Notificações**
  - [ ] E-mail (certificado expirando, sync falhou)
  - [ ] Webhooks

- [ ] **Billing**
  - [ ] Integração Stripe
  - [ ] Upgrade/downgrade

- [ ] **Performance**
  - [ ] API latency < 500ms (p95)
  - [ ] Sync 1 mês < 5 min
  - [ ] Uptime > 99.5%

---

## 9. Testes de Performance (Roadmap)

### Load Testing (K6)

```javascript
// k6/load_test.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  stages: [
    { duration: '2m', target: 100 }, // Ramp-up
    { duration: '5m', target: 100 }, // Sustain
    { duration: '2m', target: 0 },   // Ramp-down
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'], // 95% < 500ms
  },
};

export default function () {
  let res = http.get('https://api.fiscalmix.com/documents');
  check(res, { 'status is 200': (r) => r.status === 200 });
  sleep(1);
}
```

### Stress Testing (Sync)

**Cenário**: Sincronizar 10k documentos em paralelo (5 empresas)

**Métricas**:
- Tempo total < 10 min
- Memória < 512 MB por worker
- CPU < 80%

---

## 10. Conclusão

Esta estratégia de testes garante:
- **Qualidade**: Cobertura > 80% em código crítico
- **Confiabilidade**: Testes E2E para fluxos principais
- **Performance**: Validação de NFRs (latência, throughput)
- **Segurança**: Testes de tenant isolation e criptografia
- **CI/CD**: Automação completa no GitHub Actions
