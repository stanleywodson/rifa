<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('tenants', function (Blueprint $table) {
      // Conta Mercado Pago conectada via OAuth (marketplace)
      $table->string('mp_user_id', 40)->nullable()->after('payment_gateway');
      $table->text('mp_access_token')->nullable()->after('mp_user_id'); // encrypted
      $table->text('mp_refresh_token')->nullable()->after('mp_access_token'); // encrypted
      $table->string('mp_public_key', 100)->nullable()->after('mp_refresh_token');
      $table->timestamp('mp_token_expires_at')->nullable()->after('mp_public_key');
      $table->boolean('mp_live_mode')->default(false)->after('mp_token_expires_at');
    });
  }

  public function down(): void
  {
    Schema::table('tenants', function (Blueprint $table) {
      $table->dropColumn(['mp_user_id', 'mp_access_token', 'mp_refresh_token', 'mp_public_key', 'mp_token_expires_at', 'mp_live_mode']);
    });
  }
};
