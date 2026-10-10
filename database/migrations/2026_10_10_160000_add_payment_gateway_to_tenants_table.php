<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('tenants', function (Blueprint $table) {
      // Gateway em que a conta de recebimento (wallet) foi criada
      $table->string('payment_gateway', 20)->nullable()->after('asaas_api_key');
    });

    // Carteiras existentes: as do driver simulado têm prefixo "fake_"
    DB::table('tenants')->whereNotNull('asaas_wallet_id')->update([
      'payment_gateway' => DB::raw("CASE WHEN asaas_wallet_id LIKE 'fake\\_%' THEN 'fake' ELSE 'asaas' END"),
    ]);
  }

  public function down(): void
  {
    Schema::table('tenants', function (Blueprint $table) {
      $table->dropColumn('payment_gateway');
    });
  }
};
