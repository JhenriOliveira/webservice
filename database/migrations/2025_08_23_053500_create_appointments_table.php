<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbershop_id')->constrained()->onDelete('cascade');
            $table->foreignId('barber_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');                       // Cliente
            $table->dateTime('start_time');                                                         // Data e hora de início
            $table->dateTime('end_time');                                                           // Data e hora de término
            $table->integer('total_duration_minutes');                                              // Duração total em minutos
            $table->decimal('total_price', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])
                  ->default('pending');                                                             // Status
            $table->text('notes')->nullable();                                                      // Observações do cliente
            $table->text('barber_notes')->nullable();                                               // Observações do barbeiro
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('barbershop_id');
            $table->index('barber_id');
            $table->index('user_id');
            $table->index('start_time');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};