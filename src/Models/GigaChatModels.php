<?php

namespace Tigusigalpa\GigaChat\Models;

/**
 * Константы доступных моделей GigaChat
 * 
 * @see https://developers.sber.ru/docs/ru/gigachat/models
 */
class GigaChatModels
{
    /**
     * Модели для генерации текста
     */
    
    /** @var string Базовая модель GigaChat второго поколения */
    public const GIGACHAT_2 = 'GigaChat-2';
    
    /** @var string Продвинутая модель GigaChat второго поколения */
    public const GIGACHAT_2_PRO = 'GigaChat-2-Pro';
    
    /** @var string Максимальная модель GigaChat второго поколения */
    public const GIGACHAT_2_MAX = 'GigaChat-2-Max';
    
    /** @var string Базовая модель GigaChat (устаревшая, для совместимости) */
    public const GIGACHAT = 'GigaChat';
    
    /** @var string Продвинутая модель GigaChat (устаревшая, для совместимости) */
    public const GIGACHAT_PRO = 'GigaChat-Pro';
    
    /** @var string Максимальная модель GigaChat (устаревшая, для совместимости) */
    public const GIGACHAT_MAX = 'GigaChat-Max';

    /**
     * Модели для векторного представления текста (эмбеддинги)
     */
    
    /** @var string Базовая модель для создания эмбеддингов */
    public const EMBEDDINGS = 'Embeddings';
    
    /** @var string Улучшенная модель для создания эмбеддингов */
    public const EMBEDDINGS_GIGA_R = 'EmbeddingsGigaR';

    /**
     * Получить все доступные модели для генерации текста
     * 
     * @return array<string, string> Массив моделей [константа => название]
     */
    public static function getGenerationModels(): array
    {
        return [
            'GIGACHAT_2' => self::GIGACHAT_2,
            'GIGACHAT_2_PRO' => self::GIGACHAT_2_PRO,
            'GIGACHAT_2_MAX' => self::GIGACHAT_2_MAX,
            'GIGACHAT' => self::GIGACHAT,
            'GIGACHAT_PRO' => self::GIGACHAT_PRO,
            'GIGACHAT_MAX' => self::GIGACHAT_MAX,
        ];
    }

    /**
     * Получить все доступные модели для эмбеддингов
     * 
     * @return array<string, string> Массив моделей [константа => название]
     */
    public static function getEmbeddingModels(): array
    {
        return [
            'EMBEDDINGS' => self::EMBEDDINGS,
            'EMBEDDINGS_GIGA_R' => self::EMBEDDINGS_GIGA_R,
        ];
    }

    /**
     * Получить все доступные модели
     * 
     * @return array<string, string> Массив всех моделей [константа => название]
     */
    public static function getAllModels(): array
    {
        return array_merge(
            self::getGenerationModels(),
            self::getEmbeddingModels()
        );
    }

    /**
     * Проверить, является ли модель валидной для генерации
     * 
     * @param string $model Название модели
     * @return bool
     */
    public static function isValidGenerationModel(string $model): bool
    {
        return in_array($model, self::getGenerationModels(), true);
    }

    /**
     * Проверить, является ли модель валидной для эмбеддингов
     * 
     * @param string $model Название модели
     * @return bool
     */
    public static function isValidEmbeddingModel(string $model): bool
    {
        return in_array($model, self::getEmbeddingModels(), true);
    }

    /**
     * Получить рекомендуемые модели второго поколения
     * 
     * @return array<string, string> Массив рекомендуемых моделей
     */
    public static function getRecommendedModels(): array
    {
        return [
            'GIGACHAT_2' => self::GIGACHAT_2,
            'GIGACHAT_2_PRO' => self::GIGACHAT_2_PRO,
            'GIGACHAT_2_MAX' => self::GIGACHAT_2_MAX,
        ];
    }

    /**
     * Получить описания моделей
     * 
     * @return array<string, array{name: string, description: string, use_cases: string[]}>
     */
    public static function getModelDescriptions(): array
    {
        return [
            self::GIGACHAT_2 => [
                'name' => 'GigaChat-2',
                'description' => 'Базовая модель второго поколения для общих задач',
                'use_cases' => ['Общение', 'Простые вопросы', 'Базовая генерация текста']
            ],
            self::GIGACHAT_2_PRO => [
                'name' => 'GigaChat-2-Pro',
                'description' => 'Продвинутая модель с улучшенными возможностями',
                'use_cases' => ['Сложные задачи', 'Креативное письмо', 'Анализ текста']
            ],
            self::GIGACHAT_2_MAX => [
                'name' => 'GigaChat-2-Max',
                'description' => 'Максимальная модель для самых сложных задач',
                'use_cases' => ['Профессиональные задачи', 'Глубокий анализ', 'Сложная генерация']
            ],
            self::EMBEDDINGS => [
                'name' => 'Embeddings',
                'description' => 'Базовая модель для создания векторных представлений текста',
                'use_cases' => ['Поиск по смыслу', 'Кластеризация', 'Сравнение текстов']
            ],
            self::EMBEDDINGS_GIGA_R => [
                'name' => 'EmbeddingsGigaR',
                'description' => 'Улучшенная модель для создания эмбеддингов',
                'use_cases' => ['Точный поиск', 'Семантический анализ', 'Рекомендательные системы']
            ],
        ];
    }
}
