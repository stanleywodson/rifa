<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('plans', function (Blueprint $table) {
      $table->id();
      $table->string('code', 40)->unique();
      $table->string('name');
      $table->unsignedInteger('price_cents')->default(0);
      $table->decimal('fee_percent', 5, 2);
      $table->json('limits');
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });

    Schema::create('tenants', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('slug', 60)->unique();
      $table->string('document', 18)->nullable(); // CPF/CNPJ
      $table->string('email')->nullable();
      $table->string('phone', 20)->nullable();
      $table->string('status', 20)->default('active');
      $table->foreignId('plan_id')->nullable()->constrained();
      $table->decimal('fee_percent', 5, 2)->nullable(); // sobrescreve o plano
      $table->string('kyc_status', 20)->default('pending');
      $table->timestamp('kyc_reviewed_at')->nullable();
      $table->text('kyc_notes')->nullable();
      $table->string('asaas_account_id')->nullable();
      $table->string('asaas_wallet_id')->nullable();
      $table->text('asaas_api_key')->nullable(); // encrypted
      $table->json('settings')->nullable();
      $table->timestamps();
    });

    Schema::create('tenant_user', function (Blueprint $table) {
      $table->id();
      $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('role', 20)->default('owner');
      $table->timestamps();
      $table->unique(['tenant_id', 'user_id']);
    });

    Schema::create('kyc_documents', function (Blueprint $table) {
      $table->id();
      $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
      $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
      $table->string('type', 30); // identity, selfie, company, address
      $table->string('path');
      $table->string('original_name');
      $table->timestamps();
    });

    Schema::create('audit_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('tenant_id')->nullable()->index();
      $table->foreignId('user_id')->nullable()->index();
      $table->string('action', 80);
      $table->string('subject_type', 80)->nullable();
      $table->unsignedBigInteger('subject_id')->nullable();
      $table->json('before')->nullable();
      $table->json('after')->nullable();
      $table->string('ip', 45)->nullable();
      $table->string('user_agent')->nullable();
      $table->char('prev_hash', 64)->nullable();
      $table->char('hash', 64);
      $table->timestamp('created_at')->useCurrent();
      $table->index(['subject_type', 'subject_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('audit_logs');
    Schema::dropIfExists('kyc_documents');
    Schema::dropIfExists('tenant_user');
    Schema::dropIfExists('tenants');
    Schema::dropIfExists('plans');
  }
};
