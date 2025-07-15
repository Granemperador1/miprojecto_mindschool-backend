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
        Schema::create('plantillas_notificaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('codigo')->unique(); // código único para identificar la plantilla
            $table->enum('tipo', ['email', 'push', 'sms', 'in_app', 'webhook'])->default('email');
            $table->string('asunto')->nullable();
            $table->text('contenido');
            $table->json('variables')->nullable(); // variables disponibles en la plantilla
            $table->json('configuracion')->nullable(); // configuración específica del tipo
            $table->boolean('activa')->default(true);
            $table->string('idioma', 10)->default('es');
            $table->text('descripcion')->nullable();
            $table->foreignId('creador_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['tipo', 'activa']);
            $table->index('codigo');
            $table->index('idioma');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantillas_notificaciones');
    }
};
