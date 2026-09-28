<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('renewals', function (Blueprint $table) {
            $table->string('ulid')->nullable()->after('id');
        });

        // Backfill existing rows before making the column unique.
        DB::table('renewals')->whereNull('ulid')->orderBy('id')->cursor()->each(function ($renewal) {
            DB::table('renewals')->where('id', $renewal->id)->update(['ulid' => (string) Str::ulid()]);
        });

        Schema::table('renewals', function (Blueprint $table) {
            $table->string('ulid')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('renewals', function (Blueprint $table) {
            $table->dropColumn('ulid');
        });
    }
};
