<?php

namespace Tigusigalpa\GigaChat\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array models()
 * @method static array chat(array $messages, array $options = [])
 * @method static \Generator|void chatStream(array $messages, array $options = [], ?callable $onEvent = null)
 * @method static array generateImage(string $prompt, array $options = [])
 * @method static string downloadImage(string $fileId)
 * @method static array createImage(string $prompt, array $options = [])
 * 
 * @see \Tigusigalpa\GigaChat\GigaChatClient
 */
class GigaChat extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'gigachat';
    }

    /**
     * Quick chat helper
     */
    public static function ask(string $question, array $options = []): string
    {
        $messages = [['role' => 'user', 'content' => $question]];
        $response = static::chat($messages, $options);
        return GigaChatHelper::extractContent($response) ?? '';
    }

    /**
     * Chat with system prompt
     */
    public static function askWithContext(string $systemPrompt, string $question, array $options = []): string
    {
        $messages = GigaChatHelper::conversation($systemPrompt, $question);
        $response = static::chat($messages, $options);
        return GigaChatHelper::extractContent($response) ?? '';
    }

    /**
     * Continue conversation
     */
    public static function continueChat(array $conversation, string $newMessage, array $options = []): array
    {
        $messages = array_merge($conversation, [GigaChatHelper::userMessage($newMessage)]);
        $response = static::chat($messages, $options);
        
        if ($content = GigaChatHelper::extractContent($response)) {
            $messages[] = GigaChatHelper::assistantMessage($content);
        }
        
        return $messages;
    }

    /**
     * Quick image generation helper
     */
    public static function drawImage(string $description, array $options = []): array
    {
        $prompt = "Нарисуй " . $description;
        return static::createImage($prompt, $options);
    }

    /**
     * Generate image with artist style
     */
    public static function drawImageInStyle(string $description, string $artistStyle, array $options = []): array
    {
        $prompt = "Нарисуй " . $description;
        $options['system_message'] = "Ты — " . $artistStyle;
        return static::createImage($prompt, $options);
    }

    /**
     * Extract image ID from GigaChat response content
     */
    public static function extractImageId(string $content): ?string
    {
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
            return $matches[1];
        }
        return null;
    }
}