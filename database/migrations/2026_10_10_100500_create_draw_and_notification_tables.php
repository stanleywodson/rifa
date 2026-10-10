<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('draws', function (Blueprint $table) {
      $table->id();
      $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
      $table->string('method', 20);
      $table->string('source_ref')->nullable(); // concurso / URL
      $table->json('raw_data')->nullable();
      $table->text('seed_revealed')->nullable();
      $table->unsignedInteger('computed_number')->nullable(); // resultado do cálculo
      $table->unsignedInteger('drawn_number')->nullable(); // após regra de não vendido
      $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
      $table->string('outcome', 20); // winner | redraw | void
      $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
      $table->timestamp('executed_at');
      $table->char('prev_hash', 64)->nullable();
      $table->char('hash', 64);
      $table->string('minutes_pdf_path')->nullable();
      $table->timestamps();
    });

    Schema::create('notifications_log', function (Blueprint $table) {
      $table->id();
      $table->foreignId('tenant_id')->nullable()->index();
      $table->string('channel', 20)->default('mail');
      $table->string('recipient');
      $table->string('template', 120);
      $table->string('status', 20)->default('sent');
      $table->json('payload')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('notifications_log');
    Schema::dropIfExists('draws');
  }
};
