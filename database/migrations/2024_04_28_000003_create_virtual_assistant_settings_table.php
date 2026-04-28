<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('virtual_assistant_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type')->default('string');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('virtual_assistant_settings')->insert([
            [
                'key' => 'ai_provider',
                'value' => 'openai',
                'type' => 'string',
                'description' => 'AI provider: openai, anthropic, etc.'
            ],
            [
                'key' => 'ai_model',
                'value' => 'gpt-4',
                'type' => 'string',
                'description' => 'AI model to use'
            ],
            [
                'key' => 'system_prompt',
                'value' => 'You are a helpful assistant for Prelease Canada, a platform connecting renters and landlords. You help users with questions about properties, rentals, leases, and account management.',
                'type' => 'text',
                'description' => 'System prompt for the AI assistant'
            ],
            [
                'key' => 'max_tokens',
                'value' => '1000',
                'type' => 'integer',
                'description' => 'Maximum tokens for AI responses'
            ],
            [
                'key' => 'temperature',
                'value' => '0.7',
                'type' => 'float',
                'description' => 'Temperature for AI responses'
            ],
            [
                'key' => 'enable_voice',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enable voice/call support'
            ],
            [
                'key' => 'enable_sms',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enable SMS support'
            ],
            [
                'key' => 'business_hours_start',
                'value' => '09:00',
                'type' => 'string',
                'description' => 'Business hours start time'
            ],
            [
                'key' => 'business_hours_end',
                'value' => '18:00',
                'type' => 'string',
                'description' => 'Business hours end time'
            ],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('virtual_assistant_settings');
    }
};
