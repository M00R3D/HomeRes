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
        Schema::create('comentarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reservacion_id');
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedTinyInteger('calificacion');
            $table->string('comentario', 1000);
            $table->timestamp('fecha_creacion')->useCurrent();

            $table->foreign('reservacion_id')->references('id')->on('reservaciones')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comentarios');
    }
};
