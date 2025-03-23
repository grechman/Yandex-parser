<?php

namespace App\Downloader;

use App\Contracts\DownloaderInterface;
use App\Exceptions\DownloadException;
use App\Providers\YandexMusicProvider;
use GuzzleHttp\Client as HttpClient; // Используем Guzzle потому что другое назначение
use GuzzleHttp\Exception\GuzzleException;

class TrackDownloader implements DownloaderInterface
{
    /**
     * @var YandexMusicProvider Provider for Yandex Music API
     */
    private $provider;
    
    /**
     * @var HttpClient HTTP client for downloading files
     */
    private $httpClient;
    
    /**
     * @var string Download directory
     */
    private $downloadDir;
    
    /**
     * TrackDownloader constructor
     * 
     * @param string $token Yandex Music API token
     * @param string $downloadDir Download directory
     */
    public function __construct(string $token, string $downloadDir)
    {
        $this->provider = new YandexMusicProvider($token);
        $this->httpClient = new HttpClient();
        $this->downloadDir = $this->validateDownloadDir($downloadDir);
    }
    
    /**
     * Download track
     * 
     * @param int $trackId Track ID
     * @param string $fileName File name without extension
     * @return string|null Local file path or null on failure
     * @throws DownloadException
     */
    public function downloadTrack(int $trackId, string $fileName): ?string
    {
        try {
            // Get track download info from provider
            $downloadInfo = $this->provider->getTrackDownloadInfo($trackId);
            if (!$downloadInfo || empty($downloadInfo['directLink'])) {
                return null;
            }
            
            // Sanitize the filename
            $fileName = $this->sanitizeFileName($fileName);
            
            // Download track
            $filePath = $this->downloadDir . '/' . $fileName . '.mp3';
            $this->downloadFile($downloadInfo['directLink'], $filePath);
            
            return $filePath;
        } catch (\Exception $e) {
            throw new DownloadException("Failed to download track: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Download file from URL
     * 
     * @param string $url Source URL
     * @param string $filePath Destination file path
     * @throws DownloadException
     */
    private function downloadFile(string $url, string $filePath): void
    {
        try {
            // Download file using Guzzle HTTP client
            $response = $this->httpClient->get($url, [
                'sink' => $filePath
            ]);
            
            if ($response->getStatusCode() !== 200) {
                throw new DownloadException("Failed to download file: HTTP status " . $response->getStatusCode());
            }
        } catch (GuzzleException $e) {
            // Не передаем $e в качестве предыдущего исключения, так как GuzzleException - это интерфейс
            throw new DownloadException("Failed to download file: " . $e->getMessage());
        }
    }
    
    /**
     * Validate and secure download directory
     * 
     * @param string $downloadDir Download directory
     * @return string Validated download directory path
     * @throws DownloadException
     */
    private function validateDownloadDir(string $downloadDir): string
    {
        // Remove trailing slashes
        $downloadDir = rtrim($downloadDir, '/\\');
        
        // Prevent path traversal by removing parent directory references
        $downloadDir = str_replace(['../', '..\\'], '', $downloadDir);
        
        // Make path absolute and safe
        $baseDir = realpath(__DIR__.'/../../');
        if ($baseDir === false) {
            throw new DownloadException("Cannot determine base directory");
        }
        
        $absolutePath = $baseDir.DIRECTORY_SEPARATOR.$downloadDir;
        
        // Create directory if it doesn't exist
        if (!is_dir($absolutePath)) {
            if (!mkdir($absolutePath, 0755, true)) {
                throw new DownloadException("Failed to create download directory: $absolutePath");
            }
        }
        
        // Verify the directory is writable
        if (!is_writable($absolutePath)) {
            throw new DownloadException("Download directory is not writable: $absolutePath");
        }
        
        return $absolutePath;
    }
    
    private function sanitizeFileName(string $fileName): string
    {
        $fileName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $fileName);
        
        if (empty($fileName)) {
            $fileName = 'track_' . time();
        }
        
        return $fileName;
    }
}