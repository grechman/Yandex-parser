<?php

namespace App\Factory;

use PDO;
use App\Contracts\ProviderInterface;
use App\Contracts\StorageInterface;
use App\Contracts\DownloaderInterface;
use App\Providers\YandexMusicProvider;
use App\Storage\DatabaseStorage;
use App\Downloader\TrackDownloader;

// Моя любимая фабрика
class YandexMusicFactory
{
    /**
     * Create provider instance
     * 
     * @param string $token Yandex Music API token
     * @return ProviderInterface
     */
    public static function createProvider(string $token): ProviderInterface
    {
        return new YandexMusicProvider($token);
    }
    
    /**
     * Create storage instance
     * 
     * @param PDO $db Database connection
     * @return StorageInterface
     */
    public static function createStorage(PDO $db): StorageInterface
    {
        return new DatabaseStorage($db);
    }
    
    /**
     * Create downloader instance
     * 
     * @param string $token Yandex Music API token
     * @param string $downloadDir Download directory
     * @return DownloaderInterface
     */
    public static function createDownloader(string $token, string $downloadDir): DownloaderInterface
    {
        return new TrackDownloader($token, $downloadDir);
    }
}