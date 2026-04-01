<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('reviewer_id');
            $table->unsignedBigInteger('reviewee_id');
            $table->unsignedBigInteger('lease_agreement_id')->nullable();

            $table->enum('review_type', ['renter_to_landlord', 'landlord_to_renter', 'renter_to_property']);
            $table->tinyInteger('rating')->unsigned();
            $table->text('comment')->nullable();

            $table->tinyInteger('cleanliness_rating')->unsigned()->nullable();
            $table->tinyInteger('communication_rating')->unsigned()->nullable();
            $table->tinyInteger('value_rating')->unsigned()->nullable();
            $table->tinyInteger('location_rating')->unsigned()->nullable();

            $table->enum('status', ['pending', 'published', 'flagged', 'removed'])->default('pending');
            $table->timestamps();

            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->foreign('reviewer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewee_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('lease_agreement_id')->references('id')->on('lease_agreements')->onDelete('set null');

            $table->unique(['property_id', 'reviewer_id', 'review_type'], 'unique_review');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
