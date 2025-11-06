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
    Schema::create('deliveries', function (Blueprint $table) {
        $table->id();
        $table->foreignId('customer_id')->constrained()->onDelete('cascade');
        $table->foreignId('staff_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('product_id')->constrained()->onDelete('cascade');
        $table->decimal('quantity', 8, 2);
        $table->decimal('unit_price', 8, 2);
        $table->decimal('total', 10, 2);
        $table->date('date');
        $table->enum('status', ['pending', 'delivered', 'cancelled'])->default('pending');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
