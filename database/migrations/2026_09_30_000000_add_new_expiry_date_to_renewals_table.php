<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('renewals', function (Blueprint $table) {
            // The expiry date this renewal will roll the subscription to
            // once it's paid and ProcessRenewalAction is run — kept separate
            // from renewal_confirmed_date, which now marks when that roll
            // actually happened (payment and processing are decoupled).
            $table->date('new_expiry_date')->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('renewals', function (Blueprint $table) {
            $table->dropColumn('new_expiry_date');
        });
    }
};
