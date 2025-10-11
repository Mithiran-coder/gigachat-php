<?php

namespace Tigusigalpa\GigaChat\Tests\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Tigusigalpa\GigaChat\Tests\TestCase;
use Tigusigalpa\GigaChat\Auth\TokenManager;
use Tigusigalpa\GigaChat\Exceptions\AuthenticationException;

class TokenManagerTest extends TestCase
{
    private $mockHttpClient;
    private $tokenManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockHttpClient = Mockery::mock(Client::class);
        $this->tokenManager = new TokenManager(
            'test-client-id',
            'test-client-secret',
            'GIGACHAT_API_PERS',
            'https://ngw.devices.sberbank.ru:9443',
            $this->mockHttpClient
        );
    }

    /** @test */
    public function it_can_get_access_token()
    {
        $expectedToken = 'test-access-token-12345';
        $tokenResponse = [
            'access_token' => $expectedToken,
            'expires_at' => time() + 1800 // 30 minutes from now
        ];

        $this->mockHttpClient->shouldReceive('post')
            ->with('/api/v2/oauth', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                    'RqUID' => Mockery::any(),
                    'Authorization' => 'Basic ' . base64_encode('test-client-id:test-client-secret'),
                ],
                'form_params' => [
                    'scope' => 'GIGACHAT_API_PERS'
                ],
            ])
            ->andReturn(new Response(200, [], json_encode($tokenResponse)));

        $result = $this->tokenManager->getAccessToken();

        $this->assertEquals($expectedToken, $result);
    }

    /** @test */
    public function it_caches_valid_token()
    {
        $expectedToken = 'cached-token-67890';
        $tokenResponse = [
            'access_token' => $expectedToken,
            'expires_at' => time() + 1800
        ];

        // Should only make one HTTP request
        $this->mockHttpClient->shouldReceive('post')
            ->once()
            ->andReturn(new Response(200, [], json_encode($tokenResponse)));

        // First call
        $result1 = $this->tokenManager->getAccessToken();
        
        // Second call should use cached token
        $result2 = $this->tokenManager->getAccessToken();

        $this->assertEquals($expectedToken, $result1);
        $this->assertEquals($expectedToken, $result2);
    }

    /** @test */
    public function it_refreshes_expired_token()
    {
        $firstToken = 'first-token';
        $secondToken = 'refreshed-token';
        
        $expiredTokenResponse = [
            'access_token' => $firstToken,
            'expires_at' => time() - 100 // Already expired
        ];
        
        $newTokenResponse = [
            'access_token' => $secondToken,
            'expires_at' => time() + 1800
        ];

        $this->mockHttpClient->shouldReceive('post')
            ->twice()
            ->andReturn(
                new Response(200, [], json_encode($expiredTokenResponse)),
                new Response(200, [], json_encode($newTokenResponse))
            );

        // First call gets expired token
        $result1 = $this->tokenManager->getAccessToken();
        
        // Second call should refresh the token
        $result2 = $this->tokenManager->getAccessToken();

        $this->assertEquals($firstToken, $result1);
        $this->assertEquals($secondToken, $result2);
    }

    /** @test */
    public function it_throws_exception_on_authentication_failure()
    {
        $errorResponse = [
            'error' => 'invalid_client',
            'error_description' => 'Invalid client credentials'
        ];

        $this->mockHttpClient->shouldReceive('post')
            ->andReturn(new Response(401, [], json_encode($errorResponse)));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authentication failed: Invalid client credentials');

        $this->tokenManager->getAccessToken();
    }

    /** @test */
    public function it_handles_malformed_response()
    {
        $this->mockHttpClient->shouldReceive('post')
            ->andReturn(new Response(200, [], 'invalid json'));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid response format');

        $this->tokenManager->getAccessToken();
    }

    /** @test */
    public function it_handles_missing_access_token_in_response()
    {
        $invalidResponse = [
            'expires_at' => time() + 1800
            // access_token missing
        ];

        $this->mockHttpClient->shouldReceive('post')
            ->andReturn(new Response(200, [], json_encode($invalidResponse)));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Access token not found in response');

        $this->tokenManager->getAccessToken();
    }

    /** @test */
    public function it_can_create_from_auth_key()
    {
        $authKey = base64_encode('key-client-id:key-client-secret');
        $tokenManager = TokenManager::fromAuthKey($authKey);

        $this->assertInstanceOf(TokenManager::class, $tokenManager);
    }

    /** @test */
    public function it_validates_auth_key_format()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid auth key format');

        TokenManager::fromAuthKey('invalid-auth-key');
    }

    /** @test */
    public function it_can_clear_cached_token()
    {
        $tokenResponse = [
            'access_token' => 'test-token',
            'expires_at' => time() + 1800
        ];

        $this->mockHttpClient->shouldReceive('post')
            ->twice() // Should make two requests after clearing cache
            ->andReturn(new Response(200, [], json_encode($tokenResponse)));

        // Get token (cached)
        $this->tokenManager->getAccessToken();
        
        // Clear cache
        $this->tokenManager->clearToken();
        
        // Get token again (should make new request)
        $result = $this->tokenManager->getAccessToken();

        $this->assertEquals('test-token', $result);
    }

    /** @test */
    public function it_generates_unique_request_ids()
    {
        $capturedHeaders = [];
        
        $this->mockHttpClient->shouldReceive('post')
            ->twice()
            ->with('/api/v2/oauth', Mockery::capture($capturedHeaders))
            ->andReturn(new Response(200, [], json_encode([
                'access_token' => 'test-token',
                'expires_at' => time() + 1800
            ])));

        // Clear any cached token first
        $this->tokenManager->clearToken();
        
        // Make two requests
        $this->tokenManager->getAccessToken();
        $this->tokenManager->clearToken();
        $this->tokenManager->getAccessToken();

        // Verify that RqUID headers are different
        $this->assertNotEquals(
            $capturedHeaders[0]['headers']['RqUID'],
            $capturedHeaders[1]['headers']['RqUID']
        );
    }
}
