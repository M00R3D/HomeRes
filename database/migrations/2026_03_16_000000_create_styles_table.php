<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('styles')) {
            Schema::create('styles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->default('global');
                $table->string('btn_primary', 32)->default('#6366f1');
                $table->string('btn_alt', 32)->default('#06b6d4');
                $table->string('bg', 32)->default('#f8fafc');
                $table->string('sidebar_bg', 32)->default('#ffffff');
                $table->string('sidebar_text', 32)->default('#0f172a');
                $table->unsignedTinyInteger('transparency')->default(0); // 0-100
                $table->string('variant', 50)->default('predeterminado');
                $table->string('exotic_animation', 100)->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('styles');
    }
};
