<?php

namespace Tigusigalpa\GigaChat\Laravel\Traits;

use Tigusigalpa\GigaChat\Laravel\GigaChat;
use Tigusigalpa\GigaChat\Laravel\GigaChatHelper;

trait HasGigaChat
{
    /**
     * Send a message to GigaChat
     */
    public function askGigaChat(string $message, array $options = []): string
    {
        return GigaChat::ask($message, $options);
    }

    /**
     * Send a message with context to GigaChat
     */
    public function askGigaChatWithContext(string $systemPrompt, string $message, array $options = []): string
    {
        return GigaChat::askWithContext($systemPrompt, $message, $options);
    }

    /**
     * Generate content based on model attributes
     */
    public function generateContent(string $prompt, array $attributes = [], array $options = []): string
    {
        $context = $this->buildContextFromAttributes($attributes);
        $fullPrompt = $context ? "{$context}\n\n{$prompt}" : $prompt;
        
        return GigaChat::ask($fullPrompt, $options);
    }

    /**
     * Summarize model content
     */
    public function summarize(string $field = 'content', array $options = []): string
    {
        $content = $this->getAttribute($field);
        
        if (!$content) {
            return '';
        }

        $prompt = "Кратко изложи основные моменты следующего текста:\n\n{$content}";
        
        return GigaChat::ask($prompt, array_merge([
            'temperature' => 0.3,
            'max_tokens' => 300
        ], $options));
    }

    /**
     * Generate tags for model content
     */
    public function generateTags(string $field = 'content', int $maxTags = 5, array $options = []): array
    {
        $content = $this->getAttribute($field);
        
        if (!$content) {
            return [];
        }

        $prompt = "Создай до {$maxTags} тегов для следующего контента. Верни только теги через запятую:\n\n{$content}";
        
        $response = GigaChat::ask($prompt, array_merge([
            'temperature' => 0.5,
            'max_tokens' => 100
        ], $options));

        // Parse tags from response
        $tags = array_map('trim', explode(',', $response));
        return array_filter($tags, fn($tag) => !empty($tag));
    }

    /**
     * Build context string from model attributes
     */
    protected function buildContextFromAttributes(array $attributes = []): string
    {
        if (empty($attributes)) {
            $attributes = $this->getFillable();
        }

        $context = [];
        
        foreach ($attributes as $attribute) {
            $value = $this->getAttribute($attribute);
            if ($value && is_string($value)) {
                $context[] = "{$attribute}: {$value}";
            }
        }

        return implode("\n", $context);
    }
}
