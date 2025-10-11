<?php

namespace Tigusigalpa\GigaChat\Contracts;

interface GigaChatClientInterface
{
    /**
     * Get available models
     * 
     * @return array
     * @throws \Tigusigalpa\GigaChat\Exceptions\GigaChatException
     */
    public function models(): array;

    /**
     * Chat completion (non-streaming)
     * 
     * @param array $messages Messages array with role and content
     * @param array $options Additional options (temperature, top_p, max_tokens, etc.)
     * @return array
     * @throws \Tigusigalpa\GigaChat\Exceptions\GigaChatException
     */
    public function chat(array $messages, array $options = []): array;

    /**
     * Streaming chat completion via SSE
     * 
     * @param array $messages Messages array with role and content
     * @param array $options Additional options
     * @param callable|null $onEvent Callback for handling events
     * @return \Generator|void
     * @throws \Tigusigalpa\GigaChat\Exceptions\GigaChatException
     */
    public function chatStream(array $messages, array $options = [], ?callable $onEvent = null);

    /**
     * Generate image using GigaChat
     * 
     * @param string $prompt Image generation prompt (should contain "нарисуй" or similar)
     * @param array $options Additional options (system message, model, etc.)
     * @return array Response with image ID in content
     * @throws \Tigusigalpa\GigaChat\Exceptions\GigaChatException
     */
    public function generateImage(string $prompt, array $options = []): array;

    /**
     * Download image by file ID
     * 
     * @param string $fileId Image file ID from generateImage response
     * @return string Base64 encoded image content
     * @throws \Tigusigalpa\GigaChat\Exceptions\GigaChatException
     */
    public function downloadImage(string $fileId): string;

    /**
     * Generate and download image in one call
     * 
     * @param string $prompt Image generation prompt
     * @param array $options Additional options
     * @return array ['content' => base64_content, 'file_id' => file_id]
     * @throws \Tigusigalpa\GigaChat\Exceptions\GigaChatException
     */
    public function createImage(string $prompt, array $options = []): array;
}
