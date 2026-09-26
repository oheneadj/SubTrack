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
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('ulid', 26)->nullable()->after('id');
        });

        DB::table('activity_logs')->orderBy('id')->each(function ($row) {
            DB::table('activity_logs')->where('id', $row->id)->update(['ulid' => (string) Str::ulid()]);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('ulid', 26)->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropUnique(['ulid']);
            $table->dropColumn('ulid');
        });
    }
};
