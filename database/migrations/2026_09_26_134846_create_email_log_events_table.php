<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_log_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_log_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->json('payload');
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_log_events');
    }
};
