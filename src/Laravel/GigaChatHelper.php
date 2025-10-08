<?php

namespace Tigusigalpa\GigaChat\Laravel;

use Tigusigalpa\GigaChat\Exceptions\ValidationException;

class GigaChatHelper
{
    /**
     * Create a simple user message
     */
    public static function userMessage(string $content): array
    {
        return ['role' => 'user', 'content' => $content];
    }

    /**
     * Create a system message
     */
    public static function systemMessage(string $content): array
    {
        return ['role' => 'system', 'content' => $content];
    }

    /**
     * Create an assistant message
     */
    public static function assistantMessage(string $content): array
    {
        return ['role' => 'assistant', 'content' => $content];
    }

    /**
     * Create a conversation with system prompt
     */
    public static function conversation(string $systemPrompt, string $userMessage): array
    {
        return [
            self::systemMessage($systemPrompt),
            self::userMessage($userMessage)
        ];
    }

    /**
     * Extract content from GigaChat response
     */
    public static function extractContent(array $response): ?string
    {
        return $response['choices'][0]['message']['content'] ?? null;
    }

    /**
     * Extract usage statistics from response
     */
    public static function extractUsage(array $response): ?array
    {
        return $response['usage'] ?? null;
    }

    /**
     * Create default chat options
     */
    public static function defaultOptions(array $overrides = []): array
    {
        return array_merge([
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'top_p' => 0.9,
            'repetition_penalty' => 1.1,
        ], $overrides);
    }

    /**
     * Validate message format
     */
    public static function validateMessage(array $message): bool
    {
        if (!isset($message['role']) || !isset($message['content'])) {
            throw new ValidationException('Message must have role and content fields');
        }

        if (!in_array($message['role'], ['user', 'system', 'assistant'], true)) {
            throw new ValidationException('Invalid message role');
        }

        if (!is_string($message['content']) || trim($message['content']) === '') {
            throw new ValidationException('Message content must be a non-empty string');
        }

        return true;
    }

    /**
     * Format conversation for display
     */
    public static function formatConversation(array $messages): string
    {
        $formatted = [];
        
        foreach ($messages as $message) {
            $role = ucfirst($message['role']);
            $content = $message['content'];
            $formatted[] = "{$role}: {$content}";
        }

        return implode("\n\n", $formatted);
    }

    /**
     * Truncate text to fit token limit (approximate)
     */
    public static function truncateForTokens(string $text, int $maxTokens = 1000): string
    {
        // Rough approximation: 1 token ≈ 4 characters for Russian text
        $maxChars = $maxTokens * 4;
        
        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }

        return mb_substr($text, 0, $maxChars - 3) . '...';
    }

    /**
     * Split long text into chunks
     */
    public static function splitIntoChunks(string $text, int $chunkSize = 2000): array
    {
        if (mb_strlen($text) <= $chunkSize) {
            return [$text];
        }

        $chunks = [];
        $words = explode(' ', $text);
        $currentChunk = '';

        foreach ($words as $word) {
            if (mb_strlen($currentChunk . ' ' . $word) > $chunkSize) {
                if (!empty($currentChunk)) {
                    $chunks[] = trim($currentChunk);
                    $currentChunk = $word;
                } else {
                    // Word is longer than chunk size, split it
                    $chunks[] = mb_substr($word, 0, $chunkSize);
                    $currentChunk = mb_substr($word, $chunkSize);
                }
            } else {
                $currentChunk .= ' ' . $word;
            }
        }

        if (!empty(trim($currentChunk))) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }
}
