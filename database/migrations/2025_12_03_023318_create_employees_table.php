<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable();          
            $table->string('phone')->unique();           
            $table->string('email')->nullable();        
            $table->decimal('salary', 10, 2)->nullable(); 
            $table->decimal('given_money', 10, 2)->nullable(); 
            $table->integer('is_active')->default(1); 
            $table->unsignedBigInteger('user_id')->nullable(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
