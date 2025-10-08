<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tigusigalpa\GigaChat\Laravel\GigaChat;
use Tigusigalpa\GigaChat\Laravel\GigaChatHelper;
use Tigusigalpa\GigaChat\Exceptions\GigaChatException;

class ChatController extends Controller
{
    /**
     * Simple chat endpoint
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1|max:2000',
        ]);

        try {
            $response = GigaChat::ask($request->input('message'), [
                'temperature' => $request->input('temperature', 0.7),
                'max_tokens' => $request->input('max_tokens', 500),
            ]);

            return response()->json([
                'success' => true,
                'response' => $response
            ]);

        } catch (GigaChatException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Chat with conversation history
     */
    public function conversation(Request $request): JsonResponse
    {
        $request->validate([
            'messages' => 'required|array',
            'messages.*.role' => 'required|in:user,system,assistant',
            'messages.*.content' => 'required|string',
        ]);

        try {
            $messages = $request->input('messages');
            
            // Validate messages format
            foreach ($messages as $message) {
                GigaChatHelper::validateMessage($message);
            }

            $response = GigaChat::chat($messages);
            $content = GigaChatHelper::extractContent($response);
            $usage = GigaChatHelper::extractUsage($response);

            return response()->json([
                'success' => true,
                'response' => $content,
                'usage' => $usage,
                'conversation' => array_merge($messages, [
                    GigaChatHelper::assistantMessage($content)
                ])
            ]);

        } catch (GigaChatException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Streaming chat endpoint
     */
    public function stream(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $messages = [GigaChatHelper::userMessage($request->input('message'))];

        return response()->stream(function () use ($messages) {
            echo "data: " . json_encode(['type' => 'start']) . "\n\n";
            flush();

            try {
                GigaChat::chatStream($messages, [], function($event, $error) {
                    if ($error) {
                        echo "data: " . json_encode([
                            'type' => 'error',
                            'error' => $error
                        ]) . "\n\n";
                        return;
                    }

                    if ($event === '[DONE]') {
                        echo "data: " . json_encode(['type' => 'done']) . "\n\n";
                        return;
                    }

                    if (isset($event['choices'][0]['delta']['content'])) {
                        echo "data: " . json_encode([
                            'type' => 'content',
                            'content' => $event['choices'][0]['delta']['content']
                        ]) . "\n\n";
                    }

                    flush();
                });

            } catch (GigaChatException $e) {
                echo "data: " . json_encode([
                    'type' => 'error',
                    'error' => $e->getMessage()
                ]) . "\n\n";
            }

        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Content generation endpoint
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:article,summary,tags,translation',
            'content' => 'required|string',
            'params' => 'nullable|array',
        ]);

        $type = $request->input('type');
        $content = $request->input('content');
        $params = $request->input('params', []);

        try {
            $result = match($type) {
                'article' => $this->generateArticle($content, $params),
                'summary' => $this->generateSummary($content, $params),
                'tags' => $this->generateTags($content, $params),
                'translation' => $this->translateContent($content, $params),
            };

            return response()->json([
                'success' => true,
                'result' => $result
            ]);

        } catch (GigaChatException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateArticle(string $topic, array $params): string
    {
        $length = $params['length'] ?? 'средней длины';
        $style = $params['style'] ?? 'информационный';
        
        $prompt = "Напиши {$length} статью в {$style} стиле на тему: {$topic}";
        
        return GigaChat::askWithContext(
            'Ты профессиональный копирайтер. Пиши качественные и интересные статьи.',
            $prompt,
            ['temperature' => 0.8, 'max_tokens' => 1500]
        );
    }

    private function generateSummary(string $content, array $params): string
    {
        $maxLength = $params['max_length'] ?? 200;
        
        $prompt = "Создай краткое изложение (не более {$maxLength} слов) следующего текста:\n\n{$content}";
        
        return GigaChat::ask($prompt, [
            'temperature' => 0.3,
            'max_tokens' => $maxLength * 2
        ]);
    }

    private function generateTags(string $content, array $params): array
    {
        $maxTags = $params['max_tags'] ?? 5;
        
        $prompt = "Создай до {$maxTags} релевантных тегов для следующего контента. Верни только теги через запятую:\n\n{$content}";
        
        $response = GigaChat::ask($prompt, [
            'temperature' => 0.5,
            'max_tokens' => 100
        ]);

        return array_map('trim', explode(',', $response));
    }

    private function translateContent(string $content, array $params): string
    {
        $targetLang = $params['target_language'] ?? 'английский';
        
        $prompt = "Переведи следующий текст на {$targetLang} язык:\n\n{$content}";
        
        return GigaChat::ask($prompt, [
            'temperature' => 0.2,
            'max_tokens' => strlen($content) * 2
        ]);
    }
}
