<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('themes')) {
            Schema::create('themes', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120)->nullable(false);
                $table->string('btn_primary', 32)->nullable()->default('#6366f1');
                $table->string('btn_alt', 32)->nullable()->default('#06b6d4');
                $table->string('bg', 32)->nullable()->default('#f8fafc');
                $table->string('sidebar_bg', 32)->nullable()->default('#0f172a');
                $table->string('sidebar_text', 32)->nullable()->default('#ffffff');
                $table->string('gradient_start', 32)->nullable();
                $table->string('gradient_end', 32)->nullable();
                $table->integer('gradient_angle')->nullable()->default(90);
                $table->boolean('animated_gradient')->default(false);
                $table->float('animation_speed')->default(6.0); // seconds
                $table->integer('font_size')->default(16); // px base
                $table->json('button_variants')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
