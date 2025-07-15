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
        Schema::create('transacciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('plan_id')->nullable()->constrained('planes_pago')->onDelete('set null');
            $table->foreignId('curso_id')->nullable()->constrained('cursos')->onDelete('set null');
            $table->string('numero_transaccion')->unique();
            $table->decimal('monto', 10, 2);
            $table->string('moneda', 10)->default('USD');
            $table->enum('estado', ['pendiente', 'completada', 'fallida', 'reembolsada', 'cancelada'])->default('pendiente');
            $table->enum('metodo_pago', ['tarjeta', 'paypal', 'transferencia', 'efectivo', 'crypto'])->nullable();
            $table->string('referencia_pago')->nullable();
            $table->string('codigo_promocional')->nullable();
            $table->decimal('descuento_aplicado', 10, 2)->default(0);
            $table->decimal('monto_final', 10, 2);
            $table->timestamp('fecha_pago')->nullable();
            $table->timestamp('fecha_vencimiento')->nullable();
            $table->json('detalles_pago')->nullable(); // detalles específicos del método de pago
            $table->text('notas')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['user_id', 'estado']);
            $table->index(['estado', 'fecha_pago']);
            $table->index('numero_transaccion');
            $table->index('referencia_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transacciones');
    }
};
