<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('logs')) {
            Schema::create('logs', function (Blueprint $table) {
                $table->id();
                $table->string('tipo', 100)->nullable(false);
                $table->text('mensaje');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
