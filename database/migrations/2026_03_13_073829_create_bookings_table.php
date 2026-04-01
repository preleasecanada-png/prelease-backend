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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('renter_id');
            $table->unsignedBigInteger('landlord_id');

            $table->date('move_in_date');
            $table->date('move_out_date');

            $table->integer('guests')->default(1);
            $table->integer('adult_count')->default(0);
            $table->integer('child_count')->default(0);
            $table->integer('infront_count')->default(0);
            $table->integer('pets_count')->default(0);

            $table->decimal('price_agreed', 10, 2)->nullable();

            $table->enum('status', [
                'pending',
                'negotiating',
                'payment_pending',
                'paid',
                'cancelled'
            ])->default('pending');
            $table->timestamps();

            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->foreign('renter_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('landlord_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
