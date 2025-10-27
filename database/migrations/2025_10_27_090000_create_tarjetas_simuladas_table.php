<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarjetas_simuladas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_tarjeta', 16);
            $table->string('nombre', 100);
            $table->string('expiracion', 5); // MM/YY
            $table->string('cvv', 3);
            $table->decimal('saldo', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarjetas_simuladas');
    }
};