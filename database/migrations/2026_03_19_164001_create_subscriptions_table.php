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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('service_type', 50); // Enum replacement
            $table->string('provider');
            $table->string('domain_name')->nullable();
            $table->date('purchase_date');
            $table->date('expiry_date');
            $table->decimal('purchase_cost_usd', 10, 2);
            $table->decimal('renewal_cost_usd', 10, 2);
            $table->string('status', 50)->default('Active'); // Enum replacement
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
