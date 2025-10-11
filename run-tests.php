<?php

/**
 * Простой скрипт для запуска тестов GigaChat PHP SDK
 * 
 * Использование:
 * php run-tests.php [опции]
 * 
 * Опции:
 * --unit          Запустить только unit тесты
 * --integration   Запустить интеграционные тесты (требуют API ключи)
 * --validation    Запустить тесты валидации
 * --coverage      Запустить с покрытием кода
 * --verbose       Подробный вывод
 * --help          Показать справку
 */

class TestRunner
{
    private array $options;
    private string $phpunitPath;

    public function __construct(array $argv)
    {
        $this->options = $this->parseArguments($argv);
        $this->phpunitPath = $this->findPhpUnit();
    }

    public function run(): int
    {
        if (isset($this->options['help'])) {
            $this->showHelp();
            return 0;
        }

        echo "🚀 GigaChat PHP SDK Test Runner\n";
        echo str_repeat("=", 50) . "\n\n";

        if (!$this->checkRequirements()) {
            return 1;
        }

        $commands = $this->buildCommands();
        $exitCode = 0;

        foreach ($commands as $name => $command) {
            echo "📋 Запуск: {$name}\n";
            echo "💻 Команда: {$command}\n\n";

            $result = $this->executeCommand($command);
            
            if ($result !== 0) {
                echo "❌ Тесты {$name} завершились с ошибкой (код: {$result})\n\n";
                $exitCode = $result;
            } else {
                echo "✅ Тесты {$name} прошли успешно\n\n";
            }
        }

        if ($exitCode === 0) {
            echo "🎉 Все тесты прошли успешно!\n";
        } else {
            echo "💥 Некоторые тесты завершились с ошибками\n";
        }

        return $exitCode;
    }

    private function parseArguments(array $argv): array
    {
        $options = [];
        
        for ($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];
            
            if (str_starts_with($arg, '--')) {
                $option = substr($arg, 2);
                $options[$option] = true;
            }
        }

        return $options;
    }

    private function findPhpUnit(): string
    {
        $paths = [
            __DIR__ . '/vendor/bin/phpunit',
            __DIR__ . '/vendor/bin/phpunit.bat',
            'phpunit'
        ];

        foreach ($paths as $path) {
            if (file_exists($path) || $this->commandExists($path)) {
                return $path;
            }
        }

        throw new RuntimeException('PHPUnit не найден. Установите зависимости: composer install --dev');
    }

    private function commandExists(string $command): bool
    {
        $result = shell_exec(sprintf('which %s 2>/dev/null || where %s 2>nul', 
            escapeshellarg($command), 
            escapeshellarg($command)
        ));
        
        return !empty($result);
    }

    private function checkRequirements(): bool
    {
        // Проверка PHP версии
        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            echo "❌ Требуется PHP 8.2 или выше. Текущая версия: " . PHP_VERSION . "\n";
            return false;
        }

        // Проверка composer.json
        if (!file_exists(__DIR__ . '/composer.json')) {
            echo "❌ Файл composer.json не найден\n";
            return false;
        }

        // Проверка vendor
        if (!is_dir(__DIR__ . '/vendor')) {
            echo "❌ Папка vendor не найдена. Выполните: composer install --dev\n";
            return false;
        }

        // Проверка тестов
        if (!is_dir(__DIR__ . '/tests')) {
            echo "❌ Папка tests не найдена\n";
            return false;
        }

        echo "✅ Все требования выполнены\n\n";
        return true;
    }

    private function buildCommands(): array
    {
        $commands = [];
        $baseCommand = $this->phpunitPath;

        if (isset($this->options['verbose'])) {
            $baseCommand .= ' --verbose';
        }

        if (isset($this->options['coverage'])) {
            $baseCommand .= ' --coverage-html coverage';
        }

        if (isset($this->options['unit'])) {
            $commands['Unit тесты'] = $baseCommand . ' tests/GigaChatClientTest.php tests/Auth/ tests/Laravel/';
        } elseif (isset($this->options['integration'])) {
            $this->setupIntegrationEnvironment();
            $commands['Интеграционные тесты'] = $baseCommand . ' tests/Integration/';
        } elseif (isset($this->options['validation'])) {
            $commands['Тесты валидации'] = $baseCommand . ' tests/Validation/';
        } else {
            // Запуск всех тестов кроме интеграционных
            $commands['Unit тесты'] = $baseCommand . ' tests/GigaChatClientTest.php tests/Auth/ tests/Laravel/ tests/Validation/';
            
            // Интеграционные тесты только если есть переменные окружения
            if ($this->hasIntegrationCredentials()) {
                $this->setupIntegrationEnvironment();
                $commands['Интеграционные тесты'] = $baseCommand . ' tests/Integration/';
            } else {
                echo "⚠️  Интеграционные тесты пропущены (нет API ключей)\n";
                echo "   Для запуска установите: GIGACHAT_CLIENT_ID, GIGACHAT_CLIENT_SECRET\n\n";
            }
        }

        return $commands;
    }

    private function setupIntegrationEnvironment(): void
    {
        putenv('GIGACHAT_INTEGRATION_TEST=true');
        
        // Попытка загрузить из .env.testing
        $envFile = __DIR__ . '/.env.testing';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    putenv(trim($line));
                }
            }
        }
    }

    private function hasIntegrationCredentials(): bool
    {
        return !empty(getenv('GIGACHAT_CLIENT_ID')) && !empty(getenv('GIGACHAT_CLIENT_SECRET'));
    }

    private function executeCommand(string $command): int
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $process = proc_open($command, $descriptors, $pipes, __DIR__);

        if (!is_resource($process)) {
            echo "❌ Не удалось запустить команду: {$command}\n";
            return 1;
        }

        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if (!empty($output)) {
            echo $output;
        }

        if (!empty($error) && $exitCode !== 0) {
            echo "Ошибки:\n" . $error . "\n";
        }

        return $exitCode;
    }

    private function showHelp(): void
    {
        echo "🚀 GigaChat PHP SDK Test Runner\n\n";
        echo "Использование: php run-tests.php [опции]\n\n";
        echo "Опции:\n";
        echo "  --unit          Запустить только unit тесты\n";
        echo "  --integration   Запустить интеграционные тесты (требуют API ключи)\n";
        echo "  --validation    Запустить тесты валидации\n";
        echo "  --coverage      Запустить с покрытием кода\n";
        echo "  --verbose       Подробный вывод\n";
        echo "  --help          Показать эту справку\n\n";
        echo "Примеры:\n";
        echo "  php run-tests.php                    # Все тесты\n";
        echo "  php run-tests.php --unit             # Только unit тесты\n";
        echo "  php run-tests.php --integration      # Только интеграционные\n";
        echo "  php run-tests.php --coverage         # С покрытием кода\n";
        echo "  php run-tests.php --unit --verbose   # Unit тесты с подробным выводом\n\n";
        echo "Для интеграционных тестов установите переменные окружения:\n";
        echo "  GIGACHAT_CLIENT_ID=your_client_id\n";
        echo "  GIGACHAT_CLIENT_SECRET=your_client_secret\n";
        echo "  GIGACHAT_SCOPE=GIGACHAT_API_PERS\n\n";
        echo "Или создайте файл .env.testing с этими переменными.\n";
    }
}

// Запуск
try {
    $runner = new TestRunner($argv);
    exit($runner->run());
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}
