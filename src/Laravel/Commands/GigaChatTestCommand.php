<?php

namespace Tigusigalpa\GigaChat\Laravel\Commands;

use Illuminate\Console\Command;
use Tigusigalpa\GigaChat\Exceptions\GigaChatException;
use Tigusigalpa\GigaChat\Laravel\GigaChat;

class GigaChatTestCommand extends Command
{
    protected $signature = 'gigachat:test {message?}';
    protected $description = 'Test GigaChat API connection and send a test message';

    public function handle()
    {
        $this->info('🚀 Testing GigaChat API connection...');

        try {
            // Test models endpoint
            $this->info('📋 Fetching available models...');
            $models = GigaChat::models();
            
            if (isset($models['data']) && is_array($models['data'])) {
                $this->info('✅ Available models:');
                foreach ($models['data'] as $model) {
                    $this->line("   - {$model['id']}");
                }
            }

            // Test chat endpoint
            $message = $this->argument('message') ?? 'Привет! Это тестовое сообщение.';
            $this->info("💬 Sending test message: {$message}");

            $messages = [
                ['role' => 'user', 'content' => $message]
            ];

            $response = GigaChat::chat($messages, [
                'temperature' => 0.7,
                'max_tokens' => 100
            ]);

            if (isset($response['choices'][0]['message']['content'])) {
                $this->info('✅ Response received:');
                $this->line($response['choices'][0]['message']['content']);
            }

            $this->info('🎉 GigaChat API test completed successfully!');

        } catch (GigaChatException $e) {
            $this->error('❌ GigaChat API Error: ' . $e->getMessage());
            return 1;
        } catch (\Exception $e) {
            $this->error('❌ General Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
