<?php

namespace App;

use App\Contracts\ParserInterface;
use App\Contracts\ProviderInterface;
use App\Contracts\StorageInterface;
use App\Contracts\DownloaderInterface;
use App\Exceptions\ParserException;
use App\Exceptions\ValidationException;
use App\Factory\YandexMusicFactory;

/**
 * Class YandexMusicParser
 * 
 * Main parser for Yandex Music artists and tracks
 */
class YandexMusicParser implements ParserInterface
{
    /**
     * @var ProviderInterface Data provider
     */
    private $provider;
    
    /**
     * @var StorageInterface Data storage
     */
    private $storage;
    
    /**
     * @var DownloaderInterface Track downloader
     */
    private $downloader;
    
    /**
     * YandexMusicParser constructor
     * 
     * @param ProviderInterface $provider
     * @param StorageInterface $storage
     * @param DownloaderInterface $downloader
     */
    public function __construct(
        ProviderInterface $provider,
        StorageInterface $storage,
        DownloaderInterface $downloader
    ) {
        $this->provider = $provider;
        $this->storage = $storage;
        $this->downloader = $downloader;
    }
    
    /**
     * Create parser from dependencies
     * 
     * @param string $token Yandex Music API token
     * @param \PDO $db Database connection
     * @param string $downloadDir Download directory
     * @return YandexMusicParser
     */
    public static function create(string $token, \PDO $db, string $downloadDir = 'downloads'): YandexMusicParser
    {
        $provider = YandexMusicFactory::createProvider($token);
        $storage = YandexMusicFactory::createStorage($db);
        $downloader = YandexMusicFactory::createDownloader($token, $downloadDir);
        
        return new self($provider, $storage, $downloader);
    }
    
    /**
     * Parse artist data from a Yandex Music URL
     * 
     * @param string $url Artist URL (e.g., https://music.yandex.ru/artist/какой-то номер/tracks)
     * @param bool $downloadTracks Whether to download tracks
     * @return array Artist data with tracks
     * @throws ParserException|ValidationException
     */
    public function parseArtist(string $url, bool $downloadTracks = false): array
    {
        // Extract artist ID from URL
        $artistId = $this->extractArtistId($url);
        
        // Get artist info from API to have the most up-to-date information
        $artistData = $this->provider->getArtistInfo($artistId);
        if (!$artistData) {
            throw new ParserException("Failed to fetch artist data for ID: $artistId");
        }
        
        // Check if artist already exists in database and update
        $existingArtist = $this->storage->getArtist($artistId);
        if ($existingArtist) {
            // Update artist info in the database
            $this->storage->saveArtist($artistData);
            
            // Get existing tracks and update with any new ones
            $tracks = $this->updateArtistTracks($artistId, $downloadTracks);
            $artistData['tracks'] = $tracks;
        } else {
            // Save new artist to database
            $this->storage->saveArtist($artistData);
            
            // Get and save all tracks
            $tracks = $this->getAndSaveTracks($artistId, $downloadTracks);
            $artistData['tracks'] = $tracks;
        }
        
        return $artistData;
    }
    
    /**
     * Update artist tracks - checking for new ones
     * 
     * @param int $artistId Artist ID
     * @param bool $downloadTracks Whether to download tracks
     * @return array Tracks data
     * @throws ParserException
     */
    private function updateArtistTracks(int $artistId, bool $downloadTracks = false): array
    {
        // Get existing tracks from database
        $existingTracks = $this->storage->getArtistTracks($artistId);
        $existingTrackIds = array_column($existingTracks, 'id');
        
        // Get all tracks from API
        $apiTracks = $this->provider->getArtistTracks($artistId);
        if (!$apiTracks) {
            return $existingTracks; // Return existing tracks if API fails
        }
        
        // Find new tracks
        $newTracks = [];
        foreach ($apiTracks as $trackData) {
            if (!in_array($trackData['id'], $existingTrackIds)) {
                // New track found
                $newTracks[] = $trackData;
            }
        }
        
        // Process new tracks
        foreach ($newTracks as $trackData) {
            // Download track if requested
            if ($downloadTracks) {
                $fileName = $this->formatFileName($trackData['artist_id'], $trackData['title']);
                $trackData['local_path'] = $this->downloader->downloadTrack($trackData['id'], $fileName);
            }
            
            // Save track to database
            $this->storage->saveTrack($trackData);
            
            // Add to existing tracks array
            $existingTracks[] = $trackData;
        }
        
        return $existingTracks;
    }
    
    /**
     * Get and save tracks for artist
     * 
     * @param int $artistId Artist ID
     * @param bool $downloadTracks Whether to download tracks
     * @return array Tracks data
     * @throws ParserException
     */
    private function getAndSaveTracks(int $artistId, bool $downloadTracks = false): array
    {
        // Get tracks from API
        $tracksData = $this->provider->getArtistTracks($artistId);
        if (!$tracksData) {
            return [];
        }
        
        $result = [];
        
        foreach ($tracksData as $trackData) {
            // Check if track already exists in database
            $existingTrack = $this->storage->getTrack($trackData['id']);
            if ($existingTrack) {
                $result[] = $existingTrack;
                continue;
            }
            
            // Download track if requested
            if ($downloadTracks) {
                $fileName = $this->formatFileName($trackData['artist_id'], $trackData['title']);
                $trackData['local_path'] = $this->downloader->downloadTrack($trackData['id'], $fileName);
            }
            
            // Save track to database
            $this->storage->saveTrack($trackData);
            $result[] = $trackData;
        }
        
        return $result;
    }
    
    /**
     * Extract artist ID from Yandex Music URL
     * 
     * @param string $url Artist URL
     * @return int Artist ID
     * @throws ValidationException
     */
    private function extractArtistId(string $url): int
    {
        if (preg_match('/artist\/(\d+)/', $url, $matches)) {
            return (int)$matches[1];
        }
        
        throw new ValidationException("Invalid Yandex Music artist URL: $url");
    }
    
    /**
     * Format a file name for downloading
     * 
     * @param int $artistId Artist ID
     * @param string $trackTitle Track title
     * @return string Formatted file name
     */
    private function formatFileName(int $artistId, string $trackTitle): string
    {
        // Remove invalid characters from title
        $safeTitle = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $trackTitle);
        return $artistId . '_' . $safeTitle;
    }
}