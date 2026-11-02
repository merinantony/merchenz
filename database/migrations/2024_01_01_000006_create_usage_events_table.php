<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            
            // Idempotency identifier: event_id (prompt spec) and idempotency_key (backward-compat)
            $table->string('event_id', 128)->nullable()->unique();
            $table->string('idempotency_key', 128)->nullable()->unique();
            
            // Usage quantities
            $table->bigInteger('units')->default(1);
            $table->bigInteger('quantity')->default(1);
            
            $table->string('event_type', 50)->default('api_call');
            
            // Event timestamps
            $table->dateTime('timestamp')->nullable();
            $table->dateTime('recorded_at')->nullable();
            
            $table->timestamps();

            // -------------------------------------------------------------
            // Scale Requirement: Indexing for 50L+ (5 Million+) rows
            // -------------------------------------------------------------
            // 1. Composite index for customer-scoped range queries during billing cycles:
            //    WHERE customer_id = ? AND timestamp BETWEEN ? AND ?
            $table->index(['customer_id', 'timestamp'], 'idx_usage_events_customer_timestamp');
            $table->index(['customer_id', 'recorded_at'], 'idx_usage_events_customer_recorded');

            // 2. Index for nightly chunked aggregation jobs querying specific date ranges:
            $table->index('timestamp', 'idx_usage_events_timestamp');
            $table->index('recorded_at', 'idx_usage_events_recorded_at');

            // 3. Index on customer_id foreign key for cascade/lookups
            $table->index('customer_id', 'idx_usage_events_customer_id');
        });

        // NOTE FOR HIGH SCALE (50L+ / 50M+ rows in MySQL / PostgreSQL):
        // In production MySQL 8.0+, usage_events should be partitioned by RANGE on timestamp/recorded_at:
        // ALTER TABLE usage_events PARTITION BY RANGE (TO_DAYS(timestamp)) (
        //     PARTITION p2026_10 VALUES LESS THAN (TO_DAYS('2026-11-01')),
        //     PARTITION p2026_11 VALUES LESS THAN (TO_DAYS('2026-12-01')),
        //     PARTITION p_future VALUES LESS THAN MAXVALUE
        // );
        // This enables partition pruning so range scans only read the active partition,
        // and allows instant archival/dropping of old partitions via ALTER TABLE DROP PARTITION.
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_events');
    }
};
