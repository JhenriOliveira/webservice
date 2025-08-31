<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppointmentsTable extends Migration
{
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barber_id')->constrained('barbers')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('barbershop_id')->constrained('barbershops')->onDelete('cascade');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->decimal('total_price', 10, 2);
            $table->integer('total_duration');
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'confirmed', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
            
            $table->index(['barber_id', 'start_time']);
            $table->index(['client_id', 'start_time']);
            $table->index(['barbershop_id', 'start_time']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointments');
    }
}