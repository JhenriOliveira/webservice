<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('barbershops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Vincular com Usuário Existente                                   
            $table->string('name', 100);                                             // Nome da Barbearia (Comercial)
            $table->string('corporate_name', 150)->nullable();                       // Razao Social da barbearia
            $table->string('tax_id', 18)->unique()->nullable();                      // CNPJ
            $table->string('phone', 15);                                             // Telefone de Contato
            $table->string('email', 100)->unique();                                  // Email de Contato
            $table->text('description')->nullable();                                 // Breve Descrição
            $table->string('address', 200);                                          // Endereço
            $table->string('address_number', 10);                                    // Numero
            $table->string('address_complement', 100)->nullable();                   // Complemento do Endereço
            $table->string('neighborhood', 100);                                     // Bairro
            $table->string('city', 100);                                             // Cidade
            $table->string('state', 2);                                              // Estado
            $table->string('zip_code', 9);                                           // CEP
            $table->decimal('latitude', 10, 8)->nullable();                            
            $table->decimal('longitude', 11, 8)->nullable();             
            $table->time('opening_time');                                            // Horário de Abertura
            $table->time('closing_time');                                            // Horário de Fechamento
            $table->integer('average_service_time')->default(30);                    // Tempo Médio de Serviço (em minutos)
            $table->boolean('accepts_online_scheduling')->default(true);             // Aceita Agendamento Online
            $table->boolean('active')->default(true);                                // Status do Cadastro
            $table->longText('profile_photo_base64')->nullable();                    // Foto em Base64
            $table->string('profile_photo_type', 20)->nullable();                    // Tipo da imagem (jpeg, png, etc.)
            $table->json('social_media')->nullable();                                // JSON com links
            $table->json('working_days')->nullable();                                // Dias da semana que funciona
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('barbershops');
    }
};