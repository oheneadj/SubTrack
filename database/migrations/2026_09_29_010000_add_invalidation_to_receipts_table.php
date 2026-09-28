<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->timestamp('invalidated_at')->nullable()->after('notes');
            $table->string('invalidated_reason')->nullable()->after('invalidated_at');
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn(['invalidated_at', 'invalidated_reason']);
        });
    }
};
