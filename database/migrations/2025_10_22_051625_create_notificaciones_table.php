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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->unsignedBigInteger('reservacion_id')->nullable();
            $table->unsignedBigInteger('propiedad_id')->nullable();
            $table->enum('estado', ['cerrada', 'abierta', 'vista'])->default('cerrada');
            $table->enum('tipo', ['info', 'confirmacion', 'pago', 'alerta', 'mantenimiento'])->default('info');
            $table->string('descripcion', 500);
            $table->string('link', 512)->nullable();
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_visto')->nullable();

            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
            $table->foreign('reservacion_id')->references('id')->on('reservaciones')->onDelete('cascade');
            $table->foreign('propiedad_id')->references('id')->on('propiedades')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
