<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('background_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_application_id')->constrained()->onDelete('cascade');
            $table->foreignId('renter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('landlord_id')->constrained('users')->onDelete('cascade');
            $table->enum('check_type', ['credit', 'criminal', 'both'])->default('credit');
            $table->enum('status', ['pending_consent', 'consent_given', 'in_progress', 'completed', 'failed', 'declined'])->default('pending_consent');
            $table->boolean('renter_consent')->default(false);
            $table->timestamp('consent_given_at')->nullable();
            $table->integer('credit_score')->nullable();
            $table->enum('credit_rating', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->text('credit_summary')->nullable();
            $table->enum('criminal_result', ['clear', 'flagged'])->nullable();
            $table->text('criminal_summary')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('fee_amount', 8, 2)->default(25.00);
            $table->enum('fee_paid_by', ['renter', 'landlord'])->default('renter');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('background_checks');
    }
};
