<?php

namespace Tigusigalpa\GigaChat\Auth;

use GuzzleHttp\Client;
use Tigusigalpa\GigaChat\Contracts\TokenManagerInterface;
use Tigusigalpa\GigaChat\Exceptions\AuthenticationException;
use Tigusigalpa\GigaChat\Exceptions\GigaChatException;

class TokenManager implements TokenManagerInterface
{
    private string $authKey;
    private string $scope;
    private Client $client;
    private string $oauthUri;
    private ?string $accessToken = null;
    private ?int $expiresAt = null; // seconds

    public function __construct(string $authKey, string $scope = 'GIGACHAT_API_PERS', $verify = true, string $oauthUri = 'https://ngw.devices.sberbank.ru:9443', ?Client $httpClient = null)
    {
        $this->authKey = $authKey;
        $this->scope = $scope;
        $this->oauthUri = rtrim($oauthUri, '/');
        $this->client = $httpClient ?: new Client([
            'base_uri' => $this->oauthUri,
            'verify' => $verify,
            // Дополнительные настройки для SSL
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => $verify ? 1 : 0,
                CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
            ],
        ]);
    }

    public function getAccessToken(): string
    {
        // refresh if missing or expiring within 30 seconds
        if ($this->accessToken && $this->expiresAt && (time() + 30) < $this->expiresAt) {
            return $this->accessToken;
        }
        $this->refreshToken();
        return $this->accessToken ?? '';
    }

    protected function refreshToken(): void
    {
        $rqUid = $this->generateUuidV4();
        try {
            $resp = $this->client->post('/api/v2/oauth', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                    'RqUID' => $rqUid,
                    'Authorization' => 'Basic ' . $this->authKey,
                ],
                'form_params' => [
                    'scope' => $this->scope,
                ],
            ]);
        } catch (\Throwable $e) {
            throw new AuthenticationException('OAuth token request failed: ' . $e->getMessage(), 0, $e);
        }

        $data = json_decode((string) $resp->getBody(), true);
        if (!is_array($data) || empty($data['access_token'])) {
            throw new AuthenticationException('Invalid token response');
        }
        $this->accessToken = $data['access_token'];

        $expiresAt = $data['expires_at'] ?? null;
        if ($expiresAt) {
            // expires_at may be in milliseconds
            if ($expiresAt > 1000000000000) {
                $expiresAt = intdiv((int) $expiresAt, 1000);
            }
            $this->expiresAt = (int) $expiresAt;
        } else {
            // default: 29 minutes
            $this->expiresAt = time() + 29 * 60;
        }
    }

    private function generateUuidV4(): string
    {
        $data = random_bytes(16);
        // Set version to 0100
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}