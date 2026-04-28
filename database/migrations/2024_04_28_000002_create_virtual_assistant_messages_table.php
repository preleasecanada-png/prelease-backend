<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('virtual_assistant_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('virtual_assistant_conversations')->onDelete('cascade');
            $table->enum('sender', ['user', 'assistant', 'system']);
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_ai_generated')->default(false);
            $table->string('model_used')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('virtual_assistant_messages');
    }
};
