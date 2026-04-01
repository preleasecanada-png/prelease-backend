<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_insurance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lease_agreement_id');
            $table->unsignedBigInteger('renter_id');
            $table->string('policy_number')->nullable();
            $table->string('provider')->nullable();
            $table->decimal('premium_amount', 10, 2);
            $table->date('coverage_start');
            $table->date('coverage_end');
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled', 'claimed'])->default('pending');
            $table->text('coverage_details')->nullable();
            $table->timestamps();

            $table->foreign('lease_agreement_id')->references('id')->on('lease_agreements')->onDelete('cascade');
            $table->foreign('renter_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_insurance');
    }
};
