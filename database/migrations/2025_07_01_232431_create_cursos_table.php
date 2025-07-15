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
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion');
            $table->integer('duracion');
            $table->enum('nivel', ['principiante', 'intermedio', 'avanzado']);
            $table->decimal('precio', 8, 2)->default(0);
            $table->enum('estado', ['activo', 'inactivo', 'borrador'])->default('activo');
            $table->unsignedBigInteger('instructor_id');
            $table->timestamps();

            $table->foreign('instructor_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};
