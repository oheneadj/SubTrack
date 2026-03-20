<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->date('due_date');
            $table->decimal('provider_cost_usd', 10, 2);
            $table->decimal('client_cost_usd', 10, 2);
            $table->string('payment_status', 50)->default('Pending'); // Enum replacement
            $table->date('payment_received_date')->nullable();
            $table->date('renewal_confirmed_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('renewals');
    }
};
