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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reservacion_id')->nullable();
            $table->decimal('monto', 10, 2);
            $table->enum('metodo_pago', ['efectivo', 'tarjeta', 'transferencia'])->default('efectivo');
            $table->enum('estado', ['pendiente', 'pagado', 'cancelado'])->default('pendiente');
            $table->timestamp('fecha_pago')->useCurrent();

            $table->foreign('reservacion_id')->references('id')->on('reservaciones')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
