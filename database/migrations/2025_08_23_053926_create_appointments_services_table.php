<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppointmentsServicesTable extends Migration
{
    public function up()
    {
        Schema::create('appointments_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->integer('duration');
            $table->timestamps();
            
            // Garantir que não haja duplicidade
            $table->unique(['appointment_id', 'service_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointments_services');
    }
}