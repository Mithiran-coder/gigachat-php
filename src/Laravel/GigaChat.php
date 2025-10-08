<?php

namespace Tigusigalpa\GigaChat\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array models()
 * @method static array chat(array $messages, array $options = [])
 * @method static \Generator|void chatStream(array $messages, array $options = [], ?callable $onEvent = null)
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
}