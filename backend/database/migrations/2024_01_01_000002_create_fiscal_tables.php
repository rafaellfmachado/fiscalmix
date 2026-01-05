<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Fiscal Documents
        Schema::create('fiscal_documents', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('account_id');
            $table->uuid('company_id');
            $table->enum('doc_type', ['NFE', 'NFCE', 'CTE', 'MDFE', 'NFSE']);
            $table->enum('direction', ['issued', 'received']);
            $table->string('access_key', 44)->nullable()->unique(); // NF-e/CT-e/MDF-e
            $table->string('external_id')->nullable(); // NFS-e ou outros
            $table->integer('number');
            $table->integer('series')->nullable();
            $table->timestamp('issue_date');
            $table->date('competence')->nullable(); // NFS-e
            $table->string('issuer_cnpj_cpf', 14);
            $table->string('issuer_name')->nullable();
            $table->string('recipient_cnpj_cpf', 14)->nullable();
            $table->string('recipient_name')->nullable();
            $table->decimal('total_value', 15, 2);
            $table->string('status', 50); // authorized, cancelled, denied, etc
            $table->string('storage_xml_path', 500);
            $table->string('storage_pdf_path', 500)->nullable();
            $table->string('hash_sha256', 64);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique(['account_id', 'external_id', 'doc_type']);
            $table->index(['account_id', 'company_id']);
            $table->index('issue_date');
            $table->index(['doc_type', 'status']);
            $table->index('direction');
            $table->index('issuer_cnpj_cpf');
            $table->index('recipient_cnpj_cpf');
            $table->index('access_key');
            $table->index('external_id');
        });

        // Fiscal Events (cancelamento, CCe, etc)
        Schema::create('fiscal_events', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('fiscal_document_id');
            $table->string('event_type', 50); // cancelamento, cce, manifestacao, etc
            $table->timestamp('event_date');
            $table->string('protocol', 50)->nullable();
            $table->string('storage_xml_path', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('fiscal_document_id')->references('id')->on('fiscal_documents')->onDelete('cascade');
            $table->index('fiscal_document_id');
            $table->index('event_type');
        });

        // Sync Runs (logs de sincronização)
        Schema::create('sync_runs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('company_id');
            $table->string('doc_type', 10);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->bigInteger('nsu_start')->nullable();
            $table->bigInteger('nsu_end')->nullable();
            $table->integer('docs_found')->default(0);
            $table->integer('docs_saved')->default(0);
            $table->integer('events_saved')->default(0);
            $table->jsonb('error_summary')->nullable();
            $table->string('logs_path', 500)->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index('company_id');
            $table->index('status');
            $table->index('started_at');
        });

        // Company User Permissions (RBAC)
        Schema::create('company_user_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('company_id');
            $table->uuid('user_id');
            $table->enum('role', ['viewer', 'operator', 'admin']);
            $table->jsonb('permissions')->default('{}');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['company_id', 'user_id']);
            $table->index('company_id');
            $table->index('user_id');
        });

        // Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('account_id');
            $table->uuid('user_id')->nullable();
            $table->string('action', 100);
            $table->string('entity_type', 50)->nullable();
            $table->uuid('entity_id')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->inet('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index('account_id');
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });

        // Export Jobs (ZIP assíncronos)
        Schema::create('export_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('account_id');
            $table->uuid('company_id')->nullable();
            $table->uuid('user_id');
            $table->jsonb('filters');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('progress')->default(0);
            $table->string('storage_zip_path', 500)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('account_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_jobs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('company_user_permissions');
        Schema::dropIfExists('sync_runs');
        Schema::dropIfExists('fiscal_events');
        Schema::dropIfExists('fiscal_documents');
    }
};
