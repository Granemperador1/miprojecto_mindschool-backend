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
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('remitente_id');
            $table->unsignedBigInteger('destinatario_id');
            $table->string('asunto');
            $table->text('contenido');
            $table->enum('tipo', ['consulta', 'respuesta', 'notificacion', 'general'])->default('general');
            $table->enum('estado', ['enviado', 'leido', 'archivado'])->default('enviado');
            $table->timestamp('fecha_envio')->useCurrent();
            $table->timestamp('fecha_lectura')->nullable();
            $table->timestamps();

            $table->foreign('remitente_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('destinatario_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};
