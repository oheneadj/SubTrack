<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Back-fill client_id from the existing project relationship before adding the column
            // (done via raw SQL after adding the nullable column below)
            $table->foreignId('client_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->change();
        });

        // Back-fill client_id for all existing subscriptions that have a project
        DB::statement('
            UPDATE subscriptions
            SET client_id = (
                SELECT projects.client_id
                FROM projects
                WHERE projects.id = subscriptions.project_id
            )
            WHERE project_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
            $table->foreignId('project_id')->nullable(false)->change();
        });
    }
};
