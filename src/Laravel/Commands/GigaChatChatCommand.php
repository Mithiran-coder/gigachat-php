<?php

namespace Tigusigalpa\GigaChat\Laravel\Commands;

use Illuminate\Console\Command;
use Tigusigalpa\GigaChat\Exceptions\GigaChatException;
use Tigusigalpa\GigaChat\Laravel\GigaChat;

class GigaChatChatCommand extends Command
{
    protected $signature = 'gigachat:chat 
                           {message : The message to send to GigaChat}
                           {--model= : Model to use (default: from config)}
                           {--temperature=0.7 : Temperature for generation}
                           {--max-tokens=500 : Maximum tokens to generate}
                           {--stream : Use streaming mode}';
    
    protected $description = 'Send a message to GigaChat and get response';

    public function handle()
    {
        $message = $this->argument('message');
        $model = $this->option('model');
        $temperature = (float) $this->option('temperature');
        $maxTokens = (int) $this->option('max-tokens');
        $stream = $this->option('stream');

        $this->info("💬 Sending message to GigaChat...");
        $this->line("Message: {$message}");

        $messages = [
            ['role' => 'user', 'content' => $message]
        ];

        $options = [
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];

        if ($model) {
            $options['model'] = $model;
        }

        try {
            if ($stream) {
                $this->info('🔄 Streaming response:');
                $this->newLine();
                
                GigaChat::chatStream($messages, $options, function($event, $error) {
                    if ($error) {
                        $this->error("Error: {$error}");
                        return;
                    }
                    
                    if ($event === '[DONE]') {
                        $this->newLine();
                        $this->info('✅ Stream completed');
                        return;
                    }
                    
                    if (isset($event['choices'][0]['delta']['content'])) {
                        $this->getOutput()->write($event['choices'][0]['delta']['content']);
                    }
                });
            } else {
                $response = GigaChat::chat($messages, $options);
                
                if (isset($response['choices'][0]['message']['content'])) {
                    $this->info('✅ Response:');
                    $this->line($response['choices'][0]['message']['content']);
                    
                    if (isset($response['usage'])) {
                        $this->newLine();
                        $this->info('📊 Usage statistics:');
                        $this->line("Prompt tokens: {$response['usage']['prompt_tokens']}");
                        $this->line("Completion tokens: {$response['usage']['completion_tokens']}");
                        $this->line("Total tokens: {$response['usage']['total_tokens']}");
                    }
                }
            }

        } catch (GigaChatException $e) {
            $this->error('❌ GigaChat Error: ' . $e->getMessage());
            return 1;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
