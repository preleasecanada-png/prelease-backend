<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_reference')->unique();
            $table->unsignedBigInteger('lease_agreement_id')->nullable();
            $table->unsignedBigInteger('renter_id');
            $table->unsignedBigInteger('landlord_id');
            $table->unsignedBigInteger('property_id');

            $table->decimal('rent_amount', 10, 2);
            $table->decimal('support_fee', 10, 2)->default(100.00);
            $table->decimal('commission_fee', 10, 2)->default(0);
            $table->decimal('insurance_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);

            $table->enum('payment_type', ['full_upfront', 'monthly']);
            $table->enum('payment_method', ['credit_card', 'debit_card', 'bank_transfer', 'e_transfer'])->nullable();
            $table->string('transaction_id')->nullable();

            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'refunded',
                'partially_refunded'
            ])->default('pending');

            $table->enum('landlord_payout_status', [
                'pending',
                'processing',
                'completed',
                'failed'
            ])->default('pending');
            $table->decimal('landlord_payout_amount', 10, 2)->nullable();
            $table->timestamp('landlord_paid_at')->nullable();

            $table->enum('insurance_payout_status', [
                'pending',
                'processing',
                'completed',
                'not_applicable'
            ])->default('not_applicable');
            $table->decimal('insurance_payout_amount', 10, 2)->nullable();

            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('lease_agreement_id')->references('id')->on('lease_agreements')->onDelete('set null');
            $table->foreign('renter_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('landlord_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
