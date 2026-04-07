<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure previous trigger removed
        DB::unprepared('DROP TRIGGER IF EXISTS usuarios_before_update_cvv_block;');

        DB::unprepared(<<<'SQL'
CREATE TRIGGER usuarios_before_update_cvv_block
BEFORE UPDATE ON usuarios FOR EACH ROW
BEGIN
  -- When attempts cross the threshold set bloqueo_tarjetas = true
  IF COALESCE(NEW.intentos_cvv, 0) >= 5 AND COALESCE(OLD.intentos_cvv, 0) < 5 THEN
    SET NEW.bloqueo_tarjetas = 1;
  END IF;
END;
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS usuarios_before_update_cvv_block;');
    }
};
