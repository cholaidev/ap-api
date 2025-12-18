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
               

            $table->string('milk')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('price_per_liter', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->default(0)->nullable();
            $table->integer('is_delivered')->default(0)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_items');
    }
};
