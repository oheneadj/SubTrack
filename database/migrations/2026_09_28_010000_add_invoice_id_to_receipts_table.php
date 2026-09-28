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
            $table->foreignId('invoice_id')->nullable()->after('subscription_id')->constrained()->cascadeOnDelete();
            $table->index(['invoice_id', 'issued_date']);
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->foreignId('subscription_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropIndex(['invoice_id', 'issued_date']);
            $table->dropColumn('invoice_id');
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->foreignId('subscription_id')->nullable(false)->change();
        });
    }
};
