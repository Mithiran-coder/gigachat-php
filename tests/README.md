# Тесты GigaChat PHP SDK

Этот каталог содержит полный набор тестов для GigaChat PHP SDK, включая unit-тесты, интеграционные тесты и тесты валидации.

## Структура тестов

```
tests/
├── TestCase.php                           # Базовый класс для всех тестов
├── GigaChatClientTest.php                 # Тесты основного клиента
├── Auth/
│   └── TokenManagerTest.php              # Тесты управления токенами
├── Laravel/
│   └── GigaChatFacadeTest.php            # Тесты Laravel фасада
├── Integration/
│   └── ImageGenerationIntegrationTest.php # Интеграционные тесты
├── Validation/
│   └── MessageValidationTest.php         # Тесты валидации
└── README.md                             # Этот файл
```

## Запуск тестов

### Установка зависимостей

```bash
composer install --dev
```

### Запуск всех тестов

```bash
composer test
# или
vendor/bin/phpunit
```

### Запуск конкретной группы тестов

```bash
# Unit тесты
vendor/bin/phpunit tests/GigaChatClientTest.php
vendor/bin/phpunit tests/Auth/
vendor/bin/phpunit tests/Laravel/

# Тесты валидации
vendor/bin/phpunit tests/Validation/

# Интеграционные тесты (требуют API ключи)
GIGACHAT_INTEGRATION_TEST=true vendor/bin/phpunit tests/Integration/
```

### Запуск с покрытием кода

```bash
composer test-coverage
```

## Интеграционные тесты

Интеграционные тесты требуют реальные API ключи GigaChat и по умолчанию отключены.

### Настройка для интеграционных тестов

1. Создайте файл `.env.testing`:

```env
GIGACHAT_INTEGRATION_TEST=true
GIGACHAT_CLIENT_ID=your_client_id
GIGACHAT_CLIENT_SECRET=your_client_secret
GIGACHAT_SCOPE=GIGACHAT_API_PERS
```

2. Запустите интеграционные тесты:

```bash
# Загрузить переменные окружения и запустить тесты
export $(cat .env.testing | xargs) && vendor/bin/phpunit tests/Integration/
```

### Что тестируют интеграционные тесты

- ✅ Реальная генерация изображений
- ✅ Скачивание сгенерированных изображений
- ✅ Работа с различными стилями и промптами
- ✅ Полный workflow создания изображений
- ✅ Обработка сложных промптов
- ✅ Последовательная генерация нескольких изображений

## Покрываемый функционал

### 🔧 GigaChatClient
- ✅ Получение списка моделей
- ✅ Отправка chat сообщений
- ✅ Генерация изображений
- ✅ Скачивание изображений
- ✅ Полный workflow создания изображений
- ✅ Извлечение ID изображений из ответов
- ✅ Валидация входных данных
- ✅ Обработка ошибок

### 🔐 TokenManager
- ✅ Получение access токенов
- ✅ Кеширование токенов
- ✅ Обновление истекших токенов
- ✅ Обработка ошибок аутентификации
- ✅ Создание из auth key
- ✅ Валидация формата ключей
- ✅ Очистка кеша токенов
- ✅ Генерация уникальных request ID

### 🎨 Laravel Facade
- ✅ Простые вопросы (ask)
- ✅ Вопросы с контекстом (askWithContext)
- ✅ Продолжение диалогов (continueChat)
- ✅ Быстрая генерация изображений (drawImage)
- ✅ Генерация в стиле художника (drawImageInStyle)
- ✅ Извлечение ID изображений (extractImageId)
- ✅ Обработка пустых ответов
- ✅ Работа с различными форматами кавычек

### ✅ Валидация
- ✅ Валидация структуры сообщений
- ✅ Проверка обязательных полей
- ✅ Валидация ролей сообщений
- ✅ Проверка содержимого сообщений
- ✅ Валидация промптов для изображений
- ✅ Проверка ID файлов
- ✅ Обработка множественных сообщений
- ✅ Корректные индексы ошибок

## Моки и тестовые данные

### Создание mock ответов

```php
// Обычный ответ чата
$response = $this->createMockResponse([
    'content' => 'Тестовый ответ',
    'finish_reason' => 'stop'
]);

// Ответ с изображением
$imageResponse = $this->createMockImageResponse('test-image-id');

// Mock base64 данные изображения
$imageData = $this->createMockImageData();
```

### Настройка HTTP клиента

```php
$this->mockHttpClient->shouldReceive('post')
    ->with('/api/v1/chat/completions', [
        'headers' => [...],
        'json' => [...]
    ])
    ->andReturn(new Response(200, [], json_encode($response)));
```

## Примеры тестов

### Unit тест для генерации изображений

```php
/** @test */
public function it_can_generate_image()
{
    $prompt = "Нарисуй красивый пейзаж";
    $expectedResponse = $this->createMockImageResponse('test-image-123');

    $this->mockHttpClient->shouldReceive('post')
        ->with('/api/v1/chat/completions', Mockery::any())
        ->andReturn(new Response(200, [], json_encode($expectedResponse)));

    $result = $this->client->generateImage($prompt);

    $this->assertEquals($expectedResponse, $result);
    $this->assertStringContains('test-image-123', $result['choices'][0]['message']['content']);
}
```

### Интеграционный тест

```php
/** @test */
public function it_can_create_image_with_full_workflow()
{
    $prompt = "Нарисуй звезду";
    
    $result = $this->client->createImage($prompt);

    $this->assertArrayHasKey('content', $result);
    $this->assertArrayHasKey('file_id', $result);
    $this->assertNotEmpty($result['content']);
    
    // Проверка валидности base64
    $decoded = base64_decode($result['content'], true);
    $this->assertNotFalse($decoded);
}
```

## Отладка тестов

### Включение подробного вывода

```bash
vendor/bin/phpunit --verbose
vendor/bin/phpunit --debug
```

### Запуск отдельного теста

```bash
vendor/bin/phpunit --filter "test_method_name"
vendor/bin/phpunit tests/GigaChatClientTest.php::it_can_generate_image
```

### Проверка покрытия

```bash
composer test-coverage
# Откройте coverage/index.html в браузере
```

## Добавление новых тестов

1. Создайте новый файл в соответствующей папке
2. Наследуйтесь от `TestCase`
3. Используйте методы `createMockResponse()` и `createMockImageResponse()`
4. Добавьте аннотацию `/** @test */` к методам тестов
5. Используйте описательные имена методов: `it_can_do_something()`

## Continuous Integration

Тесты настроены для запуска в CI/CD pipeline:

```yaml
# .github/workflows/tests.yml
- name: Run Tests
  run: composer test

- name: Run Integration Tests
  env:
    GIGACHAT_INTEGRATION_TEST: true
    GIGACHAT_CLIENT_ID: ${{ secrets.GIGACHAT_CLIENT_ID }}
    GIGACHAT_CLIENT_SECRET: ${{ secrets.GIGACHAT_CLIENT_SECRET }}
  run: vendor/bin/phpunit tests/Integration/
```

## Требования

- PHP 8.2+
- PHPUnit 10.0+
- Mockery 1.6+
- Composer

## Полезные команды

```bash
# Установка зависимостей для разработки
composer install --dev

# Запуск всех тестов
composer test

# Запуск с покрытием
composer test-coverage

# Проверка синтаксиса
composer validate

# Автозагрузка классов
composer dump-autoload
```
