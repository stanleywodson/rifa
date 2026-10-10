<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->string('cpf', 14)->nullable()->after('email');
      $table->string('phone', 20)->nullable()->after('cpf');
      $table->date('birthdate')->nullable()->after('phone');
      $table->boolean('is_platform_admin')->default(false)->after('birthdate');
    });
  }

  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn(['cpf', 'phone', 'birthdate', 'is_platform_admin']);
    });
  }
};
