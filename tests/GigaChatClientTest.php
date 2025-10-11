<?php

namespace Tigusigalpa\GigaChat\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Tigusigalpa\GigaChat\Auth\TokenManager;
use Tigusigalpa\GigaChat\GigaChatClient;
use Tigusigalpa\GigaChat\Exceptions\ValidationException;
use Tigusigalpa\GigaChat\Exceptions\GigaChatException;

class GigaChatClientTest extends TestCase
{
    private $mockTokenManager;
    private $mockHttpClient;
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockTokenManager = Mockery::mock(TokenManager::class);
        $this->mockHttpClient = Mockery::mock(Client::class);
        
        $this->mockTokenManager->shouldReceive('getAccessToken')
            ->andReturn('mock-access-token');
            
        $this->client = new GigaChatClient(
            $this->mockTokenManager,
            'https://gigachat.devices.sberbank.ru',
            true,
            'GigaChat',
            $this->mockHttpClient
        );
    }

    /** @test */
    public function it_can_get_models()
    {
        $expectedResponse = [
            'object' => 'list',
            'data' => [
                [
                    'id' => 'GigaChat',
                    'object' => 'model',
                    'owned_by' => 'sberbank'
                ]
            ]
        ];

        $this->mockHttpClient->shouldReceive('get')
            ->with('/api/v1/models', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer mock-access-token',
                ],
            ])
            ->andReturn(new Response(200, [], json_encode($expectedResponse)));

        $result = $this->client->models();

        $this->assertEquals($expectedResponse, $result);
    }

    /** @test */
    public function it_can_send_chat_message()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Привет!']
        ];

        $expectedResponse = $this->createMockResponse([
            'content' => 'Привет! Как дела?'
        ]);

        $this->mockHttpClient->shouldReceive('post')
            ->with('/api/v1/chat/completions', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer mock-access-token',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'GigaChat',
                    'messages' => $messages,
                    'stream' => false,
                ],
            ])
            ->andReturn(new Response(200, [], json_encode($expectedResponse)));

        $result = $this->client->chat($messages);

        $this->assertEquals($expectedResponse, $result);
        $this->assertEquals('Привет! Как дела?', $result['choices'][0]['message']['content']);
    }

    /** @test */
    public function it_validates_empty_messages()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Messages array cannot be empty');

        $this->client->chat([]);
    }

    /** @test */
    public function it_validates_message_structure()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Message at index 0 must have 'role' and 'content' fields");

        $this->client->chat([
            ['invalid' => 'message']
        ]);
    }

    /** @test */
    public function it_validates_message_role()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Invalid role 'invalid' at index 0. Must be 'user', 'system', or 'assistant'");

        $this->client->chat([
            ['role' => 'invalid', 'content' => 'test']
        ]);
    }

    /** @test */
    public function it_validates_message_content()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Message content at index 0 must be a non-empty string");

        $this->client->chat([
            ['role' => 'user', 'content' => '']
        ]);
    }

    /** @test */
    public function it_can_generate_image()
    {
        $prompt = "Нарисуй красивый пейзаж";
        $expectedResponse = $this->createMockImageResponse('test-image-123');

        $this->mockHttpClient->shouldReceive('post')
            ->with('/api/v1/chat/completions', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer mock-access-token',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'GigaChat',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'stream' => false,
                    'function_call' => 'auto',
                ],
            ])
            ->andReturn(new Response(200, [], json_encode($expectedResponse)));

        $result = $this->client->generateImage($prompt);

        $this->assertEquals($expectedResponse, $result);
        $this->assertStringContains('test-image-123', $result['choices'][0]['message']['content']);
    }

    /** @test */
    public function it_can_generate_image_with_system_message()
    {
        $prompt = "Нарисуй кота";
        $options = ['system_message' => 'Ты — Леонардо да Винчи'];
        $expectedResponse = $this->createMockImageResponse('artist-image-456');

        $this->mockHttpClient->shouldReceive('post')
            ->with('/api/v1/chat/completions', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer mock-access-token',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'GigaChat',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Ты — Леонардо да Винчи'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'stream' => false,
                    'function_call' => 'auto',
                ],
            ])
            ->andReturn(new Response(200, [], json_encode($expectedResponse)));

        $result = $this->client->generateImage($prompt, $options);

        $this->assertEquals($expectedResponse, $result);
    }

    /** @test */
    public function it_validates_empty_image_prompt()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Image prompt cannot be empty');

        $this->client->generateImage('');
    }

    /** @test */
    public function it_can_download_image()
    {
        $fileId = 'test-image-123';
        $mockImageData = $this->createMockImageData();

        $this->mockHttpClient->shouldReceive('get')
            ->with("/api/v1/files/{$fileId}/content", [
                'headers' => [
                    'Accept' => 'application/jpg',
                    'Authorization' => 'Bearer mock-access-token',
                ],
            ])
            ->andReturn(new Response(200, [], base64_decode($mockImageData)));

        $result = $this->client->downloadImage($fileId);

        $this->assertEquals($mockImageData, $result);
    }

    /** @test */
    public function it_validates_empty_file_id()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('File ID cannot be empty');

        $this->client->downloadImage('');
    }

    /** @test */
    public function it_can_create_image_with_full_workflow()
    {
        $prompt = "Нарисуй дракона";
        $fileId = 'dragon-image-789';
        $mockImageData = $this->createMockImageData();
        
        // Mock generate image response
        $generateResponse = $this->createMockImageResponse($fileId);
        
        $this->mockHttpClient->shouldReceive('post')
            ->with('/api/v1/chat/completions', Mockery::any())
            ->andReturn(new Response(200, [], json_encode($generateResponse)));
            
        // Mock download image response
        $this->mockHttpClient->shouldReceive('get')
            ->with("/api/v1/files/{$fileId}/content", Mockery::any())
            ->andReturn(new Response(200, [], base64_decode($mockImageData)));

        $result = $this->client->createImage($prompt);

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('file_id', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertEquals($mockImageData, $result['content']);
        $this->assertEquals($fileId, $result['file_id']);
    }

    /** @test */
    public function it_throws_exception_when_cannot_extract_image_id()
    {
        $prompt = "Нарисуй что-то";
        
        // Response without image tag
        $invalidResponse = $this->createMockResponse([
            'content' => 'Извините, не могу создать изображение'
        ]);
        
        $this->mockHttpClient->shouldReceive('post')
            ->andReturn(new Response(200, [], json_encode($invalidResponse)));

        $this->expectException(GigaChatException::class);
        $this->expectExceptionMessage('Could not extract image ID from response');

        $this->client->createImage($prompt);
    }
}
