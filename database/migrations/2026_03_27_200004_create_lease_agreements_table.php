<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lease_agreements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('renter_id');
            $table->unsignedBigInteger('landlord_id');
            $table->unsignedBigInteger('rental_application_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();

            $table->enum('lease_type', ['3_month', '6_month']);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('monthly_rent', 10, 2);
            $table->decimal('total_rent', 10, 2);
            $table->decimal('support_fee', 10, 2)->default(100.00);
            $table->decimal('commission_fee', 10, 2)->default(0);
            $table->decimal('insurance_fee', 10, 2)->default(0);
            $table->decimal('total_payable', 10, 2);

            $table->enum('status', [
                'draft',
                'pending_renter_signature',
                'pending_landlord_signature',
                'active',
                'expired',
                'terminated',
                'cancelled'
            ])->default('draft');

            $table->timestamp('renter_signed_at')->nullable();
            $table->timestamp('landlord_signed_at')->nullable();
            $table->string('lease_document_path')->nullable();
            $table->text('terms')->nullable();
            $table->text('special_conditions')->nullable();
            $table->timestamps();

            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->foreign('renter_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('landlord_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('rental_application_id')->references('id')->on('rental_applications')->onDelete('set null');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_agreements');
    }
};
