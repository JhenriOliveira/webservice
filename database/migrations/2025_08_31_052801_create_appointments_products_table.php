<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppointmentsProductsTable extends Migration
{
    public function up()
    {
        Schema::create('appointments_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2);
            $table->timestamps();
            
            // Garantir que não haja duplicidade
            $table->unique(['appointment_id', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointments_products');
    }
}