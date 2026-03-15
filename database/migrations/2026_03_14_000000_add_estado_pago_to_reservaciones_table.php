<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('reservaciones', 'estado_pago')) {
            Schema::table('reservaciones', function (Blueprint $table) {
                $table->string('estado_pago', 50)->nullable()->default('pendiente')->after('estado');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reservaciones', 'estado_pago')) {
            Schema::table('reservaciones', function (Blueprint $table) {
                $table->dropColumn('estado_pago');
            });
        }
    }
};
