# 🚀 GigaChat PHP SDK

[Русская версия](README.md)

![GigaChat PHP SDK](https://github.com/user-attachments/assets/bcf42e4c-d410-47b3-844e-c298f68b0c52)

A comprehensive PHP SDK for working with Sber GigaChat API with Laravel integration. The package provides a convenient
interface for integrating with Sber GigaChat AI models, including support for streaming and regular requests.

[![Latest Version](https://img.shields.io/packagist/v/tigusigalpa/gigachat-php.svg?style=flat-square)](https://packagist.org/packages/tigusigalpa/gigachat-php)
[![PHP Version](https://img.shields.io/packagist/php-v/tigusigalpa/gigachat-php.svg?style=flat-square)](https://packagist.org/packages/tigusigalpa/gigachat-php)
[![License](https://img.shields.io/packagist/l/tigusigalpa/gigachat-php.svg?style=flat-square)](https://packagist.org/packages/tigusigalpa/gigachat-php)

## 🚀 Features

- 🔌 **Easy integration** with GigaChat API
- 🔐 **Automatic management** of OAuth and access tokens
- 🎯 **Support for all models** GigaChat (GigaChat, GigaChat-Pro, GigaChat-Max)
- 🛠 **Full Laravel integration** (8-12, Service Provider, Facades, configuration)
- 📝 **Support for conversations** and single requests
- ⚡ **Streaming support** for real-time responses
- 🎨 **Helper methods** for simplified usage
- 🔒 **Rate limiting** and middleware
- 🧪 **Artisan commands** for testing
- 📚 **Comprehensive documentation** and examples

## 📦 Installation

### Install from Packagist (recommended)

Install the package via Composer:

```bash
composer require tigusigalpa/gigachat-php
```

### For Laravel

The package automatically registers in Laravel thanks to auto-discovery. Publish the configuration file:

```bash
php artisan vendor:publish --tag=gigachat-config
```

## ⚙️ Configuration

### 1. Getting authorization credentials

To work with GigaChat API, you need to obtain authorization credentials:

1. Register in [Sber AI personal account](https://developers.sber.ru/docs/ru/gigachat/quickstart/ind-create-project)
2. Create a project and get **Client ID** and **Client Secret**
3. Generate **Authorization Key** (Base64 of "Client ID:Client Secret")

> 💡 **Detailed instructions
**: [Creating a project and getting keys](https://developers.sber.ru/docs/ru/gigachat/quickstart/ind-create-project)

### 2. Environment setup

Add to your `.env` file:

```env
# Method 1: Using ready Authorization Key
GIGACHAT_AUTH_KEY=your_base64_encoded_auth_key

# Method 2: Using Client ID and Client Secret (will auto-generate auth_key)
GIGACHAT_CLIENT_ID=your_client_id
GIGACHAT_CLIENT_SECRET=your_client_secret

# Additional settings
GIGACHAT_SCOPE=GIGACHAT_API_PERS
GIGACHAT_DEFAULT_MODEL=GigaChat
GIGACHAT_TEMPERATURE=0.7
GIGACHAT_MAX_TOKENS=1000

# Disable SSL verification (to solve certificate issues)
GIGACHAT_CERT_PATH=false
```

## 💡 Usage

### Basic usage (without Laravel)

```php
<?php

use Tigusigalpa\GigaChat\Auth\TokenManager;
use Tigusigalpa\GigaChat\GigaChatClient;

// Create token manager
$authKey = base64_encode('your_client_id:your_client_secret');
$tokenManager = new TokenManager($authKey);

// Create client
$client = new GigaChatClient($tokenManager);

// Get list of available models
$models = $client->models();
print_r($models);

// Send message
$messages = [
    ['role' => 'user', 'content' => 'Hello! How are you?']
];

$response = $client->chat($messages);
echo $response['choices'][0]['message']['content'];
```

### Usage with Laravel

After publishing configuration, use Facade:

```php
<?php

use Tigusigalpa\GigaChat\Laravel\GigaChat;
use Tigusigalpa\GigaChat\Models\GigaChatModels;

// Simple question-answer
$answer = GigaChat::ask('Tell me a joke');
echo $answer;

// Get list of models
$models = GigaChat::models();

// Send message with parameters
$response = GigaChat::chat([
    ['role' => 'user', 'content' => 'Explain quantum physics']
], [
    'temperature' => 0.7,
    'max_tokens' => 1000,
    'model' => GigaChatModels::GIGACHAT_2_PRO
]);

echo $response['choices'][0]['message']['content'];
```

### Working with conversations

```php
<?php

use Tigusigalpa\GigaChat\Laravel\GigaChat;
use Tigusigalpa\GigaChat\Laravel\GigaChatHelper;

// Create conversation with system prompt
$conversation = GigaChatHelper::conversation(
    'You are a helpful programming assistant',
    'How to create REST API in Laravel?'
);

$response = GigaChat::chat($conversation);
echo GigaChatHelper::extractContent($response);

// Continue conversation
$conversation = GigaChat::continueChat($conversation, 'How to add authentication?');
```

### Streaming requests

```php
<?php

use Tigusigalpa\GigaChat\Laravel\GigaChat;

$messages = [
    ['role' => 'user', 'content' => 'Write a long story about space']
];

// Method 1: With callback function
GigaChat::chatStream($messages, [], function($event, $error) {
    if ($error) {
        echo "Error: " . $error;
        return;
    }
    
    if ($event === '[DONE]') {
        echo "\n✅ Done!";
        return;
    }
    
    if (isset($event['choices'][0]['delta']['content'])) {
        echo $event['choices'][0]['delta']['content'];
    }
});

// Method 2: With generator
$stream = GigaChat::chatStream($messages);
foreach ($stream as $event) {
    if (isset($event['choices'][0]['delta']['content'])) {
        echo $event['choices'][0]['delta']['content'];
    }
}
```

### Usage in Eloquent models

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Tigusigalpa\GigaChat\Laravel\Traits\HasGigaChat;

class Article extends Model
{
    use HasGigaChat;

    protected $fillable = ['title', 'content', 'category'];

    // Generate article summary
    public function generateSummary(): string
    {
        return $this->summarize('content');
    }

    // Generate tags
    public function generateTags(): array
    {
        return $this->generateTags('content', 5);
    }

    // Personalized content
    public function generateRelatedContent(): string
    {
        return $this->generateContent(
            'Create a similar article based on this one',
            ['title', 'category']
        );
    }
}
```

## 🤖 Available models

GigaChat supports several models for different tasks. The current list of models is available in
the [official documentation](https://developers.sber.ru/docs/ru/gigachat/models).

### Text generation models

| Model              | Description                               | Usage                           |
|--------------------|-------------------------------------------|---------------------------------|
| **GigaChat-2**     | Base second-generation model              | General tasks, dialogues        |
| **GigaChat-2-Pro** | Advanced model with enhanced capabilities | Complex tasks, creative writing |
| **GigaChat-2-Max** | Maximum model for the most complex tasks  | Professional tasks, analysis    |

### Embedding models

| Model               | Description                            | Usage                             |
|---------------------|----------------------------------------|-----------------------------------|
| **Embeddings**      | Base model for vector representation   | Semantic search, clustering       |
| **EmbeddingsGigaR** | Enhanced model for creating embeddings | Precise search, semantic analysis |

### Using model constants

```php
use Tigusigalpa\GigaChat\Models\GigaChatModels;
use Tigusigalpa\GigaChat\Laravel\GigaChat;

// Using constants for generation
$response = GigaChat::chat($messages, [
    'model' => GigaChatModels::GIGACHAT_2_PRO
]);

// Getting list of available models
$generationModels = GigaChatModels::getGenerationModels();
$embeddingModels = GigaChatModels::getEmbeddingModels();

// Validating model
if (GigaChatModels::isValidGenerationModel('GigaChat-2')) {
    // Model is valid for generation
}
```

## 🔧 Generation parameters

Available parameters for generation customization:

```php
use Tigusigalpa\GigaChat\Models\GigaChatModels;

$options = [
    'model' => GigaChatModels::GIGACHAT_2_PRO, // Model to use
    'temperature' => 0.7,                      // Creativity (0.0 - 2.0)
    'top_p' => 0.9,                           // Nucleus sampling (0.0 - 1.0)
    'max_tokens' => 1000,                     // Maximum number of tokens
    'repetition_penalty' => 1.1,              // Repetition penalty (0.0 - 2.0)
    'update_interval' => 0                    // Update interval for streaming
];

$response = GigaChat::chat($messages, $options);
```

## ⚠️ Error handling

SDK provides specialized exceptions:

```php
<?php

use Tigusigalpa\GigaChat\Exceptions\GigaChatException;
use Tigusigalpa\GigaChat\Exceptions\AuthenticationException;
use Tigusigalpa\GigaChat\Exceptions\ValidationException;

try {
    $response = GigaChat::chat($messages);
} catch (AuthenticationException $e) {
    // Authentication errors (invalid keys, expired token)
    echo "Authentication error: " . $e->getMessage();
} catch (ValidationException $e) {
    // Validation errors (invalid message format)
    echo "Validation error: " . $e->getMessage();
} catch (GigaChatException $e) {
    // General GigaChat API errors
    echo "GigaChat error: " . $e->getMessage();
}
```

## 🛠️ Artisan commands

SDK provides convenient commands for console work:

```bash
# Test API connection
php artisan gigachat:test

# Send message
php artisan gigachat:chat "Hello, how are you?"

# Send with parameters
php artisan gigachat:chat "Tell a story" --model=GigaChat-Pro --temperature=0.8 --max-tokens=500

# Streaming mode
php artisan gigachat:chat "Write a long story" --stream
```

## 🔒 Rate limiting

Use middleware to limit the number of requests:

```php
// In routes/api.php
Route::middleware(['gigachat.rate_limit:30,1'])->group(function () {
    Route::post('/chat', [ChatController::class, 'chat']);
});

// Configuration in config/gigachat.php
'rate_limit' => [
    'enabled' => true,
    'max_attempts' => 60,        // Maximum requests
    'decay_minutes' => 1,        // Per period in minutes
],
```

## 📚 Usage examples

### Chatbot for Laravel

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
                'You are a friendly assistant',
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

### Content generation

```php
<?php

use Tigusigalpa\GigaChat\Laravel\GigaChat;

class ContentGenerator
{
    public function generateArticle(string $topic, string $style = 'informational'): string
    {
        return GigaChat::askWithContext(
            "You are a professional copywriter. Write in {$style} style.",
            "Write an article on the topic: {$topic}",
            ['temperature' => 0.8, 'max_tokens' => 1500]
        );
    }

    public function translateText(string $text, string $targetLang = 'English'): string
    {
        return GigaChat::ask(
            "Translate the following text to {$targetLang}:\n\n{$text}",
            ['temperature' => 0.2]
        );
    }

    public function summarizeText(string $text, int $maxWords = 100): string
    {
        return GigaChat::ask(
            "Create a brief summary (no more than {$maxWords} words):\n\n{$text}",
            ['temperature' => 0.3, 'max_tokens' => $maxWords * 2]
        );
    }
}
```

## 🧪 Testing

### Running package tests

```bash
# Test connection
php artisan gigachat:test

# Test with custom message
php artisan gigachat:test "API functionality test"
```

### Testing in Laravel project

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tigusigalpa\GigaChat\Laravel\GigaChat;

class GigaChatTest extends TestCase
{
    public function test_gigachat_basic_functionality()
    {
        $response = GigaChat::ask('Hello!');
        
        $this->assertNotEmpty($response);
        $this->assertIsString($response);
    }

    public function test_gigachat_with_context()
    {
        $response = GigaChat::askWithContext(
            'You are a mathematician',
            'What is 2+2?'
        );
        
        $this->assertStringContainsString('4', $response);
    }
}
```

## ❓ Troubleshooting and FAQ

### Frequently asked questions

**Q: How to get Client ID and Client Secret?**
A: Register in [Sber AI personal account](https://developers.sber.ru/docs/ru/gigachat/quickstart/ind-create-project) and
create a project.

**Q: What to do with "Invalid token response" error?**
A: Check the correctness of Client ID and Client Secret, as well as the availability of the authorization service.

**Q: How to use custom SSL certificates?**
A: Set `GIGACHAT_CERT_PATH` to the certificate file path or `false` to disable verification.

**Q: Is production usage supported?**
A: Yes, the SDK is ready for production use. Make sure SSL and rate limiting are properly configured.

**Q: Where can I find pricing information?**
A: Current pricing information is available in
the [official documentation](https://developers.sber.ru/docs/ru/gigachat/api/tariffs).

### Problem solving

**Problem**: SSL/TLS errors

When making requests to GigaChat API, you may encounter the error:

```
OAuth token request failed: cURL error 60: SSL certificate problem: self-signed certificate in certificate chain
```

**Solutions:**

```bash
# Solution 1: Disable SSL verification (recommended for development)
GIGACHAT_CERT_PATH=false

# Solution 2: Specify certificate path (for production)
GIGACHAT_CERT_PATH=/path/to/certificate.pem
```

After adding `GIGACHAT_CERT_PATH=false` to your `.env` file, clear the configuration cache:

```bash
php artisan config:clear
php artisan config:cache
```

**Problem**: Token expires too quickly

```php
// SDK automatically refreshes tokens, check system time
// and correctness of Client ID/Secret settings
```

**Problem**: Rate limiting errors

```php
// Configure limits in config/gigachat.php
'rate_limit' => [
    'max_attempts' => 30,    // Reduce number of requests
    'decay_minutes' => 1,    // Or increase period
],
```

## 🛡️ Configuration

Full list of settings in `config/gigachat.php`:

```php
<?php

return [
    // Authorization key (Base64(Client ID:Client Secret))
    'auth_key' => env('GIGACHAT_AUTH_KEY', null),

    // Alternatively, specify Client ID and Client Secret
    'client_id' => env('GIGACHAT_CLIENT_ID', null),
    'client_secret' => env('GIGACHAT_CLIENT_SECRET', null),

    // API access scope: GIGACHAT_API_PERS | GIGACHAT_API_B2B | GIGACHAT_API_CORP
    'scope' => env('GIGACHAT_SCOPE', 'GIGACHAT_API_PERS'),

    // TLS certificate verification
    'verify' => env('GIGACHAT_CERT_PATH', true),

    // Base URIs
    'base_uri' => env('GIGACHAT_BASE_URI', 'https://gigachat.devices.sberbank.ru'),
    'oauth_uri' => env('GIGACHAT_OAUTH_URI', 'https://ngw.devices.sberbank.ru:9443'),

    // Default model
    'default_model' => env('GIGACHAT_DEFAULT_MODEL', 'GigaChat'),

    // Default generation parameters
    'default_options' => [
        'temperature' => (float) env('GIGACHAT_TEMPERATURE', 0.7),
        'max_tokens' => (int) env('GIGACHAT_MAX_TOKENS', 1000),
        'top_p' => (float) env('GIGACHAT_TOP_P', 0.9),
        'repetition_penalty' => (float) env('GIGACHAT_REPETITION_PENALTY', 1.1),
    ],

    // Rate limiting settings
    'rate_limit' => [
        'enabled' => env('GIGACHAT_RATE_LIMIT_ENABLED', true),
        'max_attempts' => (int) env('GIGACHAT_RATE_LIMIT_MAX_ATTEMPTS', 60),
        'decay_minutes' => (int) env('GIGACHAT_RATE_LIMIT_DECAY_MINUTES', 1),
    ],

    // Logging settings
    'logging' => [
        'enabled' => env('GIGACHAT_LOGGING_ENABLED', false),
        'channel' => env('GIGACHAT_LOG_CHANNEL', 'default'),
        'level' => env('GIGACHAT_LOG_LEVEL', 'info'),
    ],
];
```

## ✅ Requirements

- **PHP**: 8.2 or higher
- **Laravel**: 8+ (including Laravel 11 and 12)
- **Guzzle HTTP**: 7.8.2+
- **Valid Sber GigaChat API credentials**

## 📄 License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## 🔗 Useful links

### Official GigaChat Documentation

- 📝 **Registration and getting Client ID
  **: [Create Project](https://developers.sber.ru/docs/ru/gigachat/quickstart/ind-create-project)
- 🚀 **Getting started with API**: [Quick Start](https://developers.sber.ru/docs/ru/gigachat/quickstart/ind-using-api)
- 📖 **API Documentation**: [API Reference](https://developers.sber.ru/docs/ru/gigachat/api/reference/rest/gigachat-api)
- 🤖 **Current models list**: [Models Description](https://developers.sber.ru/docs/ru/gigachat/models)
- 💰 **Pricing and billing**: [Pricing Plans](https://developers.sber.ru/docs/ru/gigachat/api/tariffs)

## 🤝 Support

- 📧 **Email**: [create issue](https://github.com/tigusigalpa/gigachat-php/issues)
- 📖 **Documentation**: [Sber GigaChat API](https://developers.sber.ru/docs/ru/gigachat/api/overview)
- 🐛 **Bug reports**: [GitHub Issues](https://github.com/tigusigalpa/gigachat-php/issues)
- 💬 **Discussions**: [GitHub Discussions](https://github.com/tigusigalpa/gigachat-php/discussions)

## 🧑‍💻 Contributing

We welcome contributions to the project! Please:

1. **Fork** the repository
2. **Create a branch** for new feature (`git checkout -b feature/amazing-feature`)
3. **Commit changes** (`git commit -m 'Add amazing feature'`)
4. **Push to branch** (`git push origin feature/amazing-feature`)
5. **Open Pull Request**

### Development guidelines

- Follow PSR-12 standards
- Add tests for new functionality
- Update documentation
- Use clear commit messages

## 🛡️ Security

If you discover a security vulnerability, please send an email to sovletig@gmail.com instead of creating a public issue.

## 🆕 Laravel 12 Support

SDK is fully compatible with Laravel 12! All features work without changes:

- ✅ Service Provider automatically registers
- ✅ Facade `GigaChat` available out of the box
- ✅ Artisan commands `gigachat:test` and `gigachat:chat`
- ✅ Middleware `gigachat.rate_limit`
- ✅ Trait `HasGigaChat` for models

## 📈 Roadmap

- [ ] Image support (when available in GigaChat API)
- [ ] Response caching
- [ ] Metrics and analytics
- [ ] WebSocket support
- [ ] Integration with other PHP frameworks

---

**Made with ❤️ for the PHP developer community**

> 💡 **Tip**: Start with simple examples and gradually explore more advanced SDK features. GigaChat API
> documentation: https://developers.sber.ru/docs/ru/gigachat/api/overview
