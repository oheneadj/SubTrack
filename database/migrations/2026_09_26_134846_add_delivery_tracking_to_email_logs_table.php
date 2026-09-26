<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->string('message_id')->nullable()->unique()->after('mailable_class');
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('bounced_at')->nullable()->after('delivered_at');
            $table->timestamp('opened_at')->nullable()->after('bounced_at');
            $table->timestamp('clicked_at')->nullable()->after('opened_at');
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropColumn(['message_id', 'delivered_at', 'bounced_at', 'opened_at', 'clicked_at']);
        });
    }
};
