<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->integer('installment_number')->nullable()->after('payment_type');
            $table->integer('total_installments')->nullable()->after('installment_number');
            $table->date('due_date')->nullable()->after('total_installments');
            $table->string('installment_group')->nullable()->after('due_date');
        });

        Schema::table('lease_agreements', function (Blueprint $table) {
            $table->enum('payment_plan', ['full_upfront', 'monthly'])->default('full_upfront')->after('total_payable');
            $table->boolean('landlord_allows_monthly')->default(false)->after('payment_plan');
            $table->decimal('monthly_support_fee', 8, 2)->default(100.00)->after('landlord_allows_monthly');
            $table->decimal('monthly_commission_rate', 5, 4)->default(0.0500)->after('monthly_support_fee');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['installment_number', 'total_installments', 'due_date', 'installment_group']);
        });

        Schema::table('lease_agreements', function (Blueprint $table) {
            $table->dropColumn(['payment_plan', 'landlord_allows_monthly', 'monthly_support_fee', 'monthly_commission_rate']);
        });
    }
};
