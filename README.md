# Yandex Music Parser

Библиотека для парсинга информации об исполнителях и треках из Яндекс.Музыки с возможностью сохранения данных в базу данных и опциональной загрузкой треков.

## Возможности

- Получение информации об исполнителях (имя, количество подписчиков, ежемесячных слушателей, альбомов, треков)
- Получение информации о треках (название, продолжительность, альбом)
- Сохранение данных в базу данных MySQL
- Опциональная загрузка треков (MP3) в указанную директорию
- Обновление информации при повторном парсинге
- Автоматическое обнаружение и обработка новых треков

## Требования

- PHP 7.4+
- MySQL 5.7+ или MariaDB 10.2+
- Расширения PHP: PDO, cURL, JSON
- Composer для управления зависимостями
- Git для загрузки внешней библиотеки Yandex Music API

## Установка

### 1. Клонирование репозитория

```bash
git clone https://github.com/your-username/yandex-music-parser.git
cd yandex-music-parser
```

### 2. Установка зависимостей

```bash
composer install
```

Этот шаг автоматически загрузит все необходимые зависимости, включая внешнюю библиотеку Yandex Music API, которая будет размещена в директории `vendor/luckywins/yandex-music-api`.

### 3. Настройка базы данных

Выполните SQL-запросы из файла `DB.sql` для создания необходимой структуры базы данных:

```bash
mysql -u your_username -p < DB.sql
```

### 4. Настройка переменных окружения

Создайте файл `.env` на основе `.env.example`:

```bash
cp .env.example .env
```

Отредактируйте файл `.env`, указав ваши параметры:

```
# Параметры подключения к базе данных
DB_HOST=localhost
DB_NAME=yandex_music_parser
DB_USER=your_database_user
DB_PASS=your_database_password

# Токен Яндекс.Музыки
YANDEX_TOKEN=your_yandex_music_token

# Настройки загрузки
DOWNLOAD_PATH=downloads
ENABLE_DOWNLOADS=false
```

## Получение токена Яндекс.Музыки

Для работы с API Яндекс.Музыки вам потребуется токен. Вы можете получить его одним из следующих способов:

### Способ 1: Через браузер

1. Войдите в свой аккаунт Яндекс.Музыки в веб-браузере
2. Откройте инструменты разработчика (F12 или Ctrl+Shift+I)
3. Перейдите на вкладку "Network" (Сеть)
4. Обновите страницу и найдите запросы к `api.music.yandex.net`
5. Проверьте заголовки запроса и найдите `Authorization: OAuth ВАШ_ТОКЕН`

### Способ 2: Через предоставленную библиотеку

```php
// Создайте файл get_token.php с следующим содержимым
<?php
require_once 'vendor/luckywins/yandex-music-api/src/client.php';

$client = new Client("");
$client->fromCredentials("ваш_логин", "ваш_пароль", true);
// Эта команда выведет токен на экран
```

## Использование

### Пример базового использования

```php
<?php
// Подключаем автозагрузчик Composer
require_once __DIR__ . '/vendor/autoload.php';

// Подключаем библиотеку Yandex.Music API
require_once __DIR__ . '/vendor/luckywins/yandex-music-api/src/client.php';

// Загружаем переменные окружения
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    // Создаем подключение к базе данных
    $db = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Создаем парсер
    $parser = App\YandexMusicParser::create(
        $_ENV['YANDEX_TOKEN'],
        $db,
        $_ENV['DOWNLOAD_PATH'] ?? 'downloads'
    );
    
    // URL исполнителя для парсинга
    $artistUrl = 'https://music.yandex.ru/artist/36800/tracks'; // Например, Linkin Park
    
    // Определяем, нужно ли скачивать треки
    $downloadTracks = filter_var($_ENV['ENABLE_DOWNLOADS'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    
    // Запускаем парсинг
    $artist = $parser->parseArtist($artistUrl, $downloadTracks);
    
    // Выводим результаты
    echo "Исполнитель: {$artist['name']}\n";
    echo "Подписчиков: {$artist['subscribers_count']}\n";
    echo "Ежемесячных слушателей: {$artist['monthly_listeners']}\n";
    echo "Альбомов: {$artist['albums_count']}\n";
    echo "Треков: " . count($artist['tracks']) . "\n";
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
```

Полный пример использования смотрите в файле `Example.php`.

## Структура проекта

