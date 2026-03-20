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
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('client_id')->constrained()->cascadeOnDelete();
            }
            if (!Schema::hasColumn('invoices', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('due_date');
            }
            if (!Schema::hasColumn('invoices', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0)->after('tax_rate');
            }
            if (!Schema::hasColumn('invoices', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->default(0)->after('tax_amount');
            }
            if (!Schema::hasColumn('invoices', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->default(0)->after('subtotal');
            }
            
            // Cleanup legacy
            if (Schema::hasColumn('invoices', 'subtotal_usd')) {
                $table->dropColumn('subtotal_usd');
            }
            if (Schema::hasColumn('invoices', 'total_usd')) {
                $table->dropColumn('total_usd');
            }
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_items', 'quantity')) {
                $table->decimal('quantity', 10, 2)->default(1)->after('description');
            }
            if (!Schema::hasColumn('invoice_items', 'unit_price')) {
                $table->decimal('unit_price', 10, 2)->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('invoice_items', 'total')) {
                $table->decimal('total', 10, 2)->default(0)->after('unit_price');
            }
            
            // Cleanup legacy
            if (Schema::hasColumn('invoice_items', 'amount_usd')) {
                $table->dropColumn('amount_usd');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Re-adding isn't strictly necessary for a quick dev fix but good practice
            $table->decimal('subtotal_usd', 10, 2)->default(0);
            $table->decimal('total_usd', 10, 2)->default(0);
            
            $table->dropForeign(['project_id']);
            $table->dropColumn(['project_id', 'tax_rate', 'tax_amount', 'subtotal', 'total_amount']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('amount_usd', 10, 2)->default(0);
            $table->dropColumn(['quantity', 'unit_price', 'total']);
        });
    }
};
