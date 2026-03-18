<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pagos')) {
            return;
        }

        Schema::table('pagos', function (Blueprint $table) {
            if (! Schema::hasColumn('pagos', 'tarjeta_id')) {
                $table->unsignedBigInteger('tarjeta_id')->nullable()->after('reservacion_id');
            }
            if (! Schema::hasColumn('pagos', 'usuario_id')) {
                $table->unsignedBigInteger('usuario_id')->nullable()->after('tarjeta_id');
            }
            if (! Schema::hasColumn('pagos', 'codigo_qr')) {
                $table->string('codigo_qr', 64)->nullable()->after('estado');
            }
            if (! Schema::hasColumn('pagos', 'codigo_qr_generado_en')) {
                $table->dateTime('codigo_qr_generado_en')->nullable()->after('codigo_qr');
            }
        });

        if (Schema::hasColumn('pagos', 'codigo_qr')) {
            try {
                Schema::table('pagos', function (Blueprint $table) {
                    $table->unique('codigo_qr');
                });
            } catch (\Throwable $e) {
                // Ignore if unique index already exists.
            }
        }

        if (Schema::hasColumn('pagos', 'tarjeta_id')) {
            try {
                Schema::table('pagos', function (Blueprint $table) {
                    $table->foreign('tarjeta_id')->references('id')->on('tarjetas_simuladas')->onDelete('set null');
                });
            } catch (\Throwable $e) {
                // Ignore if foreign key already exists.
            }
        }

        if (Schema::hasColumn('pagos', 'usuario_id')) {
            try {
                Schema::table('pagos', function (Blueprint $table) {
                    $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
                });
            } catch (\Throwable $e) {
                // Ignore if foreign key already exists.
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('pagos')) {
            return;
        }

        Schema::table('pagos', function (Blueprint $table) {
            if (Schema::hasColumn('pagos', 'usuario_id')) {
                try {
                    $table->dropForeign(['usuario_id']);
                } catch (\Throwable $e) {
                }
            }
            if (Schema::hasColumn('pagos', 'tarjeta_id')) {
                try {
                    $table->dropForeign(['tarjeta_id']);
                } catch (\Throwable $e) {
                }
            }
            if (Schema::hasColumn('pagos', 'codigo_qr')) {
                try {
                    $table->dropUnique('pagos_codigo_qr_unique');
                } catch (\Throwable $e) {
                }
            }
        });

        Schema::table('pagos', function (Blueprint $table) {
            if (Schema::hasColumn('pagos', 'codigo_qr_generado_en')) {
                $table->dropColumn('codigo_qr_generado_en');
            }
            if (Schema::hasColumn('pagos', 'codigo_qr')) {
                $table->dropColumn('codigo_qr');
            }
            if (Schema::hasColumn('pagos', 'usuario_id')) {
                $table->dropColumn('usuario_id');
            }
            if (Schema::hasColumn('pagos', 'tarjeta_id')) {
                $table->dropColumn('tarjeta_id');
            }
        });
    }
};
