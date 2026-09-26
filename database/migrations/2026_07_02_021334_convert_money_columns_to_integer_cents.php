<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Converts all money columns from decimal (dollar amounts) to unsigned integer (cent amounts).
 *
 * Strategy:
 *   1. Multiply existing values by 100 while still in decimal form.
 *   2. Use Schema::table with ->change() (via doctrine/dbal) to alter the column type.
 *
 * Reversible: down() restores DECIMAL and divides by 100.
 */
return new class extends Migration
{
    private array $columns = [
        'subscriptions' => ['purchase_cost_usd', 'renewal_cost_usd'],
        'invoices' => ['subtotal', 'tax_amount', 'total_amount'],
        'invoice_items' => ['unit_price', 'total'],
        'renewals' => ['provider_cost_usd', 'client_cost_usd'],
    ];

    public function up(): void
    {
        foreach ($this->columns as $table => $cols) {
            // Multiply decimal values by 100 first — result is still stored as decimal integer
            $setClauses = implode(', ', array_map(fn ($c) => "`{$c}` = ROUND(`{$c}` * 100)", $cols));
            DB::statement("UPDATE `{$table}` SET {$setClauses}");

            // Change each column to unsignedBigInteger via doctrine/dbal
            Schema::table($table, function (Blueprint $t) use ($cols) {
                foreach ($cols as $col) {
                    $t->unsignedBigInteger($col)->default(0)->change();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->columns as $table => $cols) {
            // Restore column types to decimal first
            Schema::table($table, function (Blueprint $t) use ($cols) {
                foreach ($cols as $col) {
                    $t->decimal($col, 10, 2)->default(0)->change();
                }
            });

            // Divide by 100 to restore original dollar amounts
            $setClauses = implode(', ', array_map(fn ($c) => "`{$c}` = ROUND(`{$c}` / 100, 2)", $cols));
            DB::statement("UPDATE `{$table}` SET {$setClauses}");
        }
    }
};
