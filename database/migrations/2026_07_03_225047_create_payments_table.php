<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('ulid')->unique();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('gateway'); // gateway slug, e.g. 'stripe'
            $table->string('gateway_payment_id')->nullable(); // provider's session/charge/reference ID
            $table->string('method'); // e.g. 'card', 'mobile_money', 'crypto'
            $table->unsignedBigInteger('amount'); // in cents
            $table->string('currency', 10)->default('usd');
            $table->string('status')->default('pending'); // pending|succeeded|failed|refunded
            $table->json('gateway_response')->nullable(); // full raw provider response for reconciliation
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
            $table->index('gateway_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
