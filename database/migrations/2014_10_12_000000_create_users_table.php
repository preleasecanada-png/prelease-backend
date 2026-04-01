<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('verified')->nullable();
            $table->integer('verify_status')->default(0);
            $table->string('token')->nullable();
            $table->string('password')->nullable();
            $table->string('picture')->nullable();
            $table->string('phone_no')->nullable();
            $table->string('twitter_id')->nullable();
            $table->string('google_id')->nullable();
            $table->longText('description')->nullable();
            $table->string('role')->default('host');
            $table->string('date_of_birth')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        DB::table('users')->insert([
            'user_name' => 'admin',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role' => 'admin',
            'password' => bcrypt('admin123'),
            'email' => 'admin@gmail.com',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
