<?php

namespace Tigusigalpa\GigaChat\Laravel;

use Illuminate\Support\ServiceProvider;
use Tigusigalpa\GigaChat\Auth\TokenManager;
use Tigusigalpa\GigaChat\GigaChatClient;
use Tigusigalpa\GigaChat\Laravel\Commands\GigaChatTestCommand;
use Tigusigalpa\GigaChat\Laravel\Commands\GigaChatChatCommand;

class GigaChatServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/gigachat.php' => config_path('gigachat.php'),
        ], 'gigachat-config');

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                GigaChatTestCommand::class,
                GigaChatChatCommand::class,
            ]);
        }

        // Register middleware
        $router = $this->app['router'];
        $router->aliasMiddleware('gigachat.rate_limit', \Tigusigalpa\GigaChat\Laravel\Middleware\GigaChatRateLimit::class);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/gigachat.php', 'gigachat');

        $this->app->singleton('gigachat', function ($app) {
            $config = $app['config']->get('gigachat', []);

            $authKey = $config['auth_key'] ?? null;
            if (!$authKey && !empty($config['client_id']) && !empty($config['client_secret'])) {
                $authKey = base64_encode($config['client_id'] . ':' . $config['client_secret']);
            }
            if (!$authKey) {
                throw new \InvalidArgumentException('GigaChat auth_key или client_id/client_secret должны быть заданы в конфигурации.');
            }

            $verify = $config['verify'] ?? true;
            $scope = $config['scope'] ?? 'GIGACHAT_API_PERS';
            $oauthUri = $config['oauth_uri'] ?? 'https://ngw.devices.sberbank.ru:9443';
            $baseUri = $config['base_uri'] ?? 'https://gigachat.devices.sberbank.ru';
            $defaultModel = $config['default_model'] ?? 'GigaChat';

            $tm = new TokenManager($authKey, $scope, $verify, $oauthUri);
            return new GigaChatClient($tm, $baseUri, $verify, $defaultModel);
        });
    }
}