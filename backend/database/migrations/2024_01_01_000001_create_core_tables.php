<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Habilitar extensão UUID
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        
        // Accounts (Tenants)
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->string('name');
            $table->enum('plan', ['free', 'starter', 'professional', 'enterprise'])->default('free');
            $table->enum('status', ['active', 'suspended', 'cancelled'])->default('active');
            $table->timestamps();
            
            $table->index('status');
        });

        // Users
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('account_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('mfa_enabled')->default(false);
            $table->string('mfa_secret')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->index('account_id');
            $table->index('email');
        });

        // Companies (CNPJs)
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('account_id');
            $table->string('cnpj', 14);
            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('ie', 50)->nullable();
            $table->char('uf', 2);
            $table->string('municipio', 100)->nullable();
            $table->jsonb('tags')->default('[]');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->unique(['account_id', 'cnpj']);
            $table->index('account_id');
            $table->index('cnpj');
            $table->index('status');
        });

        // Certificates (A1 e A3)
        Schema::create('certificates', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('company_id');
            $table->enum('type', ['A1', 'A3']);
            $table->text('encrypted_blob')->nullable(); // A1 only
            $table->text('encrypted_dek')->nullable(); // A1 only
            $table->string('fingerprint')->nullable();
            $table->string('pairing_token')->nullable(); // A3 only
            $table->jsonb('metadata')->default('{}'); // {subject, issuer, etc}
            $table->timestamp('valid_from');
            $table->timestamp('valid_to');
            $table->timestamp('last_heartbeat')->nullable(); // A3 only
            $table->enum('status', ['active', 'expired', 'revoked', 'pending'])->default('active');
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index('company_id');
            $table->index('valid_to');
            $table->index('status');
        });

        // Connector Configs (NFS-e município/provedor)
        Schema::create('connector_configs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('company_id');
            $table->enum('doc_type', ['NFE', 'NFCE', 'CTE', 'MDFE', 'NFSE']);
            $table->jsonb('config')->default('{}'); // {municipio, provedor, etc}
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique(['company_id', 'doc_type']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connector_configs');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('users');
        Schema::dropIfExists('accounts');
        DB::statement('DROP EXTENSION IF EXISTS "uuid-ossp"');
    }
};
