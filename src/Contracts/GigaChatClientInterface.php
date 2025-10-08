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
}
