<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->longText('description')->nullable();
            // $table->string('bedroom')->nullable();
            // $table->string('bath_room')->nullable();
            // $table->string('bath_room_no')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('guest')->nullable();
            $table->string('step')->nullable();
            $table->string('describe_your_place')->nullable();
            $table->string('country')->nullable();
            $table->string('street_address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('state')->nullable();
            $table->string('how_many_guests')->nullable();
            $table->string('how_many_bedrooms')->nullable();
            $table->string('how_many_bathroom')->nullable();
            $table->string('bathroom_avaiable_private_and_attached')->nullable();
            $table->string('bathroom_avaiable_dedicated')->nullable();
            $table->string('bathroom_avaiable_shared')->nullable();
            $table->string('who_else_there')->nullable();
            $table->string('confirm_reservation')->nullable();
            $table->string('set_your_price')->nullable();
            $table->string('guest_service_fee')->nullable();
            $table->string('new_listing_promotion')->nullable();
            $table->string('monthly_discount')->nullable();
            $table->string('yearly_discount')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
