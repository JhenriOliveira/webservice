<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbershop_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);                                             // Nome do Serviço
            $table->text('description')->nullable();                                 // Breve Descricao do serviço
            $table->decimal('price', 8, 2);                                          // Preço
            $table->integer('duration_minutes')->default(30);                        // Duração em minutos
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('barbershop_id');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};