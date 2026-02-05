<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
       Schema::create('supplies', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('employee_id');

    // 🐄🐃 Milk totals
    $table->decimal('total_cow_milk_quantity', 10, 2)->default(0);
    $table->decimal('total_buffalo_milk_quantity', 10, 2)->default(0);

    // 📦 Grand totals
    $table->decimal('total_quantity', 10, 2)->default(0);
    $table->decimal('total_amount', 10, 2)->default(0);

    $table->boolean('is_supplied')->default(false);
    $table->date('supply_date')->nullable();
    $table->boolean('is_assigned_only')->default(0)->nullable();

    $table->timestamps();
});

    }

    public function down(): void
    {
        Schema::dropIfExists('supplies');
    }
};
