<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('base_price', 12, 2);
            $table->integer('billing_cycle')->default(30);
            $table->integer('billing_cycle_days')->default(30);
            $table->bigInteger('included_usage_units')->default(0);
            $table->bigInteger('included_units')->default(0);
            $table->decimal('overage_rate_per_unit', 10, 4);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('merchant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
