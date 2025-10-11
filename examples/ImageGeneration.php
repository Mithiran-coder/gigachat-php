<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Tigusigalpa\GigaChat\GigaChatClient;
use Tigusigalpa\GigaChat\Auth\TokenManager;

// Загружаем переменные окружения
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

try {
    // Инициализация клиента
    $tokenManager = new TokenManager(
        $_ENV['GIGACHAT_CLIENT_ID'] ?? 'your_client_id',
        $_ENV['GIGACHAT_CLIENT_SECRET'] ?? 'your_client_secret',
        $_ENV['GIGACHAT_SCOPE'] ?? 'GIGACHAT_API_PERS'
    );

    $client = new GigaChatClient($tokenManager);

    echo "=== Пример генерации изображений с GigaChat ===\n\n";

    // Пример 1: Простая генерация изображения
    echo "1. Простая генерация изображения:\n";
    $prompt = "Нарисуй красивый закат над морем";
    
    $response = $client->generateImage($prompt);
    $content = $response['choices'][0]['message']['content'] ?? '';
    
    echo "Промпт: {$prompt}\n";
    echo "Ответ модели: {$content}\n";
    
    // Извлекаем ID изображения
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
        $fileId = $matches[1];
        echo "ID изображения: {$fileId}\n";
        
        // Скачиваем изображение
        $imageData = $client->downloadImage($fileId);
        file_put_contents('sunset.jpg', base64_decode($imageData));
        echo "Изображение сохранено как sunset.jpg\n";
    }

    echo "\n" . str_repeat("-", 50) . "\n\n";

    // Пример 2: Генерация с системным промптом (стилизация)
    echo "2. Генерация с системным промптом (стиль Кандинского):\n";
    $prompt = "Нарисуй розового кота";
    $options = [
        'system_message' => 'Ты — Василий Кандинский'
    ];
    
    $response = $client->generateImage($prompt, $options);
    $content = $response['choices'][0]['message']['content'] ?? '';
    
    echo "Промпт: {$prompt}\n";
    echo "Системное сообщение: {$options['system_message']}\n";
    echo "Ответ модели: {$content}\n";

    echo "\n" . str_repeat("-", 50) . "\n\n";

    // Пример 3: Использование метода createImage (генерация + скачивание)
    echo "3. Генерация и скачивание в одном вызове:\n";
    $prompt = "Нарисуй космический корабль в стиле ретро-футуризма";
    $options = [
        'system_message' => 'Ты — художник-концептуалист научной фантастики'
    ];
    
    $result = $client->createImage($prompt, $options);
    
    echo "Промпт: {$prompt}\n";
    echo "ID изображения: {$result['file_id']}\n";
    echo "Размер данных: " . strlen($result['content']) . " символов (base64)\n";
    
    // Сохраняем изображение
    file_put_contents('spaceship.jpg', base64_decode($result['content']));
    echo "Изображение сохранено как spaceship.jpg\n";

    echo "\n" . str_repeat("-", 50) . "\n\n";

    // Пример 4: Обработка ошибок
    echo "4. Пример обработки ошибок:\n";
    try {
        // Попытка генерации без ключевого слова "нарисуй"
        $response = $client->generateImage("Красивый пейзаж");
        echo "Неожиданно: изображение сгенерировано без ключевого слова\n";
    } catch (Exception $e) {
        echo "Ошибка (ожидаемая): " . $e->getMessage() . "\n";
    }

    try {
        // Попытка скачать несуществующий файл
        $client->downloadImage('invalid-file-id');
    } catch (Exception $e) {
        echo "Ошибка при скачивании: " . $e->getMessage() . "\n";
    }

    echo "\n=== Примеры завершены ===\n";

} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
    echo "Убедитесь, что:\n";
    echo "1. Файл .env настроен правильно\n";
    echo "2. Указаны корректные CLIENT_ID и CLIENT_SECRET\n";
    echo "3. Есть доступ к интернету\n";
}
