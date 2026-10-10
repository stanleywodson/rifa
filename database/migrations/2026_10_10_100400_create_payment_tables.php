<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('payments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('order_id')->constrained()->cascadeOnDelete();
      $table->string('gateway', 20);
      $table->string('gateway_id')->nullable()->index();
      $table->string('method', 20)->default('pix');
      $table->string('status', 20)->default('pending');
      $table->unsignedInteger('amount_cents');
      $table->longText('pix_qr_image')->nullable(); // base64 PNG
      $table->text('pix_copy_paste')->nullable();
      $table->timestamp('paid_at')->nullable();
      $table->json('payload')->nullable();
      $table->timestamps();
    });

    Schema::create('payment_splits', function (Blueprint $table) {
      $table->id();
      $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
      $table->string('recipient', 20); // platform | organizer
      $table->string('wallet_id')->nullable();
      $table->unsignedInteger('amount_cents');
      $table->timestamps();
    });

    Schema::create('refunds', function (Blueprint $table) {
      $table->id();
      $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
      $table->string('reason');
      $table->unsignedInteger('amount_cents');
      $table->string('status', 20)->default('pending');
      $table->json('payload')->nullable();
      $table->timestamps();
    });

    Schema::create('webhook_events', function (Blueprint $table) {
      $table->id();
      $table->string('origin', 30);
      $table->string('event_id');
      $table->string('event_type', 80)->nullable();
      $table->json('payload');
      $table->timestamp('processed_at')->nullable();
      $table->text('error')->nullable();
      $table->timestamps();
      $table->unique(['origin', 'event_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('webhook_events');
    Schema::dropIfExists('refunds');
    Schema::dropIfExists('payment_splits');
    Schema::dropIfExists('payments');
  }
};
