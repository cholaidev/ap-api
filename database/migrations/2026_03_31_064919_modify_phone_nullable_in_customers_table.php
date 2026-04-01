<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up(): void
{
    Schema::table('customers', function (Blueprint $table) {
        // 🔥 Drop existing unique index
        $table->dropUnique('customers_phone_unique');

        // ✅ Modify column
        $table->string('phone')->nullable()->unique()->change();
    });
}

    /**
     * Reverse the migrations.
     */
  public function down(): void
{
    Schema::table('customers', function (Blueprint $table) {
        $table->dropUnique('customers_phone_unique');
        $table->string('phone')->unique()->nullable(false)->change();
    });
}
};
