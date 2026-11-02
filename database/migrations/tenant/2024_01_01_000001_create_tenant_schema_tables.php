<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations on the isolated tenant/client database.
     */
    public function up(): void
    {
        // 1. Customers belonging to this tenant
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });

        // 2. Subscriptions
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('plan_id');
            $table->dateTime('started_at');
            $table->dateTime('ends_at');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 3. Subscription Changes (for mid-cycle proration tracking)
        Schema::create('subscription_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('old_plan_id');
            $table->unsignedBigInteger('new_plan_id');
            $table->dateTime('changed_at');
            $table->timestamps();
        });

        // 4. Usage Events (High-throughput metering table)
        Schema::create('usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('event_id')->unique();
            $table->integer('units')->default(1);
            $table->integer('quantity')->default(1);
            $table->dateTime('recorded_at');
            $table->dateTime('timestamp')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'recorded_at']);
        });

        // 5. Daily Usage Aggregations (Pre-computed nightlies)
        Schema::create('daily_usage_aggregations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('usage_date');
            $table->bigInteger('total_units')->default(0);
            $table->bigInteger('total_quantity')->default(0);
            $table->timestamps();

            $table->unique(['customer_id', 'usage_date']);
            $table->index('usage_date');
        });

        // 6. Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('base_amount', 10, 2)->default(0);
            $table->integer('overage_units')->default(0);
            $table->decimal('overage_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('status')->default('draft');
            $table->dateTime('generated_at')->nullable();
            $table->timestamps();
        });

        // 7. Invoice Line Items
        Schema::create('invoice_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('plan_id');
            $table->date('segment_start');
            $table->date('segment_end');
            $table->decimal('base_amount', 10, 2)->default(0);
            $table->integer('usage_units')->default(0);
            $table->integer('included_units')->default(0);
            $table->integer('overage_units')->default(0);
            $table->decimal('overage_amount', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2)->default(0);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_line_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('daily_usage_aggregations');
        Schema::dropIfExists('usage_events');
        Schema::dropIfExists('subscription_changes');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('customers');
    }
};

