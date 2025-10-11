<?php

namespace Tigusigalpa\GigaChat;

use GuzzleHttp\Client;
use Tigusigalpa\GigaChat\Auth\TokenManager;
use Tigusigalpa\GigaChat\Contracts\GigaChatClientInterface;
use Tigusigalpa\GigaChat\Contracts\TokenManagerInterface;
use Tigusigalpa\GigaChat\Exceptions\GigaChatException;
use Tigusigalpa\GigaChat\Exceptions\ValidationException;

class GigaChatClient implements GigaChatClientInterface
{
    private Client $http;
    private string $baseUri;
    private TokenManagerInterface $tokenManager;
    private string $defaultModel;

    public function __construct(TokenManagerInterface $tokenManager, string $baseUri = 'https://gigachat.devices.sberbank.ru', $verify = true, string $defaultModel = 'GigaChat', ?Client $httpClient = null)
    {
        $this->tokenManager = $tokenManager;
        $this->baseUri = rtrim($baseUri, '/');
        $this->defaultModel = $defaultModel;
        $this->http = $httpClient ?: new Client([
            'base_uri' => $this->baseUri,
            'verify' => $verify,
            // Дополнительные настройки для SSL
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => $verify ? 1 : 0,
                CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
            ],
        ]);
    }

    public function models(): array
    {
        $token = $this->tokenManager->getAccessToken();
        $resp = $this->http->get('/api/v1/models', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
        return json_decode((string) $resp->getBody(), true);
    }

    /**
     * Chat completion (non-streaming)
     * @param array $messages [[ 'role' => 'user'|'system'|'assistant', 'content' => string ], ...]
     * @param array $options Additional fields: temperature, top_p, max_tokens, etc.
     * @throws ValidationException
     * @throws GigaChatException
     */
    public function chat(array $messages, array $options = []): array
    {
        $this->validateMessages($messages);
        $token = $this->tokenManager->getAccessToken();
        $model = $options['model'] ?? $this->defaultModel;
        $payload = array_merge($options, [
            'model' => $model,
            'messages' => $messages,
            'stream' => false,
        ]);
        unset($payload['stream_callback']);

        $resp = $this->http->post('/api/v1/chat/completions', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        return json_decode((string) $resp->getBody(), true);
    }

    /**
     * Streaming chat completion via SSE.
     * If $onEvent is provided, it will be called with ($event, $error) per chunk. Otherwise returns a Generator yielding events.
     * @throws ValidationException
     * @throws GigaChatException
     */
    public function chatStream(array $messages, array $options = [], ?callable $onEvent = null)
    {
        $this->validateMessages($messages);
        $token = $this->tokenManager->getAccessToken();
        $model = $options['model'] ?? $this->defaultModel;
        $payload = array_merge($options, [
            'model' => $model,
            'messages' => $messages,
            'stream' => true,
        ]);

        $resp = $this->http->post('/api/v1/chat/completions', [
            'headers' => [
                'Accept' => 'text/event-stream',
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'stream' => true,
        ]);

        $body = $resp->getBody();
        if ($onEvent) {
            while (!$body->eof()) {
                $chunk = $body->read(1024);
                if ($chunk === '') {
                    usleep(50000);
                    continue;
                }
                foreach (explode("\n", $chunk) as $line) {
                    $line = trim($line);
                    if ($line === '') continue;
                    if (stripos($line, 'data:') === 0) {
                        $dataPart = trim(substr($line, strlen('data:')));
                        if ($dataPart === '[DONE]') {
                            $onEvent('[DONE]', null);
                            return;
                        }
                        $event = json_decode($dataPart, true);
                        $onEvent($event, null);
                    }
                }
            }
            return;
        }

        // Generator mode
        return (function () use ($body) {
            while (!$body->eof()) {
                $chunk = $body->read(1024);
                if ($chunk === '') {
                    usleep(50000);
                    continue;
                }
                foreach (explode("\n", $chunk) as $line) {
                    $line = trim($line);
                    if ($line === '') continue;
                    if (stripos($line, 'data:') === 0) {
                        $dataPart = trim(substr($line, strlen('data:')));
                        if ($dataPart === '[DONE]') {
                            return; // end generator
                        }
                        $event = json_decode($dataPart, true);
                        yield $event;
                    }
                }
            }
        })();
    }

    /**
     * Validate messages array
     * 
     * @param array $messages
     * @throws ValidationException
     */
    private function validateMessages(array $messages): void
    {
        if (empty($messages)) {
            throw new ValidationException('Messages array cannot be empty');
        }

        foreach ($messages as $index => $message) {
            if (!is_array($message)) {
                throw new ValidationException("Message at index {$index} must be an array");
            }

            if (!isset($message['role']) || !isset($message['content'])) {
                throw new ValidationException("Message at index {$index} must have 'role' and 'content' fields");
            }

            if (!in_array($message['role'], ['user', 'system', 'assistant'], true)) {
                throw new ValidationException("Invalid role '{$message['role']}' at index {$index}. Must be 'user', 'system', or 'assistant'");
            }

            if (!is_string($message['content']) || trim($message['content']) === '') {
                throw new ValidationException("Message content at index {$index} must be a non-empty string");
            }
        }
    }

    /**
     * Generate image using GigaChat
     * 
     * @param string $prompt Image generation prompt (should contain "нарисуй" or similar)
     * @param array $options Additional options (system message, model, etc.)
     * @return array Response with image ID in content
     * @throws ValidationException
     * @throws GigaChatException
     */
    public function generateImage(string $prompt, array $options = []): array
    {
        if (trim($prompt) === '') {
            throw new ValidationException('Image prompt cannot be empty');
        }

        $systemMessage = $options['system_message'] ?? null;
        $messages = [];
        
        if ($systemMessage) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemMessage
            ];
        }
        
        $messages[] = [
            'role' => 'user',
            'content' => $prompt
        ];

        $chatOptions = array_merge($options, [
            'function_call' => 'auto'
        ]);
        
        unset($chatOptions['system_message']);

        return $this->chat($messages, $chatOptions);
    }

    /**
     * Download image by file ID
     * 
     * @param string $fileId Image file ID from generateImage response
     * @return string Base64 encoded image content
     * @throws ValidationException
     * @throws GigaChatException
     */
    public function downloadImage(string $fileId): string
    {
        if (trim($fileId) === '') {
            throw new ValidationException('File ID cannot be empty');
        }

        $token = $this->tokenManager->getAccessToken();
        
        $resp = $this->http->get("/api/v1/files/{$fileId}/content", [
            'headers' => [
                'Accept' => 'application/jpg',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return base64_encode((string) $resp->getBody());
    }

    /**
     * Generate and download image in one call
     * 
     * @param string $prompt Image generation prompt
     * @param array $options Additional options
     * @return array ['content' => base64_content, 'file_id' => file_id]
     * @throws ValidationException
     * @throws GigaChatException
     */
    public function createImage(string $prompt, array $options = []): array
    {
        $response = $this->generateImage($prompt, $options);
        
        // Extract file ID from response content
        $content = $response['choices'][0]['message']['content'] ?? '';
        $fileId = $this->extractImageId($content);
        
        if (!$fileId) {
            throw new GigaChatException('Could not extract image ID from response');
        }

        $imageContent = $this->downloadImage($fileId);

        return [
            'content' => $imageContent,
            'file_id' => $fileId,
            'response' => $response
        ];
    }

    /**
     * Extract image ID from HTML content
     * 
     * @param string $content HTML content with img tag
     * @return string|null Image file ID
     */
    private function extractImageId(string $content): ?string
    {
        // Parse HTML to extract src attribute from img tag
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}