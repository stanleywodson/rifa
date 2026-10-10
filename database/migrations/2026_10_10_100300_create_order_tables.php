<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('customers', function (Blueprint $table) {
      $table->id();
      $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
      $table->string('name');
      $table->string('cpf', 11);
      $table->string('email')->nullable();
      $table->string('phone', 20);
      $table->date('birthdate')->nullable();
      $table->string('gateway_customer_id')->nullable();
      $table->timestamps();
      $table->unique(['tenant_id', 'cpf']);
      $table->index(['tenant_id', 'phone']);
    });

    Schema::create('orders', function (Blueprint $table) {
      $table->id();
      $table->ulid('code')->unique();
      $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
      $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
      $table->foreignId('customer_id')->constrained();
      $table->string('status', 30)->default('awaiting_payment');
      $table->unsignedInteger('quantity');
      $table->unsignedInteger('subtotal_cents');
      $table->unsignedInteger('discount_cents')->default(0);
      $table->unsignedInteger('total_cents');
      $table->dateTime('expires_at');
      $table->timestamp('paid_at')->nullable();
      $table->string('ip', 45)->nullable();
      $table->timestamps();
      $table->index(['status', 'expires_at']);
      $table->index(['raffle_id', 'status']);
    });

    Schema::create('tickets', function (Blueprint $table) {
      $table->id();
      $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
      $table->unsignedInteger('number');
      $table->string('status', 20)->default('free');
      $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
      $table->dateTime('reserved_until')->nullable()->index();
      $table->timestamp('paid_at')->nullable();
      $table->unique(['raffle_id', 'number']);
      $table->index(['raffle_id', 'status']);
    });

    Schema::create('order_items', function (Blueprint $table) {
      $table->id();
      $table->foreignId('order_id')->constrained()->cascadeOnDelete();
      $table->foreignId('ticket_id')->constrained();
      $table->unsignedInteger('number');
      $table->unsignedInteger('price_cents');
      $table->index('ticket_id');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('order_items');
    Schema::dropIfExists('tickets');
    Schema::dropIfExists('orders');
    Schema::dropIfExists('customers');
  }
};
