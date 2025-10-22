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
        Schema::create('cabanas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique()->nullable();
            $table->string('nombre', 100);
            $table->string('descripcion', 500)->nullable();
            $table->unsignedInteger('capacidad')->default(2);
            $table->decimal('precio_noche', 10, 2);
            $table->string('ubicacion', 200)->nullable();
            $table->string('servicios', 500)->nullable();
            $table->enum('estado', ['disponible', 'ocupada', 'mantenimiento'])->default('disponible');
            $table->string('ruta_img', 200)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cabanas');
    }
};
