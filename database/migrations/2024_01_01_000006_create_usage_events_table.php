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
            $table->string('idempotency_key', 128)->unique();
            $table->integer('quantity')->default(1);
            $table->string('event_type')->default('api_call');
            $table->dateTime('recorded_at');
            $table->timestamps();

            // Composite index for efficient range queries per customer
            $table->index(['customer_id', 'recorded_at']);
            // Index for daily aggregation scans
            $table->index('recorded_at');
        });

        // NOTE: For production with 50M+ rows, add monthly RANGE partitioning on recorded_at:
        // ALTER TABLE usage_events PARTITION BY RANGE (TO_DAYS(recorded_at)) (
        //     PARTITION p2024_01 VALUES LESS THAN (TO_DAYS('2024-02-01')),
        //     PARTITION p2024_02 VALUES LESS THAN (TO_DAYS('2024-03-01')),
        //     ...
        // );
        // This enables fast partition pruning for date-range queries and allows
        // dropping old partitions efficiently without costly DELETE operations.
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_events');
    }
};
