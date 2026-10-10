<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('raffles', function (Blueprint $table) {
      $table->id();
      $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
      $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
      $table->string('title');
      $table->string('slug', 80);
      $table->text('summary')->nullable();
      $table->longText('description')->nullable();
      $table->string('status', 30)->default('draft')->index();
      $table->unsignedInteger('total_numbers');
      $table->unsignedTinyInteger('number_start')->default(0);
      $table->unsignedInteger('price_cents');
      $table->unsignedInteger('min_numbers_per_order')->default(1);
      $table->dateTime('sales_end_at')->nullable();
      $table->dateTime('draw_at')->nullable();
      $table->string('draw_method', 20)->default('federal');
      $table->string('unsold_rule', 30)->default('next_paid_above');
      $table->json('draw_rules')->nullable();
      $table->longText('regulation')->nullable();
      $table->string('authorization_number', 80)->nullable();
      $table->string('authorization_doc_path')->nullable();
      $table->char('commit_hash', 64)->nullable();
      $table->text('seed_encrypted')->nullable();
      $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
      $table->timestamp('approved_at')->nullable();
      $table->text('review_notes')->nullable();
      $table->timestamp('submitted_at')->nullable();
      $table->timestamp('tickets_generated_at')->nullable();
      $table->timestamp('published_at')->nullable();
      $table->timestamp('sales_closed_at')->nullable();
      $table->string('delivery_proof_path')->nullable();
      $table->text('delivery_notes')->nullable();
      $table->timestamp('completed_at')->nullable();
      $table->timestamps();
      $table->unique(['tenant_id', 'slug']);
    });

    Schema::create('raffle_prizes', function (Blueprint $table) {
      $table->id();
      $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
      $table->unsignedTinyInteger('position')->default(1);
      $table->string('title');
      $table->text('description')->nullable();
      $table->unsignedInteger('estimated_value_cents')->nullable();
      $table->timestamps();
    });

    Schema::create('raffle_media', function (Blueprint $table) {
      $table->id();
      $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
      $table->string('type', 20)->default('image');
      $table->string('path');
      $table->unsignedSmallInteger('sort')->default(0);
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('raffle_media');
    Schema::dropIfExists('raffle_prizes');
    Schema::dropIfExists('raffles');
  }
};
