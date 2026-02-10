<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
       Schema::create('supply_items', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('supply_id');
    $table->unsignedBigInteger('employee_id');
    $table->unsignedBigInteger('customer_id');

    // 🐄 Cow milk
    $table->decimal('cow_milk_quantity', 10, 2)->nullable();
    $table->decimal('cow_price_per_liter', 10, 2)->nullable();
    $table->decimal('cow_amount', 10, 2)->default(0);

    // 🐃 Buffalo milk
    $table->decimal('buffalo_milk_quantity', 10, 2)->nullable();
    $table->decimal('buffalo_price_per_liter', 10, 2)->nullable();
    $table->decimal('buffalo_amount', 10, 2)->default(0);

    // Totals
    $table->decimal('total_quantity', 10, 2)->default(0);
    $table->decimal('total_amount', 10, 2)->default(0);

    $table->boolean('is_delivered')->default(0);

    $table->timestamps();
});

    }

    public function down(): void
    {
        Schema::dropIfExists('supply_items');
    }
};
