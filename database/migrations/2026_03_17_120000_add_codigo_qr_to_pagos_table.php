<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pagos')) {
            Schema::table('pagos', function (Blueprint $table) {
                if (! Schema::hasColumn('pagos', 'codigo_qr')) {
                    $table->string('codigo_qr', 64)->nullable()->unique()->after('estado');
                }

                if (! Schema::hasColumn('pagos', 'codigo_qr_generado_en')) {
                    $table->dateTime('codigo_qr_generado_en')->nullable()->after('codigo_qr');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pagos')) {
            Schema::table('pagos', function (Blueprint $table) {
                if (Schema::hasColumn('pagos', 'codigo_qr_generado_en')) {
                    $table->dropColumn('codigo_qr_generado_en');
                }

                if (Schema::hasColumn('pagos', 'codigo_qr')) {
                    $table->dropUnique('pagos_codigo_qr_unique');
                    $table->dropColumn('codigo_qr');
                }
            });
        }
    }
};
