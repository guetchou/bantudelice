<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("
                CREATE TRIGGER gepay_ledger_prevent_update
                BEFORE UPDATE ON gepay_ledger_entries
                FOR EACH ROW
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'GePayLedgerEntry is immutable — UPDATE is forbidden'
            ");

            DB::statement("
                CREATE TRIGGER gepay_ledger_prevent_delete
                BEFORE DELETE ON gepay_ledger_entries
                FOR EACH ROW
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'GePayLedgerEntry is immutable — DELETE is forbidden'
            ");
        } elseif ($driver === 'sqlite') {
            DB::statement("
                CREATE TRIGGER gepay_ledger_prevent_update
                BEFORE UPDATE ON gepay_ledger_entries
                BEGIN
                    SELECT RAISE(ABORT, 'GePayLedgerEntry is immutable — UPDATE is forbidden');
                END
            ");

            DB::statement("
                CREATE TRIGGER gepay_ledger_prevent_delete
                BEFORE DELETE ON gepay_ledger_entries
                BEGIN
                    SELECT RAISE(ABORT, 'GePayLedgerEntry is immutable — DELETE is forbidden');
                END
            ");
        }
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS gepay_ledger_prevent_update');
        DB::statement('DROP TRIGGER IF EXISTS gepay_ledger_prevent_delete');
    }
};
