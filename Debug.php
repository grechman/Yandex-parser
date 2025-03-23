<?php
// Файл для отладки проблем с проектом

// 1. Проверяем существование файлов
echo "=== ПРОВЕРКА СУЩЕСТВОВАНИЯ ФАЙЛОВ ===\n";
$files = [
    '/vendor/luckywins/yandex-music-api/src/client.php',
    '/src/Contracts/Interfaces.php',
    '/src/Exceptions/Exceptions.php',
    '/src/Entities/Entities.php',
    '/src/Storage/DatabaseStorage.php',
    '/src/Providers/YandexMusicProvider.php',
    '/src/Downloader/TrackDownloader.php',
    '/src/Factory/YandexMusicFactory.php',
    '/src/YandexMusicParser.php',
    '/.env',
    '/config.php'
];

foreach ($files as $file) {
    $path = __DIR__ . $file;
    echo $path . ": " . (file_exists($path) ? "СУЩЕСТВУЕТ" : "ОТСУТСТВУЕТ") . "\n";
    if (file_exists($path) && !str_ends_with($path, '.php')) {
        echo "  ВНИМАНИЕ: Файл $path не имеет расширения .php\n";
    }
}
echo "\n";

// 2. Проверяем содержимое файлов на наличие пространств имен
echo "=== ПРОВЕРКА ПРОСТРАНСТВ ИМЕН В ФАЙЛАХ ===\n";
foreach ($files as $file) {
    $path = __DIR__ . $file;
    if (file_exists($path) && (str_ends_with($path, '.php') || !str_contains($path, '.'))) {
        $content = file_get_contents($path);
        $namespaceMatch = [];
        preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch);
        echo $path . ": " . (isset($namespaceMatch[1]) ? "namespace {$namespaceMatch[1]}" : "БЕЗ NAMESPACE") . "\n";
    }
}
echo "\n";

// 3. Проверяем .env файл
echo "=== ПРОВЕРКА ФАЙЛА .ENV ===\n";
if (file_exists(__DIR__ . '/.env')) {
    $envContent = file_get_contents(__DIR__ . '/.env');
    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') continue;
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            echo "$key: " . (empty($value) ? "ПУСТО" : "УСТАНОВЛЕНО") . "\n";
        }
    }
} else {
    echo ".env файл не найден!\n";
}
echo "\n";

// 4. Проверяем подключение к базе данных
echo "=== ПРОВЕРКА ПОДКЛЮЧЕНИЯ К БАЗЕ ДАННЫХ ===\n";
try {
    // Пытаемся прочитать параметры из .env вручную
    $dbHost = $dbName = $dbUser = $dbPass = null;
    if (file_exists(__DIR__ . '/.env')) {
        $envContent = file_get_contents(__DIR__ . '/.env');
        $lines = explode("\n", $envContent);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') continue;
            
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                if ($key === 'DB_HOST') $dbHost = $value;
                if ($key === 'DB_NAME') $dbName = $value;
                if ($key === 'DB_USER') $dbUser = $value;
                if ($key === 'DB_PASS') $dbPass = $value;
            }
        }
    }
    
    echo "Параметры подключения:\n";
    echo "  DB_HOST: " . ($dbHost ?: "НЕ НАЙДЕНО") . "\n";
    echo "  DB_NAME: " . ($dbName ?: "НЕ НАЙДЕНО") . "\n";
    echo "  DB_USER: " . ($dbUser ?: "НЕ НАЙДЕНО") . "\n";
    echo "  DB_PASS: " . (empty($dbPass) ? "ПУСТО" : "УСТАНОВЛЕНО") . "\n\n";
    
    if ($dbHost && $dbName && $dbUser !== null) {
        echo "Попытка подключения к базе данных...\n";
        $db = new PDO(
            "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", 
            $dbUser, 
            $dbPass, 
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "Подключение успешно!\n";
        
        // Проверка таблиц
        echo "\nПроверка наличия таблиц:\n";
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
        
        if (in_array('artists', $tables)) {
            echo "\nСтруктура таблицы artists:\n";
            $stmt = $db->query("DESCRIBE artists");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $column) {
                echo "  - {$column['Field']} ({$column['Type']})" . 
                     ($column['Key'] === 'PRI' ? " PRIMARY KEY" : "") . "\n";
            }
        }
        
        if (in_array('tracks', $tables)) {
            echo "\nСтруктура таблицы tracks:\n";
            $stmt = $db->query("DESCRIBE tracks");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $column) {
                echo "  - {$column['Field']} ({$column['Type']})" . 
                     ($column['Key'] === 'PRI' ? " PRIMARY KEY" : "") . "\n";
            }
        }
    } else {
        echo "Недостаточно параметров для подключения к базе данных.\n";
    }
} catch (Exception $e) {
    echo "ОШИБКА подключения к базе данных: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Пробуем подключить файлы PHP один за другим
echo "=== ПРОВЕРКА ПОДКЛЮЧЕНИЯ ФАЙЛОВ PHP ===\n";
foreach ($files as $file) {
    $path = __DIR__ . $file;
    if (file_exists($path) && (str_ends_with($path, '.php') || !str_contains($path, '.'))) {
        echo "Подключение $path...\n";
        try {
            // Подключаем файл в изолированном окружении
            $success = @include_once $path;
            echo $success !== false ? "  УСПЕШНО\n" : "  ОШИБКА\n";
        } catch (Throwable $e) {
            echo "  ОШИБКА: " . $e->getMessage() . "\n";
        }
    }
}
echo "\n";

// 6. Вывод информации о PHP и расширениях
echo "=== ИНФОРМАЦИЯ О PHP ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Loaded Extensions: " . implode(', ', get_loaded_extensions()) . "\n\n";

echo "=== ЗАВЕРШЕНО ===\n";