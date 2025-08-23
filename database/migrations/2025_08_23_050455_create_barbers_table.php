<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('barbershop_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->string('email', 100)->nullable();
            $table->string('phone', 15);
            $table->longText('photo_base64')->nullable();
            $table->string('photo_type', 20)->nullable();
            $table->text('specialties')->nullable();
            $table->text('description')->nullable();
            $table->integer('experience_years')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->time('start_time');
            $table->time('end_time');
            $table->json('working_days')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('user_id');
            $table->index('barbershop_id');
            $table->index('active');
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barbers');
    }
};