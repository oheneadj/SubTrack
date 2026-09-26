<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['mail_templates', 'dashboard_activity_logs'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('ulid', 26)->nullable()->after('id');
            });

            DB::table($table)->orderBy('id')->each(function ($row) use ($table) {
                DB::table($table)->where('id', $row->id)->update(['ulid' => (string) Str::ulid()]);
            });

            Schema::table($table, function (Blueprint $table) {
                $table->string('ulid', 26)->nullable(false)->unique()->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['mail_templates', 'dashboard_activity_logs'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropUnique(['ulid']);
                $table->dropColumn('ulid');
            });
        }
    }
};
