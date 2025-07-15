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
        Schema::create('configuracion_notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tipo_notificacion'); // 'nueva_tarea', 'recordatorio_examen', 'calificacion', etc.
            $table->boolean('email')->default(true);
            $table->boolean('push')->default(true);
            $table->boolean('sms')->default(false);
            $table->boolean('in_app')->default(true);
            $table->boolean('webhook')->default(false);
            $table->string('webhook_url')->nullable();
            $table->json('horarios_permitidos')->nullable(); // horarios en los que recibir notificaciones
            $table->json('frecuencia')->nullable(); // configuración de frecuencia
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['user_id', 'tipo_notificacion']);
            $table->index('activo');
            $table->unique(['user_id', 'tipo_notificacion']); // Una configuración por usuario por tipo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_notificaciones');
    }
};
