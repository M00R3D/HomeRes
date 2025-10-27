<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pagos')) {
            Schema::create('pagos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reservacion_id');
                $table->decimal('monto', 10, 2);
                $table->string('metodo_pago', 50);
                $table->enum('estado', ['pendiente','pagado','cancelado'])->default('pendiente');
                $table->dateTime('fecha_pago')->nullable();
                $table->foreign('reservacion_id')->references('id')->on('reservaciones')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};