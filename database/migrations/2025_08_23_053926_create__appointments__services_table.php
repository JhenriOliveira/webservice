<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 8, 2);                                          // Preço na hora do agendamento
            $table->integer('duration_minutes');                                     // Duração na hora do agendamento
            $table->timestamps();

            $table->unique(['appointment_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_service');
    }
};