```
yandex-music-parser/
├── src/                            # Исходный код
│   ├── Contracts/                  # Интерфейсы
│   │   └── Interfaces.php          # Описания интерфейсов
│   ├── Entities/                   # Сущности
│   │   └── Entities.php            # Классы Artist и Track
│   ├── Exceptions/                 # Исключения
│   │   └── Exceptions.php          # Классы исключений
│   ├── Factory/                    # Фабрики
│   │   └── YandexMusicFactory.php  # Фабрика для создания компонентов
│   ├── Providers/                  # Провайдеры данных
│   │   └── YandexMusicProvider.php # Провайдер для Яндекс.Музыки
│   ├── Storage/                    # Хранилища данных
│   │   └── DatabaseStorage.php     # Хранилище в БД
│   ├── Downloader/                 # Загрузчики
│   │   └── TrackDownloader.php     # Загрузчик треков
│   └── YandexMusicParser.php       # Главный класс парсера
├── downloads/                      # Директория для загруженных треков
├── .env                            # Файл с переменными окружения
├── .env.example                    # Пример файла окружения
├── .gitignore                      # Игнорируемые файлы для Git
├── composer.json                   # Конфигурация Composer
├── DB.sql                          # SQL для создания базы данных
├── Example.php                     # Пример использования
└── README.md                       # Документация проекта
```

## Схема базы данных

### Таблица `artists`
- `id` - ID исполнителя из Яндекс.Музыки (первичный ключ)
- `name` - Имя исполнителя
- `subscribers_count` - Количество подписчиков
- `monthly_listeners` - Количество ежемесячных слушателей
- `albums_count` - Количество альбомов
- `tracks_count` - Количество треков
- `cover_url` - URL обложки исполнителя
- `created_at` - Дата создания записи
- `updated_at` - Дата обновления записи

### Таблица `tracks`
- `id` - ID трека из Яндекс.Музыки (первичный ключ)
- `artist_id` - ID исполнителя (внешний ключ)
- `title` - Название трека
- `duration_seconds` - Длительность в секундах
- `album_id` - ID альбома (если доступно)
- `album_title` - Название альбома (если доступно)
- `cover_url` - URL обложки трека
- `local_path` - Путь к локальному файлу (если скачан)
- `created_at` - Дата создания записи
- `updated_at` - Дата обновления записи

## Описание классов и интерфейсов

### Главный класс
- `YandexMusicParser` - Основной класс парсера, координирующий работу всех компонентов

### Интерфейсы
- `ParserInterface` - Интерфейс для парсера
- `ProviderInterface` - Интерфейс для провайдера данных
- `StorageInterface` - Интерфейс для хранилища данных
- `DownloaderInterface` - Интерфейс для загрузчика треков

### Сущности
- `Artist` - Класс, представляющий исполнителя
- `Track` - Класс, представляющий трек

### Провайдер данных
- `YandexMusicProvider` - Класс для работы с API Яндекс.Музыки

### Хранилище данных
- `DatabaseStorage` - Класс для работы с базой данных MySQL

### Загрузчик
- `TrackDownloader` - Класс для загрузки треков

### Фабрика
- `YandexMusicFactory` - Класс для создания компонентов парсера

### Исключения
- `ParserException` - Базовое исключение парсера
- `ApiException` - Исключение при работе с API
- `StorageException` - Исключение при работе с хранилищем
- `DownloadException` - Исключение при загрузке треков
- `ValidationException` - Исключение при валидации данных

## Особенности реализации

### Обработка новых треков

При повторном парсинге исполнителя парсер:
1. Проверяет, существует ли исполнитель в базе данных
2. Если существует, обновляет его информацию
3. Получает список всех треков исполнителя через API
4. Сравнивает с треками, сохраненными в базе данных
5. Добавляет только новые треки, не дублируя уже существующие

### Загрузка треков

Если опция загрузки треков включена, парсер:
1. Получает информацию о доступных ссылках на скачивание для каждого трека
2. Выбирает ссылку с наилучшим качеством (MP3 с максимальным битрейтом)
3. Скачивает трек и сохраняет его в указанную директорию
4. Сохраняет путь к файлу в базе данных

## Возможные проблемы и их решения

### Проблемы с API Яндекс.Музыки

Если вы получаете ошибки при работе с API:
- Убедитесь, что ваш токен действителен
- Проверьте наличие VPN, который может блокировать доступ к API
- Некоторые артисты могут быть недоступны из-за региональных ограничений

### Ошибки при загрузке треков

Если возникают проблемы при загрузке треков:
- Убедитесь, что указанная директория существует и доступна для записи
- Проверьте, что у вас есть доступ к скачиванию треков (премиум-подписка)
- Некоторые треки могут быть недоступны для скачивания из-за ограничений правообладателей

## Лицензия

Проект распространяется под лицензией MIT. Подробности в файле LICENSE.

## Благодарности

Проект использует библиотеку [LuckyWins/yandex-music-api](https://github.com/LuckyWins/yandex-music-api) для взаимодействия с API Яндекс.Музыки.

## Примечание

Данный парсер предназначен только для личного использования. Используйте его в соответствии с условиями использования сервиса Яндекс.Музыка. Автор не несет ответственности за любое нарушение условий использования сервиса.
