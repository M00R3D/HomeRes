<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('themes')) {
            Schema::table('themes', function (Blueprint $table) {
                if (! Schema::hasColumn('themes', 'name')) {
                    $table->string('name', 120)->default('custom');
                }

                if (! Schema::hasColumn('themes', 'bg_gradient_start')) {
                    $table->string('bg_gradient_start', 32)->nullable();
                    $table->string('bg_gradient_end', 32)->nullable();
                    $table->integer('bg_gradient_angle')->nullable()->default(90);
                    $table->tinyInteger('bg_animated')->default(0);
                }
                if (! Schema::hasColumn('themes', 'sidebar_gradient_start')) {
                    $table->string('sidebar_gradient_start', 32)->nullable();
                    $table->string('sidebar_gradient_end', 32)->nullable();
                    $table->integer('sidebar_gradient_angle')->nullable()->default(90);
                    $table->tinyInteger('sidebar_animated')->default(0);
                }
                if (! Schema::hasColumn('themes', 'hover_animation')) {
                    $table->string('hover_animation', 60)->nullable()->default('none');
                    $table->double('hover_animation_duration')->default(0.18);
                    $table->string('float_animation', 60)->nullable()->default('none');
                    $table->double('float_animation_duration')->default(6.0);
                }
                if (! Schema::hasColumn('themes', 'button_variants')) {
                    $table->longText('button_variants')->nullable();
                }
                // Add common fields required by front-end (btn colors, global gradients, font size, meta)
                if (! Schema::hasColumn('themes', 'btn_primary')) {
                    $table->string('btn_primary', 32)->nullable()->default('#6366f1');
                }
                if (! Schema::hasColumn('themes', 'btn_alt')) {
                    $table->string('btn_alt', 32)->nullable()->default('#06b6d4');
                }
                if (! Schema::hasColumn('themes', 'bg')) {
                    $table->string('bg', 32)->nullable()->default('#f8fafc');
                }
                if (! Schema::hasColumn('themes', 'sidebar_bg')) {
                    $table->string('sidebar_bg', 32)->nullable()->default('#0f172a');
                }
                if (! Schema::hasColumn('themes', 'sidebar_text')) {
                    $table->string('sidebar_text', 32)->nullable()->default('#ffffff');
                }
                if (! Schema::hasColumn('themes', 'gradient_start')) {
                    $table->string('gradient_start', 32)->nullable();
                    $table->string('gradient_end', 32)->nullable();
                    $table->integer('gradient_angle')->nullable()->default(90);
                    $table->tinyInteger('animated_gradient')->default(0);
                    $table->double('animation_speed')->default(6);
                }
                if (! Schema::hasColumn('themes', 'font_size')) {
                    $table->integer('font_size')->default(16);
                }
                if (! Schema::hasColumn('themes', 'meta')) {
                    $table->longText('meta')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('themes')) {
            Schema::table('themes', function (Blueprint $table) {
                $cols = [
                    'name','btn_primary','btn_alt','bg','sidebar_bg','sidebar_text',
                    'gradient_start','gradient_end','gradient_angle','animated_gradient','animation_speed','font_size',
                    'button_variants','meta','bg_gradient_start','bg_gradient_end','bg_gradient_angle','bg_animated',
                    'sidebar_gradient_start','sidebar_gradient_end','sidebar_gradient_angle','sidebar_animated',
                    'hover_animation','hover_animation_duration','float_animation','float_animation_duration',
                    'created_at','updated_at'
                ];
                foreach ($cols as $c) {
                    if (Schema::hasColumn('themes', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }
    }
};
