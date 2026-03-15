<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (! Schema::hasColumn('usuarios', 'intentos_cvv')) {
                $table->integer('intentos_cvv')->default(0)->after('password');
            }
            if (! Schema::hasColumn('usuarios', 'bloqueo_tarjetas')) {
                $table->boolean('bloqueo_tarjetas')->default(false)->after('intentos_cvv');
            }
        });
    }

    public function down()
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios', 'bloqueo_tarjetas')) {
                $table->dropColumn('bloqueo_tarjetas');
            }
            if (Schema::hasColumn('usuarios', 'intentos_cvv')) {
                $table->dropColumn('intentos_cvv');
            }
        });
    }
};
