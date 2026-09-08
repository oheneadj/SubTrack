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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('ulid')->unique();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            // Denormalized: keeps receipt history stable even if the subscription is later reassigned.
            $table->foreignId('client_id')->constrained();
            $table->string('receipt_number')->unique();
            $table->unsignedBigInteger('amount_usd'); // minor units (cents)
            $table->date('issued_date');
            $table->text('notes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'issued_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
