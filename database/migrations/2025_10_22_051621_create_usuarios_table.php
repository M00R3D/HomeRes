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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('apellido', 100)->nullable();
            $table->string('email', 150)->unique()->nullable();
            $table->string('password', 255);
            $table->rememberToken();
            $table->integer('intentos_cvv')->default(0);
            $table->boolean('bloqueo_tarjetas')->default(false);
            $table->boolean('baneado')->default(false);
            $table->enum('rol', ['admin', 'recepcionista', 'cliente'])->default('cliente');
            $table->string('area', 100)->nullable();
            $table->unsignedBigInteger('id_tarjeta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
