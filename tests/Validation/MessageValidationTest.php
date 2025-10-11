<?php

namespace Tigusigalpa\GigaChat\Tests\Validation;

use Tigusigalpa\GigaChat\Tests\TestCase;
use Tigusigalpa\GigaChat\Auth\TokenManager;
use Tigusigalpa\GigaChat\GigaChatClient;
use Tigusigalpa\GigaChat\Exceptions\ValidationException;
use GuzzleHttp\Client;
use Mockery;

class MessageValidationTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $mockTokenManager = Mockery::mock(TokenManager::class);
        $mockHttpClient = Mockery::mock(Client::class);
        
        $mockTokenManager->shouldReceive('getAccessToken')
            ->andReturn('mock-token');
            
        $this->client = new GigaChatClient(
            $mockTokenManager,
            'https://test.api',
            true,
            'GigaChat',
            $mockHttpClient
        );
    }

    /** @test */
    public function it_validates_empty_messages_array()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Messages array cannot be empty');

        $this->client->chat([]);
    }

    /** @test */
    public function it_validates_message_is_array()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Message at index 0 must be an array');

        $this->client->chat(['invalid message']);
    }

    /** @test */
    public function it_validates_message_has_required_fields()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Message at index 0 must have 'role' and 'content' fields");

        $this->client->chat([
            ['role' => 'user'] // missing content
        ]);
    }

    /** @test */
    public function it_validates_message_has_content_field()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Message at index 0 must have 'role' and 'content' fields");

        $this->client->chat([
            ['content' => 'test'] // missing role
        ]);
    }

    /** @test */
    public function it_validates_role_values()
    {
        $invalidRoles = ['admin', 'moderator', 'bot', 'human', ''];
        
        foreach ($invalidRoles as $index => $role) {
            try {
                $this->client->chat([
                    ['role' => $role, 'content' => 'test']
                ]);
                $this->fail("Should have thrown ValidationException for role: {$role}");
            } catch (ValidationException $e) {
                $this->assertStringContains("Invalid role '{$role}'", $e->getMessage());
                $this->assertStringContains("Must be 'user', 'system', or 'assistant'", $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_accepts_valid_roles()
    {
        $validRoles = ['user', 'system', 'assistant'];
        
        foreach ($validRoles as $role) {
            // Should not throw exception
            try {
                $this->client->chat([
                    ['role' => $role, 'content' => 'test content']
                ]);
            } catch (ValidationException $e) {
                // If validation exception is thrown, it should not be about the role
                $this->assertStringNotContains('Invalid role', $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_validates_content_is_string()
    {
        $invalidContents = [123, [], null, true, false];
        
        foreach ($invalidContents as $content) {
            try {
                $this->client->chat([
                    ['role' => 'user', 'content' => $content]
                ]);
                $this->fail("Should have thrown ValidationException for content: " . var_export($content, true));
            } catch (ValidationException $e) {
                $this->assertStringContains('Message content at index 0 must be a non-empty string', $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_validates_content_is_not_empty()
    {
        $emptyContents = ['', '   ', "\t", "\n", "  \n  \t  "];
        
        foreach ($emptyContents as $content) {
            try {
                $this->client->chat([
                    ['role' => 'user', 'content' => $content]
                ]);
                $this->fail("Should have thrown ValidationException for empty content: " . var_export($content, true));
            } catch (ValidationException $e) {
                $this->assertStringContains('Message content at index 0 must be a non-empty string', $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_validates_multiple_messages()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Invalid role 'invalid' at index 1");

        $this->client->chat([
            ['role' => 'user', 'content' => 'First message'],
            ['role' => 'invalid', 'content' => 'Second message'], // This should fail
            ['role' => 'assistant', 'content' => 'Third message']
        ]);
    }

    /** @test */
    public function it_validates_message_at_correct_index()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Message content at index 2 must be a non-empty string");

        $this->client->chat([
            ['role' => 'user', 'content' => 'First message'],
            ['role' => 'assistant', 'content' => 'Second message'],
            ['role' => 'user', 'content' => ''], // This should fail at index 2
        ]);
    }

    /** @test */
    public function it_accepts_valid_message_structure()
    {
        $validMessages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant'],
            ['role' => 'user', 'content' => 'Hello, how are you?'],
            ['role' => 'assistant', 'content' => 'I am doing well, thank you!'],
            ['role' => 'user', 'content' => 'Great to hear!']
        ];

        // Should not throw any validation exceptions
        try {
            $this->client->chat($validMessages);
        } catch (ValidationException $e) {
            $this->fail("Should not throw ValidationException for valid messages: " . $e->getMessage());
        } catch (\Exception $e) {
            // Other exceptions (like HTTP errors) are expected since we're using mocks
            // We only care that validation passes
        }
    }

    /** @test */
    public function it_validates_image_prompt_not_empty()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Image prompt cannot be empty');

        $this->client->generateImage('');
    }

    /** @test */
    public function it_validates_image_prompt_not_whitespace_only()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Image prompt cannot be empty');

        $this->client->generateImage('   ');
    }

    /** @test */
    public function it_validates_file_id_not_empty()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('File ID cannot be empty');

        $this->client->downloadImage('');
    }

    /** @test */
    public function it_validates_file_id_not_whitespace_only()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('File ID cannot be empty');

        $this->client->downloadImage('   ');
    }

    /** @test */
    public function it_accepts_valid_image_prompt()
    {
        // Should not throw validation exception
        try {
            $this->client->generateImage('Нарисуй красивый пейзаж');
        } catch (ValidationException $e) {
            $this->fail("Should not throw ValidationException for valid image prompt: " . $e->getMessage());
        } catch (\Exception $e) {
            // Other exceptions are expected due to mocking
        }
    }

    /** @test */
    public function it_accepts_valid_file_id()
    {
        // Should not throw validation exception
        try {
            $this->client->downloadImage('valid-file-id-123');
        } catch (ValidationException $e) {
            $this->fail("Should not throw ValidationException for valid file ID: " . $e->getMessage());
        } catch (\Exception $e) {
            // Other exceptions are expected due to mocking
        }
    }
}
