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
        Schema::create('miembros_grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos_trabajo')->onDelete('cascade');
            $table->foreignId('estudiante_id')->constrained('users')->onDelete('cascade');
            $table->enum('rol', ['lider', 'miembro', 'secretario', 'coordinador'])->default('miembro');
            $table->timestamp('fecha_ingreso')->useCurrent();
            $table->timestamp('fecha_salida')->nullable();
            $table->enum('estado', ['activo', 'inactivo', 'expulsado'])->default('activo');
            $table->text('motivo_salida')->nullable();
            $table->json('permisos')->nullable(); // permisos específicos del miembro
            $table->text('notas')->nullable();
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['grupo_id', 'estado']);
            $table->index(['estudiante_id', 'estado']);
            $table->index('rol');
            $table->unique(['grupo_id', 'estudiante_id']); // Un estudiante por grupo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('miembros_grupo');
    }
};
