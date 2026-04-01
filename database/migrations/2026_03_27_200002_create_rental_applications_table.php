<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('renter_id');
            $table->unsignedBigInteger('landlord_id');

            $table->text('cover_letter')->nullable();
            $table->string('employment_status')->nullable();
            $table->decimal('monthly_income', 10, 2)->nullable();
            $table->string('current_address')->nullable();
            $table->string('reason_for_moving')->nullable();
            $table->integer('number_of_occupants')->default(1);
            $table->boolean('has_pets')->default(false);
            $table->string('pet_details')->nullable();

            $table->date('desired_move_in')->nullable();
            $table->string('desired_lease_duration')->nullable();

            $table->string('reference_name_1')->nullable();
            $table->string('reference_phone_1')->nullable();
            $table->string('reference_email_1')->nullable();
            $table->string('reference_name_2')->nullable();
            $table->string('reference_phone_2')->nullable();
            $table->string('reference_email_2')->nullable();

            $table->enum('status', [
                'draft',
                'submitted',
                'under_review',
                'approved',
                'rejected',
                'withdrawn'
            ])->default('draft');

            $table->text('landlord_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->foreign('renter_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('landlord_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_applications');
    }
};
