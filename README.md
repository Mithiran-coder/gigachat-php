# 🚀 GigaChat PHP SDK

[English version](README_en.md)

Полнофункциональный PHP SDK для работы с Sber GigaChat API с поддержкой Laravel. Пакет предоставляет удобный интерфейс
для интеграции с AI моделями Sber GigaChat, включая поддержку streaming и обычных запросов.

[![Latest Version](https://img.shields.io/packagist/v/tigusigalpa/gigachat-php.svg?style=flat-square)](https://packagist.org/packages/tigusigalpa/gigachat-php)
[![PHP Version](https://img.shields.io/packagist/php-v/tigusigalpa/gigachat-php.svg?style=flat-square)](https://packagist.org/packages/tigusigalpa/gigachat-php)
[![License](https://img.shields.io/packagist/l/tigusigalpa/gigachat-php.svg?style=flat-square)](https://packagist.org/packages/tigusigalpa/gigachat-php)

## 🚀 Возможности

- 🔌 **Простая интеграция** с GigaChat API
- 🔐 **Автоматическое управление** OAuth и токенами доступа
- 🎯 **Поддержка всех моделей** GigaChat (GigaChat, GigaChat-Pro, GigaChat-Max)
- 🛠 **Полная интеграция с Laravel** (8-12, Service Provider, Facades, конфигурация)
- 📝 **Поддержка диалогов** и одиночных запросов
- ⚡ **Streaming поддержка** для реального времени
- 🎨 **Helper методы** для упрощения работы
- 🔒 **Rate Limiting** и middleware
- 🧪 **Artisan команды** для тестирования
- 📚 **Подробная документация** и примеры

## 📦 Установка

### Установка из Packagist (рекомендуется)

Установите пакет через Composer:

```bash
composer require tigusigalpa/gigachat-php
```

### Для Laravel

Пакет автоматически регистрируется в Laravel благодаря автодискавери. Опубликуйте конфигурационный файл:

```bash
php artisan vendor:publish --tag=gigachat-config
```

## ⚙️ Настройка

### 1. Получение авторизационных данных

Для работы с GigaChat API необходимо получить авторизационные данные:

1. Зарегистрируйтесь в [личном кабинете Sber AI](https://developers.sber.ru/docs/ru/gigachat/quickstart/ind-create-project)
2. Создайте проект и получите **Client ID** и **Client Secret**
3. Сгенерируйте **Authorization Key** (Base64 от "Client ID:Client Secret")

> 💡 **Подробная инструкция**: [Создание проекта и получение ключей](https://developers.sber.ru/docs/ru/gigachat/quickstart/ind-create-project)

### 2. Настройка окружения

Добавьте в ваш `.env` файл:

```env
# Способ 1: Используя готовый Authorization Key
GIGACHAT_AUTH_KEY=your_base64_encoded_auth_key

# Способ 2: Используя Client ID и Client Secret (автоматически сгенерирует auth_key)
GIGACHAT_CLIENT_ID=your_client_id
GIGACHAT_CLIENT_SECRET=your_client_secret

# Дополнительные настройки
GIGACHAT_SCOPE=GIGACHAT_API_PERS
GIGACHAT_DEFAULT_MODEL=GigaChat
GIGACHAT_TEMPERATURE=0.7
GIGACHAT_MAX_TOKENS=1000

# Отключение проверки SSL (для решения проблем с сертификатами)
GIGACHAT_CERT_PATH=false
```

## 💡 Использование

### Базовое использование (без Laravel)

```php
<?php

use Tigusigalpa\GigaChat\Auth\TokenManager;
use Tigusigalpa\GigaChat\GigaChatClient;

// Создание токен-менеджера
$authKey = base64_encode('your_client_id:your_client_secret');
$tokenManager = new TokenManager($authKey);

// Создание клиента
$client = new GigaChatClient($tokenManager);

// Получение списка доступных моделей
$models = $client->models();
print_r($models);

// Отправка сообщения
$messages = [
    ['role' => 'user', 'content' => 'Привет! Как дела?']
];

$response = $client->chat($messages);
echo $response['choices'][0]['message']['content'];
```

### Использование с Laravel

После публикации конфигурации используйте Facade:

```php
<?php

use Tigusigalpa\GigaChat\Laravel\GigaChat;
use Tigusigalpa\GigaChat\Models\GigaChatModels;

// Простой вопрос-ответ
$answer = GigaChat::ask('Расскажи анекдот');
echo $answer;

// Получение списка моделей
$models = GigaChat::models();

// Отправка сообщения с параметрами
$response = GigaChat::chat([
    ['role' => 'user', 'content' => 'Объясни квантовую физику']
], [
    'temperature' => 0.7,
    'max_tokens' => 1000,
    'model' => GigaChatModels::GIGACHAT_2_PRO
]);

echo $response['choices'][0]['message']['content'];
```

### Работа с диалогами

```php
<?php

use Tigusigalpa\GigaChat\Laravel\GigaChat;
use Tigusigalpa\GigaChat\Laravel\GigaChatHelper;

// Создание диалога с системным промптом
$conversation = GigaChatHelper::conversation(
    'Ты полезный помощник программиста',
    'Как создать REST API в Laravel?'
);

$response = GigaChat::chat($conversation);
echo GigaChatHelper::extractContent($response);

// Продолжение диалога
$conversation = GigaChat::continueChat($conversation, 'А как добавить аутентификацию?');
```

### Streaming запросы

```php
<?php

use Tigusigalpa\GigaChat\Laravel\GigaChat;

$messages = [
    ['role' => 'user', 'content' => 'Напиши длинную историю о космосе']
];

// Способ 1: С callback функцией
GigaChat::chatStream($messages, [], function($event, $error) {
    if ($error) {
        echo "Ошибка: " . $error;
        return;
    }
    
    if ($event === '[DONE]') {
        echo "\n✅ Готово!";
        return;
    }
    
    if (isset($event['choices'][0]['delta']['content'])) {
        echo $event['choices'][0]['delta']['content'];
    }
});

// Способ 2: С генератором
$stream = GigaChat::chatStream($messages);
foreach ($stream as $event) {
    if (isset($event['choices'][0]['delta']['content'])) {
        echo $event['choices'][0]['delta']['content'];
    }
}
```

### Использование в моделях Eloquent

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Tigusigalpa\GigaChat\Laravel\Traits\HasGigaChat;

class Article extends Model
{
    use HasGigaChat;

    protected $fillable = ['title', 'content', 'category'];

    // Генерация резюме статьи
    public function generateSummary(): string
    {
        return $this->summarize('content');
    }

    // Генерация тегов
    public function generateTags(): array
    {
        return $this->generateTags('content', 5);
    }

    // Персонализированный контент
    public function generateRelatedContent(): string
    {
        return $this->generateContent(
            'Создай похожую статью на основе этой',
            ['title', 'category']
        );
    }
}
```

## 🤖 Доступные модели

GigaChat поддерживает несколько моделей для различных задач. Актуальный список моделей доступен в [официальной документации](https://developers.sber.ru/docs/ru/gigachat/models).

### Модели для генерации текста

| Модель              | Описание                                       | Использование                     |
|---------------------|------------------------------------------------|-----------------------------------|
| **GigaChat-2**      | Базовая модель второго поколения               | Общие задачи, диалоги             |
| **GigaChat-2-Pro**  | Продвинутая модель с улучшенными возможностями | Сложные задачи, креативное письмо |
| **GigaChat-2-Max**  | Максимальная модель для самых сложных задач    | Профессиональные задачи, анализ   |

### Модели для эмбеддингов

| Модель              | Описание                                       | Использование                     |
|---------------------|------------------------------------------------|-----------------------------------|
| **Embeddings**      | Базовая модель для векторного представления    | Поиск по смыслу, кластеризация    |
| **EmbeddingsGigaR** | Улучшенная модель для создания эмбеддингов    | Точный поиск, семантический анализ |

### Использование констант моделей

```php
use Tigusigalpa\GigaChat\Models\GigaChatModels;
use Tigusigalpa\GigaChat\Laravel\GigaChat;

// Использование констант для генерации
$response = GigaChat::chat($messages, [
    'model' => GigaChatModels::GIGACHAT_2_PRO
]);

// Получение списка доступных моделей
$generationModels = GigaChatModels::getGenerationModels();
$embeddingModels = GigaChatModels::getEmbeddingModels();

// Проверка валидности модели
if (GigaChatModels::isValidGenerationModel('GigaChat-2')) {
    // Модель валидна для генерации
}
```

## 🔧 Параметры генерации

Доступные параметры для настройки генерации:

```php
use Tigusigalpa\GigaChat\Models\GigaChatModels;

$options = [
    'model' => GigaChatModels::GIGACHAT_2_PRO, // Модель для использования
    'temperature' => 0.7,                      // Креативность (0.0 - 2.0)
    'top_p' => 0.9,                           // Nucleus sampling (0.0 - 1.0)
    'max_tokens' => 1000,                     // Максимальное количество токенов
    'repetition_penalty' => 1.1,              // Штраф за повторения (0.0 - 2.0)
    'update_interval' => 0                    // Интервал обновления для streaming
];

$response = GigaChat::chat($messages, $options);
```

## ⚠️ Обработка ошибок

SDK предоставляет специализированные исключения:

```php
<?php

use Tigusigalpa\GigaChat\Exceptions\GigaChatException;
use Tigusigalpa\GigaChat\Exceptions\AuthenticationException;
use Tigusigalpa\GigaChat\Exceptions\ValidationException;

try {
    $response = GigaChat::chat($messages);
} catch (AuthenticationException $e) {
    // Ошибки авторизации (неверные ключи, истекший токен)
    echo "Ошибка авторизации: " . $e->getMessage();
} catch (ValidationException $e) {
    // Ошибки валидации (неверный формат сообщений)
    echo "Ошибка валидации: " . $e->getMessage();
} catch (GigaChatException $e) {
    // Общие ошибки GigaChat API
    echo "Ошибка GigaChat: " . $e->getMessage();
}
```

## 🛠️ Artisan команды

SDK предоставляет удобные команды для работы через консоль:

```bash
# Тестирование подключения к API
php artisan gigachat:test

# Отправка сообщения
php artisan gigachat:chat "Привет, как дела?"

# Отправка с параметрами
php artisan gigachat:chat "Расскажи историю" --model=GigaChat-Pro --temperature=0.8 --max-tokens=500

# Streaming режим
php artisan gigachat:chat "Напиши длинный рассказ" --stream
```

## 🔒 Rate Limiting

Используйте middleware для ограничения количества запросов:

```php
// В routes/api.php
Route::middleware(['gigachat.rate_limit:30,1'])->group(function () {
    Route::post('/chat', [ChatController::class, 'chat']);
});

// Настройка в config/gigachat.php
'rate_limit' => [
    'enabled' => true,
    'max_attempts' => 60,        // Максимум запросов
    'decay_minutes' => 1,        // За период в минутах
],
```

## 📚 Примеры использования

### Чат-бот для Laravel

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tigusigalpa\GigaChat\Laravel\GigaChat;

class ChatController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000'
        ]);

        try {
            $response = GigaChat::askWithContext(
                'Ты дружелюбный помощник',
                $request->input('message'),
                ['temperature' => 0.7]
            );

            return response()->json([
                'success' => true,
                'reply' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

### Генерация контента

```php
<?php

use Tigusigalpa\GigaChat\Laravel\GigaChat;

class ContentGenerator
{
    public function generateArticle(string $topic, string $style = 'информационный'): string
    {
        return GigaChat::askWithContext(
            "Ты профессиональный копирайтер. Пиши в {$style} стиле.",
            "Напиши статью на тему: {$topic}",
            ['temperature' => 0.8, 'max_tokens' => 1500]
        );
    }

    public function translateText(string $text, string $targetLang = 'английский'): string
    {
        return GigaChat::ask(
            "Переведи следующий текст на {$targetLang} язык:\n\n{$text}",
            ['temperature' => 0.2]
        );
    }

    public function summarizeText(string $text, int $maxWords = 100): string
    {
        return GigaChat::ask(
            "Создай краткое изложение (не более {$maxWords} слов):\n\n{$text}",
            ['temperature' => 0.3, 'max_tokens' => $maxWords * 2]
        );
    }
}
```

### Streaming чат в реальном времени

```php
<?php

// В контроллере
public function streamChat(Request $request)
{
    $messages = [['role' => 'user', 'content' => $request->input('message')]];

    return response()->stream(function () use ($messages) {
        echo "data: " . json_encode(['type' => 'start']) . "\n\n";
        
        GigaChat::chatStream($messages, [], function($event, $error) {
            if ($error) {
                echo "data: " . json_encode(['type' => 'error', 'error' => $error]) . "\n\n";
                return;
            }
            
            if ($event === '[DONE]') {
                echo "data: " . json_encode(['type' => 'done']) . "\n\n";
                return;
            }
            
            if (isset($event['choices'][0]['delta']['content'])) {
                echo "data: " . json_encode([
                    'type' => 'content',
                    'content' => $event['choices'][0]['delta']['content']
                ]) . "\n\n";
            }
            
            flush();
        });
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
    ]);
}
```

## 🧪 Тестирование

### Запуск тестов пакета

```bash
# Тестирование подключения
php artisan gigachat:test

# Тестирование с собственным сообщением
php artisan gigachat:test "Проверка работы API"
```

### Тестирование в Laravel проекте

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tigusigalpa\GigaChat\Laravel\GigaChat;

class GigaChatTest extends TestCase
{
    public function test_gigachat_basic_functionality()
    {
        $response = GigaChat::ask('Привет!');
        
        $this->assertNotEmpty($response);
        $this->assertIsString($response);
    }

    public function test_gigachat_with_context()
    {
        $response = GigaChat::askWithContext(
            'Ты математик',
            'Сколько будет 2+2?'
        );
        
        $this->assertStringContainsString('4', $response);
    }
}
```

## ❓ Troubleshooting и FAQ

### Часто задаваемые вопросы

**Q: Как получить Client ID и Client Secret?**
A: Зарегистрируйтесь
в [личном кабинете Sber AI](https://developers.sber.ru/docs/ru/gigachat/quickstart/ind-create-project) и создайте
проект.

**Q: Что делать при ошибке "Invalid token response"?**
A: Проверьте правильность Client ID и Client Secret, а также доступность сервиса авторизации.

**Q: Как использовать собственные SSL сертификаты?**
A: Установите `GIGACHAT_CERT_PATH` в путь к файлу сертификата или `false` для отключения проверки.

**Q: Поддерживается ли работа в production?**
A: Да, SDK готов для использования в production. Убедитесь в правильной настройке SSL и rate limiting.

**Q: Где посмотреть информацию о тарифах?**
A: Актуальная информация о тарифах доступна в [официальной документации](https://developers.sber.ru/docs/ru/gigachat/api/tariffs).

### Решение проблем

**Проблема**: Ошибки SSL/TLS

При запросе к GigaChat API может возникать ошибка:
```
OAuth token request failed: cURL error 60: SSL certificate problem: self-signed certificate in certificate chain
```

**Решения:**

```bash
# Решение 1: Отключить проверку SSL (рекомендуется для разработки)
GIGACHAT_CERT_PATH=false

# Решение 2: Указать путь к сертификату (для продакшена)
GIGACHAT_CERT_PATH=/path/to/certificate.pem
```

После добавления `GIGACHAT_CERT_PATH=false` в файл `.env` очистите кэш конфигурации:
```bash
php artisan config:clear
php artisan config:cache
```

**Проблема**: Токен истекает слишком быстро

```php
// SDK автоматически обновляет токены, проверьте системное время
// и правильность настроек Client ID/Secret
```

**Проблема**: Rate limiting ошибки

```php
// Настройте лимиты в config/gigachat.php
'rate_limit' => [
    'max_attempts' => 30,    // Уменьшите количество запросов
    'decay_minutes' => 1,    // Или увеличьте период
],
```

### Отладка

Включите отладку для получения подробной информации:

```php
// В Laravel - включите логирование в config/gigachat.php
'logging' => [
    'enabled' => true,
    'channel' => 'daily',
    'level' => 'debug',
],

// Логирование запросов
use Illuminate\Support\Facades\Log;

try {
    $response = GigaChat::chat($messages);
    Log::info('GigaChat response', ['response' => $response]);
} catch (\Exception $e) {
    Log::error('GigaChat error', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
```

## 🛡️ Конфигурация

Полный список настроек в `config/gigachat.php`:

```php
<?php

return [
    // Authorization key (Base64(Client ID:Client Secret))
    'auth_key' => env('GIGACHAT_AUTH_KEY', null),

    // Альтернативно, укажите Client ID и Client Secret
    'client_id' => env('GIGACHAT_CLIENT_ID', null),
    'client_secret' => env('GIGACHAT_CLIENT_SECRET', null),

    // Область доступа API: GIGACHAT_API_PERS | GIGACHAT_API_B2B | GIGACHAT_API_CORP
    'scope' => env('GIGACHAT_SCOPE', 'GIGACHAT_API_PERS'),

    // Проверка TLS сертификатов
    'verify' => env('GIGACHAT_CERT_PATH', true),

    // Базовые URI
    'base_uri' => env('GIGACHAT_BASE_URI', 'https://gigachat.devices.sberbank.ru'),
    'oauth_uri' => env('GIGACHAT_OAUTH_URI', 'https://ngw.devices.sberbank.ru:9443'),

    // Модель по умолчанию
    'default_model' => env('GIGACHAT_DEFAULT_MODEL', 'GigaChat'),

    // Параметры генерации по умолчанию
    'default_options' => [
        'temperature' => (float) env('GIGACHAT_TEMPERATURE', 0.7),
        'max_tokens' => (int) env('GIGACHAT_MAX_TOKENS', 1000),
        'top_p' => (float) env('GIGACHAT_TOP_P', 0.9),
        'repetition_penalty' => (float) env('GIGACHAT_REPETITION_PENALTY', 1.1),
    ],

    // Rate limiting настройки
    'rate_limit' => [
        'enabled' => env('GIGACHAT_RATE_LIMIT_ENABLED', true),
        'max_attempts' => (int) env('GIGACHAT_RATE_LIMIT_MAX_ATTEMPTS', 60),
        'decay_minutes' => (int) env('GIGACHAT_RATE_LIMIT_DECAY_MINUTES', 1),
    ],

    // Настройки логирования
    'logging' => [
        'enabled' => env('GIGACHAT_LOGGING_ENABLED', false),
        'channel' => env('GIGACHAT_LOG_CHANNEL', 'default'),
        'level' => env('GIGACHAT_LOG_LEVEL', 'info'),
    ],
];
```

## ✅ Требования

- **PHP**: 8.2 или выше
- **Laravel**: 8+ (включая Laravel 11 и 12)
- **Guzzle HTTP**: 7.8.2+
- **Действующие учетные данные** Sber GigaChat API

## 📄 Лицензия

Этот проект лицензирован под лицензией MIT. Подробности см. в файле [LICENSE](LICENSE).

## 🔗 Полезные ссылки

### Официальная документация GigaChat
- 📝 **Регистрация и получение Client ID**: [Создание проекта](https://developers.sber.ru/docs/ru/gigachat/quickstart/ind-create-project)
- 🚀 **Начало работы с API**: [Быстрый старт](https://developers.sber.ru/docs/ru/gigachat/quickstart/ind-using-api)
- 📖 **Документация API**: [Справочник по API](https://developers.sber.ru/docs/ru/gigachat/api/reference/rest/gigachat-api)
- 🤖 **Список актуальных моделей**: [Описание моделей](https://developers.sber.ru/docs/ru/gigachat/models)
- 💰 **Тарифы и оплата**: [Тарифные планы](https://developers.sber.ru/docs/ru/gigachat/api/tariffs)

## 🤝 Поддержка

- 📧 **Email**: [создайте issue](https://github.com/tigusigalpa/gigachat-php/issues)
- 📖 **Документация**: [Sber GigaChat API](https://developers.sber.ru/docs/ru/gigachat/api/overview)
- 🐛 **Баг-репорты**: [GitHub Issues](https://github.com/tigusigalpa/gigachat-php/issues)
- 💬 **Обсуждения**: [GitHub Discussions](https://github.com/tigusigalpa/gigachat-php/discussions)

## 🧑‍💻 Участие в разработке

Мы приветствуем вклад в развитие проекта! Пожалуйста:

1. **Форкните** репозиторий
2. **Создайте ветку** для новой функции (`git checkout -b feature/amazing-feature`)
3. **Зафиксируйте изменения** (`git commit -m 'Add amazing feature'`)
4. **Отправьте в ветку** (`git push origin feature/amazing-feature`)
5. **Откройте Pull Request**

### Правила разработки

- Следуйте стандартам PSR-12
- Добавляйте тесты для новой функциональности
- Обновляйте документацию
- Используйте понятные commit сообщения

## 🛡️ Безопасность

Если вы обнаружили уязвимость безопасности, пожалуйста, отправьте email на sovletig@gmail.com вместо создания публичного
issue.

## 🆕 Laravel 12 Support

SDK полностью совместим с Laravel 12! Все возможности работают без изменений:

- ✅ Service Provider автоматически регистрируется
- ✅ Facade `GigaChat` доступен из коробки
- ✅ Artisan команды `gigachat:test` и `gigachat:chat`
- ✅ Middleware `gigachat.rate_limit`
- ✅ Trait `HasGigaChat` для моделей

## 📈 Roadmap

- [ ] Поддержка изображений (когда появится в GigaChat API)
- [ ] Кэширование ответов
- [ ] Метрики и аналитика
- [ ] WebSocket поддержка
- [ ] Интеграция с другими PHP фреймворками

---

**Сделано с ❤️ для сообщества PHP разработчиков**

> 💡 **Совет**: Начните с простых примеров и постепенно изучайте более продвинутые возможности SDK. Документация GigaChat
> API: https://developers.sber.ru/docs/ru/gigachat/api/overview
> use Tigusigalpa\\GigaChat\\Laravel\\GigaChat;

$messages = [
['role' => 'user', 'content' => 'Привет! Сгенерируй ответ.'],
];

$response = GigaChat::chat($messages, [
'model' => 'GigaChat',
'temperature' => 0.3,
]);

echo $response['choices'][0]['message']['content'] ?? '';

```

### Потоковая генерация (SSE)

```php
GigaChat::chatStream($messages, ['model' => 'GigaChat'], function ($event, $error) {
    if ($event === '[DONE]') {
        return; // завершение потока
    }
    $delta = $event['choices'][0]['delta']['content'] ?? '';
    echo $delta;
});
```

## Использование вне Laravel

```php
use Tigusigalpa\\GigaChat\\Auth\\TokenManager;
use Tigusigalpa\\GigaChat\\GigaChatClient;

$authKey = base64_encode('CLIENT_ID:CLIENT_SECRET');
$tm = new TokenManager($authKey, 'GIGACHAT_API_PERS');
$client = new GigaChatClient($tm);

$messages = [
    ['role' => 'user', 'content' => 'Hello!'],
];
$response = $client->chat($messages);
```

## Примечания по авторизации

- Для получения токена доступа отправляйте запрос `POST https://ngw.devices.sberbank.ru:9443/api/v2/oauth` c заголовками
  `Content-Type: application/x-www-form-urlencoded`, `Accept: application/json`,
  `Authorization: Basic Base64(ClientID:ClientSecret)` и обязательным заголовком `RqUID` (uuid4). В теле указывается
  `scope=GIGACHAT_API_PERS|GIGACHAT_API_B2B|GIGACHAT_API_CORP`.
- Токен действует ~30 минут, SDK обновляет его автоматически при необходимости.
- Если среда требует явный сертификат, задайте `GIGACHAT_CERT_PATH` на путь к цепочке сертификатов NGW (или оставьте
  `verify=true` для системного хранилища).

## Требования

- PHP >= 7.4
- guzzlehttp/guzzle ^7.8
- Laravel 8+ (опционально)

## Лицензия

MIT
