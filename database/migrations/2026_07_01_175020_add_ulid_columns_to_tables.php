<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** Tables that need a public ULID column. */
    private array $tables = [
        'users',
        'clients',
        'projects',
        'subscriptions',
        'invoices',
        'providers',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t): void {
                $t->string('ulid', 26)->nullable()->after('id');
            });

            // Back-fill existing rows with a unique ULID before enforcing the constraint.
            DB::table($table)->orderBy('id')->each(function (object $row) use ($table): void {
                DB::table($table)->where('id', $row->id)->update(['ulid' => (string) Str::ulid()]);
            });

            Schema::table($table, function (Blueprint $t) use ($table): void {
                $t->string('ulid', 26)->nullable(false)->unique()->change();
                $t->index('ulid', "{$table}_ulid_index");
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table): void {
                $t->dropIndex("{$table}_ulid_index");
                $t->dropUnique("{$table}_ulid_unique");
                $t->dropColumn('ulid');
            });
        }
    }
};
