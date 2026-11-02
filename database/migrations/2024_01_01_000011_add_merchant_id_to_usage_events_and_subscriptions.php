<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to add row-level tenancy foreign keys.
     */
    public function up(): void
    {
        // 1. Add merchant_id to usage_events
        if (!Schema::hasColumn('usage_events', 'merchant_id')) {
            Schema::table('usage_events', function (Blueprint $table) {
                $table->foreignId('merchant_id')->nullable()->after('id')->constrained('merchants')->cascadeOnDelete();
                $table->index('merchant_id', 'idx_usage_events_merchant_id');
            });
        }

        // 2. Add merchant_id to subscriptions
        if (!Schema::hasColumn('subscriptions', 'merchant_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->foreignId('merchant_id')->nullable()->after('id')->constrained('merchants')->cascadeOnDelete();
                $table->index('merchant_id', 'idx_subscriptions_merchant_id');
            });
        }

        // 3. Add merchant_id to daily_usage_aggregations
        if (!Schema::hasColumn('daily_usage_aggregations', 'merchant_id')) {
            Schema::table('daily_usage_aggregations', function (Blueprint $table) {
                $table->foreignId('merchant_id')->nullable()->after('id')->constrained('merchants')->cascadeOnDelete();
                $table->index('merchant_id', 'idx_daily_agg_merchant_id');
            });
        }

        // 4. Add merchant_id to invoices
        if (!Schema::hasColumn('invoices', 'merchant_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('merchant_id')->nullable()->after('id')->constrained('merchants')->cascadeOnDelete();
                $table->index('merchant_id', 'idx_invoices_merchant_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['merchant_id']);
            $table->dropColumn('merchant_id');
        });

        Schema::table('daily_usage_aggregations', function (Blueprint $table) {
            $table->dropForeign(['merchant_id']);
            $table->dropColumn('merchant_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['merchant_id']);
            $table->dropColumn('merchant_id');
        });

        Schema::table('usage_events', function (Blueprint $table) {
            $table->dropForeign(['merchant_id']);
            $table->dropColumn('merchant_id');
        });
    }
};

