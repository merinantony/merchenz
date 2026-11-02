<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->string('database_name')->nullable()->after('address');
            $table->string('status')->default('active')->after('database_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('email'); // super_admin, admin, user
            $table->foreignId('merchant_id')->nullable()->after('role')->constrained('merchants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['merchant_id']);
            $table->dropColumn(['role', 'merchant_id']);
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['company_name', 'phone', 'address', 'database_name', 'status']);
        });
    }
};
