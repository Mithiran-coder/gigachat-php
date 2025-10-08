<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Tigusigalpa\GigaChat\Laravel\Traits\HasGigaChat;

/**
 * Example User model with GigaChat integration
 */
class User extends Model
{
    use HasGigaChat;

    protected $fillable = [
        'name',
        'email',
        'bio',
        'interests',
    ];

    /**
     * Generate personalized greeting
     */
    public function generateGreeting(): string
    {
        $context = "Пользователь: {$this->name}";
        if ($this->bio) {
            $context .= "\nБиография: {$this->bio}";
        }
        if ($this->interests) {
            $context .= "\nИнтересы: {$this->interests}";
        }

        return $this->askGigaChatWithContext(
            'Ты дружелюбный помощник. Создавай персонализированные приветствия.',
            "Создай дружелюбное приветствие для этого пользователя:\n{$context}",
            ['temperature' => 0.8, 'max_tokens' => 150]
        );
    }

    /**
     * Generate content recommendations
     */
    public function getContentRecommendations(): string
    {
        $prompt = "Порекомендуй интересный контент для пользователя";
        
        return $this->generateContent($prompt, ['interests', 'bio'], [
            'temperature' => 0.7,
            'max_tokens' => 300
        ]);
    }

    /**
     * Generate bio summary
     */
    public function generateBioSummary(): string
    {
        if (!$this->bio) {
            return '';
        }

        return $this->summarize('bio', [
            'temperature' => 0.3,
            'max_tokens' => 100
        ]);
    }
}
