<?php

namespace Tigusigalpa\GigaChat\Tests\Integration;

use Tigusigalpa\GigaChat\Tests\TestCase;
use Tigusigalpa\GigaChat\Auth\TokenManager;
use Tigusigalpa\GigaChat\GigaChatClient;

/**
 * Integration tests for image generation functionality
 * 
 * These tests require valid GigaChat API credentials to run.
 * Set GIGACHAT_INTEGRATION_TEST=true in your environment to enable them.
 */
class ImageGenerationIntegrationTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->shouldRunIntegrationTests()) {
            $this->markTestSkipped('Integration tests are disabled. Set GIGACHAT_INTEGRATION_TEST=true to enable.');
        }

        $clientId = $_ENV['GIGACHAT_CLIENT_ID'] ?? null;
        $clientSecret = $_ENV['GIGACHAT_CLIENT_SECRET'] ?? null;
        $scope = $_ENV['GIGACHAT_SCOPE'] ?? 'GIGACHAT_API_PERS';

        if (!$clientId || !$clientSecret) {
            $this->markTestSkipped('GigaChat credentials not provided. Set GIGACHAT_CLIENT_ID and GIGACHAT_CLIENT_SECRET.');
        }

        $tokenManager = new TokenManager($clientId, $clientSecret, $scope);
        $this->client = new GigaChatClient($tokenManager);
    }

    /** @test */
    public function it_can_generate_simple_image()
    {
        $prompt = "Нарисуй простой геометрический узор";
        
        $response = $this->client->generateImage($prompt);

        $this->assertArrayHasKey('choices', $response);
        $this->assertArrayHasKey('message', $response['choices'][0]);
        $this->assertStringContains('<img', $response['choices'][0]['message']['content']);
        $this->assertStringContains('src=', $response['choices'][0]['message']['content']);
    }

    /** @test */
    public function it_can_generate_image_with_style()
    {
        $prompt = "Нарисуй цветок";
        $options = ['system_message' => 'Ты — художник-импрессионист'];
        
        $response = $this->client->generateImage($prompt, $options);

        $this->assertArrayHasKey('choices', $response);
        $content = $response['choices'][0]['message']['content'];
        $this->assertStringContains('<img', $content);
        
        // Extract and validate image ID
        preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
        $this->assertNotEmpty($matches[1], 'Image ID should be extracted from response');
    }

    /** @test */
    public function it_can_download_generated_image()
    {
        $prompt = "Нарисуй маленький круг";
        
        // Generate image
        $response = $this->client->generateImage($prompt);
        $content = $response['choices'][0]['message']['content'];
        
        // Extract image ID
        preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
        $this->assertNotEmpty($matches[1], 'Should extract image ID');
        
        $imageId = $matches[1];
        
        // Download image
        $imageData = $this->client->downloadImage($imageId);
        
        $this->assertNotEmpty($imageData);
        $this->assertTrue(strlen($imageData) > 100, 'Image data should be substantial');
        
        // Verify it's valid base64
        $decoded = base64_decode($imageData, true);
        $this->assertNotFalse($decoded, 'Should be valid base64');
        $this->assertTrue(strlen($decoded) > 50, 'Decoded image should have content');
    }

    /** @test */
    public function it_can_create_image_with_full_workflow()
    {
        $prompt = "Нарисуй звезду";
        
        $result = $this->client->createImage($prompt);

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('file_id', $result);
        $this->assertArrayHasKey('response', $result);
        
        $this->assertNotEmpty($result['content']);
        $this->assertNotEmpty($result['file_id']);
        $this->assertIsArray($result['response']);
        
        // Verify base64 content
        $decoded = base64_decode($result['content'], true);
        $this->assertNotFalse($decoded);
        $this->assertTrue(strlen($decoded) > 50);
    }

    /** @test */
    public function it_handles_complex_prompts()
    {
        $prompt = "Нарисуй абстрактную композицию из геометрических фигур в синих и зеленых тонах";
        $options = [
            'system_message' => 'Ты — современный художник-абстракционист',
            'temperature' => 0.8
        ];
        
        $result = $this->client->createImage($prompt, $options);

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('file_id', $result);
        $this->assertNotEmpty($result['content']);
        $this->assertNotEmpty($result['file_id']);
    }

    /** @test */
    public function it_can_generate_multiple_images_sequentially()
    {
        $prompts = [
            "Нарисуй треугольник",
            "Нарисуй квадрат", 
            "Нарисуй круг"
        ];
        
        $results = [];
        
        foreach ($prompts as $prompt) {
            $result = $this->client->createImage($prompt);
            $results[] = $result;
            
            $this->assertArrayHasKey('file_id', $result);
            $this->assertNotEmpty($result['file_id']);
            
            // Small delay to avoid rate limiting
            sleep(1);
        }
        
        // Verify all images have different IDs
        $fileIds = array_column($results, 'file_id');
        $uniqueIds = array_unique($fileIds);
        $this->assertCount(count($prompts), $uniqueIds, 'All images should have unique IDs');
    }

    /** @test */
    public function it_validates_image_content_format()
    {
        $prompt = "Нарисуй точку";
        
        $response = $this->client->generateImage($prompt);
        $content = $response['choices'][0]['message']['content'];
        
        // Should contain proper HTML img tag
        $this->assertMatchesRegularExpression(
            '/<img[^>]+src=["\'][^"\']+["\'][^>]*\/?>/i',
            $content,
            'Response should contain valid img tag'
        );
        
        // Should contain fuse attribute
        $this->assertStringContains('fuse=', $content);
    }

    private function shouldRunIntegrationTests(): bool
    {
        return filter_var(
            $_ENV['GIGACHAT_INTEGRATION_TEST'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );
    }
}
