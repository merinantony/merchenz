<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Scale Requirement: Pre-aggregated daily usage table to avoid querying millions of rows during billing
        Schema::create('daily_usage_aggregations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('usage_date');
            $table->bigInteger('total_units')->default(0);
            $table->bigInteger('total_quantity')->default(0);
            $table->timestamps();

            $table->unique(['customer_id', 'usage_date'], 'uq_customer_usage_date_agg');
            $table->index('usage_date', 'idx_daily_agg_usage_date');
            $table->index('customer_id', 'idx_daily_agg_customer_id');
        });

        // Companion/synonym table for backward compatibility across internal services
        Schema::create('daily_usage_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('usage_date');
            $table->bigInteger('total_quantity')->default(0);
            $table->bigInteger('total_units')->default(0);
            $table->timestamps();

            $table->unique(['customer_id', 'usage_date'], 'uq_customer_usage_date_sum');
            $table->index('usage_date', 'idx_daily_sum_usage_date');
            $table->index('customer_id', 'idx_daily_sum_customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_usage_aggregations');
        Schema::dropIfExists('daily_usage_summaries');
    }
};
