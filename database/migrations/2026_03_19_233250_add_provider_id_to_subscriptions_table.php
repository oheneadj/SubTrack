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
        // 1. Add the foreign key column (idempotent check for SQLite partial failures)
        if (!\Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'provider_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->foreignId('provider_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
            });
        }

        // 2. Migrate existing data
        $subscriptions = \Illuminate\Support\Facades\DB::table('subscriptions')->get();
        foreach ($subscriptions as $sub) {
            // Note: $sub->provider still exists conceptually in the row because we haven't dropped it
            if (isset($sub->provider) && $sub->provider) {
                $provider = \Illuminate\Support\Facades\DB::table('providers')->where('name', $sub->provider)->first();
                if (!$provider) {
                    $providerId = \Illuminate\Support\Facades\DB::table('providers')->insertGetId([
                        'name' => $sub->provider,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $providerId = $provider->id;
                }
                \Illuminate\Support\Facades\DB::table('subscriptions')->where('id', $sub->id)->update(['provider_id' => $providerId]);
            }
        }

        // 3. Drop old string column
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: restoring data down safely is tricky but we rebuild the string column
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('provider')->after('project_id')->nullable();
        });

        $subscriptions = \Illuminate\Support\Facades\DB::table('subscriptions')->get();
        foreach ($subscriptions as $sub) {
            if ($sub->provider_id) {
                $provider = \Illuminate\Support\Facades\DB::table('providers')->where('id', $sub->provider_id)->first();
                if ($provider) {
                    \Illuminate\Support\Facades\DB::table('subscriptions')->where('id', $sub->id)->update(['provider' => $provider->name]);
                }
            }
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['provider_id']);
            $table->dropColumn('provider_id');
        });
    }
};
