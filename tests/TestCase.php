<?php

namespace Tigusigalpa\GigaChat\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock GigaChat response
     */
    protected function createMockResponse(array $data): array
    {
        return [
            'choices' => [
                [
                    'message' => [
                        'content' => $data['content'] ?? 'Test response',
                        'role' => 'assistant'
                    ],
                    'index' => 0,
                    'finish_reason' => $data['finish_reason'] ?? 'stop'
                ]
            ],
            'created' => time(),
            'model' => 'GigaChat',
            'object' => 'chat.completion',
            'usage' => [
                'prompt_tokens' => $data['prompt_tokens'] ?? 10,
                'completion_tokens' => $data['completion_tokens'] ?? 20,
                'total_tokens' => $data['total_tokens'] ?? 30
            ]
        ];
    }

    /**
     * Create a mock image response
     */
    protected function createMockImageResponse(string $fileId = 'test-image-id'): array
    {
        return $this->createMockResponse([
            'content' => "<img src=\"{$fileId}\" fuse=\"true\"/>",
            'finish_reason' => 'stop'
        ]);
    }

    /**
     * Create mock base64 image data
     */
    protected function createMockImageData(): string
    {
        // Простейший base64 для тестирования (1x1 пиксель PNG)
        return '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/8A';
    }
}
