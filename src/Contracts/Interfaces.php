<?php

namespace App\Contracts;

interface ParserInterface
{
    /**
     * Parse artist data from a Yandex Music URL
     * 
     * @param string $url Yandex Music artist URL
     * @param bool $downloadTracks 
     * @return array Artist data with tracks
     */
    public function parseArtist(string $url, bool $downloadTracks = false): array;
}

interface ProviderInterface
{
    public function getArtistInfo(int $artistId): ?array;
    public function getArtistTracks(int $artistId): ?array;
}

interface StorageInterface
{
    public function saveArtist(array $artist): bool;
    public function getArtist(int $artistId): ?array;
    public function saveTrack(array $track): bool;
    public function getTrack(int $trackId): ?array;
    public function getArtistTracks(int $artistId): array;
}

interface DownloaderInterface
{
    /**
     * Download a track
     * 
     * @param int $trackId Track ID
     * @param string $fileName File name without extension
     * @return string|null Local file path or null on failure
     */
    public function downloadTrack(int $trackId, string $fileName): ?string;
}