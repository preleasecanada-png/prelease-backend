<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_chats', function (Blueprint $table) {
            $table->boolean('deleted_by_receiver')->default(false)->after('read_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_chats', function (Blueprint $table) {
            $table->dropColumn('deleted_by_receiver');
        });
    }
};
