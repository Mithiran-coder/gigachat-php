<?php

namespace Tigusigalpa\GigaChat\Tests\Laravel;

use Tigusigalpa\GigaChat\Tests\TestCase;
use Tigusigalpa\GigaChat\Laravel\GigaChat;
use Tigusigalpa\GigaChat\GigaChatClient;
use Mockery;

class GigaChatFacadeTest extends TestCase
{
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockClient = Mockery::mock(GigaChatClient::class);
        
        // Mock the facade accessor
        GigaChat::swap($this->mockClient);
    }

    /** @test */
    public function it_can_ask_simple_question()
    {
        $question = "Как дела?";
        $expectedResponse = $this->createMockResponse([
            'content' => 'Отлично, спасибо!'
        ]);

        $this->mockClient->shouldReceive('chat')
            ->with([['role' => 'user', 'content' => $question]], [])
            ->andReturn($expectedResponse);

        $result = GigaChat::ask($question);

        $this->assertEquals('Отлично, спасибо!', $result);
    }

    /** @test */
    public function it_can_ask_with_context()
    {
        $systemPrompt = "Ты дружелюбный помощник";
        $question = "Расскажи анекдот";
        $expectedResponse = $this->createMockResponse([
            'content' => 'Вот анекдот для вас!'
        ]);

        $expectedMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $question]
        ];

        $this->mockClient->shouldReceive('chat')
            ->with($expectedMessages, [])
            ->andReturn($expectedResponse);

        $result = GigaChat::askWithContext($systemPrompt, $question);

        $this->assertEquals('Вот анекдот для вас!', $result);
    }

    /** @test */
    public function it_can_continue_chat()
    {
        $conversation = [
            ['role' => 'user', 'content' => 'Привет'],
            ['role' => 'assistant', 'content' => 'Привет! Как дела?']
        ];
        $newMessage = "Отлично!";
        
        $expectedResponse = $this->createMockResponse([
            'content' => 'Рад это слышать!'
        ]);

        $expectedMessages = array_merge($conversation, [
            ['role' => 'user', 'content' => $newMessage]
        ]);

        $this->mockClient->shouldReceive('chat')
            ->with($expectedMessages, [])
            ->andReturn($expectedResponse);

        $result = GigaChat::continueChat($conversation, $newMessage);

        $this->assertCount(4, $result);
        $this->assertEquals('user', $result[2]['role']);
        $this->assertEquals($newMessage, $result[2]['content']);
        $this->assertEquals('assistant', $result[3]['role']);
        $this->assertEquals('Рад это слышать!', $result[3]['content']);
    }

    /** @test */
    public function it_can_draw_image()
    {
        $description = "красивый пейзаж";
        $expectedPrompt = "Нарисуй " . $description;
        
        $mockResult = [
            'content' => $this->createMockImageData(),
            'file_id' => 'test-image-123',
            'response' => $this->createMockImageResponse('test-image-123')
        ];

        $this->mockClient->shouldReceive('createImage')
            ->with($expectedPrompt, [])
            ->andReturn($mockResult);

        $result = GigaChat::drawImage($description);

        $this->assertEquals($mockResult, $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('file_id', $result);
    }

    /** @test */
    public function it_can_draw_image_in_style()
    {
        $description = "портрет кота";
        $artistStyle = "Леонардо да Винчи";
        $expectedPrompt = "Нарисуй " . $description;
        $expectedOptions = ['system_message' => "Ты — " . $artistStyle];
        
        $mockResult = [
            'content' => $this->createMockImageData(),
            'file_id' => 'artist-image-456',
            'response' => $this->createMockImageResponse('artist-image-456')
        ];

        $this->mockClient->shouldReceive('createImage')
            ->with($expectedPrompt, $expectedOptions)
            ->andReturn($mockResult);

        $result = GigaChat::drawImageInStyle($description, $artistStyle);

        $this->assertEquals($mockResult, $result);
    }

    /** @test */
    public function it_can_draw_image_in_style_with_additional_options()
    {
        $description = "абстракция";
        $artistStyle = "Кандинский";
        $additionalOptions = ['temperature' => 0.8];
        
        $expectedPrompt = "Нарисуй " . $description;
        $expectedOptions = array_merge($additionalOptions, [
            'system_message' => "Ты — " . $artistStyle
        ]);
        
        $mockResult = [
            'content' => $this->createMockImageData(),
            'file_id' => 'abstract-image-789',
            'response' => $this->createMockImageResponse('abstract-image-789')
        ];

        $this->mockClient->shouldReceive('createImage')
            ->with($expectedPrompt, $expectedOptions)
            ->andReturn($mockResult);

        $result = GigaChat::drawImageInStyle($description, $artistStyle, $additionalOptions);

        $this->assertEquals($mockResult, $result);
    }

    /** @test */
    public function it_can_extract_image_id_from_content()
    {
        $content = '<img src="extracted-image-123" fuse="true"/>';
        
        $result = GigaChat::extractImageId($content);

        $this->assertEquals('extracted-image-123', $result);
    }

    /** @test */
    public function it_returns_null_when_no_image_id_found()
    {
        $content = 'Обычный текст без изображения';
        
        $result = GigaChat::extractImageId($content);

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_extract_image_id_with_different_quote_styles()
    {
        // Test with double quotes
        $content1 = '<img src="double-quote-image" fuse="true"/>';
        $this->assertEquals('double-quote-image', GigaChat::extractImageId($content1));

        // Test with single quotes
        $content2 = "<img src='single-quote-image' fuse='true'/>";
        $this->assertEquals('single-quote-image', GigaChat::extractImageId($content2));

        // Test with mixed attributes
        $content3 = '<img alt="test" src="mixed-attr-image" style="width:100px" fuse="true"/>';
        $this->assertEquals('mixed-attr-image', GigaChat::extractImageId($content3));
    }

    /** @test */
    public function it_handles_empty_response_content()
    {
        $question = "Тест";
        $emptyResponse = $this->createMockResponse(['content' => '']);

        $this->mockClient->shouldReceive('chat')
            ->andReturn($emptyResponse);

        $result = GigaChat::ask($question);

        $this->assertEquals('', $result);
    }

    /** @test */
    public function it_handles_missing_content_in_response()
    {
        $question = "Тест";
        $invalidResponse = [
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant'
                        // content отсутствует
                    ]
                ]
            ]
        ];

        $this->mockClient->shouldReceive('chat')
            ->andReturn($invalidResponse);

        $result = GigaChat::ask($question);

        $this->assertEquals('', $result);
    }
}
