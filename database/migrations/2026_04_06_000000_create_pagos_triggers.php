<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop/create insert trigger
        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS pagos_before_insert_insuficiente;
SQL
        );

        DB::unprepared(<<<'SQL'
CREATE TRIGGER pagos_before_insert_insuficiente
BEFORE INSERT ON pagos FOR EACH ROW
BEGIN
  DECLARE v_saldo DECIMAL(20,2);
  IF NEW.tarjeta_id IS NOT NULL AND LOWER(COALESCE(NEW.estado,'')) = 'pagado' THEN
    SELECT saldo INTO v_saldo FROM tarjetas_simuladas WHERE id = NEW.tarjeta_id FOR UPDATE;
    IF v_saldo IS NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tarjeta no encontrada';
    END IF;
    IF v_saldo < COALESCE(NEW.monto,0) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Saldo insuficiente en la tarjeta';
    END IF;
  END IF;
END;
SQL
        );

        // Drop/create update trigger
        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS pagos_before_update_insuficiente;
SQL
        );

        DB::unprepared(<<<'SQL'
CREATE TRIGGER pagos_before_update_insuficiente
BEFORE UPDATE ON pagos FOR EACH ROW
BEGIN
  DECLARE v_saldo DECIMAL(20,2);
  IF NEW.tarjeta_id IS NOT NULL
     AND LOWER(COALESCE(NEW.estado,'')) = 'pagado'
     AND LOWER(COALESCE(OLD.estado,'')) <> 'pagado' THEN

    SELECT saldo INTO v_saldo FROM tarjetas_simuladas WHERE id = NEW.tarjeta_id FOR UPDATE;
    IF v_saldo IS NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tarjeta no encontrada';
    END IF;
    IF v_saldo < COALESCE(NEW.monto,0) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Saldo insuficiente en la tarjeta';
    END IF;
  END IF;
END;
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS pagos_before_insert_insuficiente;');
        DB::unprepared('DROP TRIGGER IF EXISTS pagos_before_update_insuficiente;');
    }
};
