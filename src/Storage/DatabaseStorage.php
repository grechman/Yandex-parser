<?php

namespace App\Storage;

use PDO;
use App\Contracts\StorageInterface;
use App\Exceptions\StorageException;

/**
 * Class DatabaseStorage
 * 
 * Database storage implementation
 */
class DatabaseStorage implements StorageInterface
{
    /**
     * @var PDO Database connection
     */
    private $db;
    
    /**
     * DatabaseStorage constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    /**
     * Save artist to database
     * 
     * @param array $artist Artist data
     * @return bool Success status
     * @throws StorageException
     */
    public function saveArtist(array $artist): bool
    {
        try {
            // Check if artist already exists
            if ($this->getArtist($artist['id'])) {
                // Update existing artist
                $stmt = $this->db->prepare("
                    UPDATE artists SET
                        name = :name,
                        subscribers_count = :subscribers_count,
                        monthly_listeners = :monthly_listeners,
                        albums_count = :albums_count,
                        tracks_count = :tracks_count,
                        cover_url = :cover_url,
                        updated_at = NOW()
                    WHERE id = :id
                ");
            } else {
                // Insert new artist
                $stmt = $this->db->prepare("
                    INSERT INTO artists (
                        id, name, subscribers_count, monthly_listeners, 
                        albums_count, tracks_count, cover_url
                    ) VALUES (
                        :id, :name, :subscribers_count, :monthly_listeners, 
                        :albums_count, :tracks_count, :cover_url
                    )
                ");
            }
            
            return $stmt->execute([
                'id' => $artist['id'],
                'name' => $artist['name'],
                'subscribers_count' => $artist['subscribers_count'],
                'monthly_listeners' => $artist['monthly_listeners'],
                'albums_count' => $artist['albums_count'],
                'tracks_count' => $artist['tracks_count'],
                'cover_url' => $artist['cover_url']
            ]);
        } catch (\Exception $e) {
            throw new StorageException("Failed to save artist: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Get artist from database
     * 
     * @param int $artistId Artist ID
     * @return array|null Artist data or null if not found
     * @throws StorageException
     */
    public function getArtist(int $artistId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, subscribers_count, monthly_listeners, 
                       albums_count, tracks_count, cover_url
                FROM artists
                WHERE id = :id
            ");
            
            $stmt->execute(['id' => $artistId]);
            $artist = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $artist ?: null;
        } catch (\Exception $e) {
            throw new StorageException("Failed to get artist: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Save track to database
     * 
     * @param array $track Track data
     * @return bool Success status
     * @throws StorageException
     */
    public function saveTrack(array $track): bool
    {
        try {
            // Check if track already exists
            if ($this->getTrack($track['id'])) {
                // Update existing track
                $stmt = $this->db->prepare("
                    UPDATE tracks SET
                        artist_id = :artist_id,
                        title = :title,
                        duration_seconds = :duration_seconds,
                        album_id = :album_id,
                        album_title = :album_title,
                        cover_url = :cover_url,
                        local_path = :local_path,
                        updated_at = NOW()
                    WHERE id = :id
                ");
            } else {
                // Insert new track
                $stmt = $this->db->prepare("
                    INSERT INTO tracks (
                        id, artist_id, title, duration_seconds, 
                        album_id, album_title, cover_url, local_path
                    ) VALUES (
                        :id, :artist_id, :title, :duration_seconds, 
                        :album_id, :album_title, :cover_url, :local_path
                    )
                ");
            }
            
            return $stmt->execute([
                'id' => $track['id'],
                'artist_id' => $track['artist_id'],
                'title' => $track['title'],
                'duration_seconds' => $track['duration_seconds'],
                'album_id' => $track['album_id'],
                'album_title' => $track['album_title'],
                'cover_url' => $track['cover_url'],
                'local_path' => $track['local_path']
            ]);
        } catch (\Exception $e) {
            throw new StorageException("Failed to save track: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Get track from database
     * 
     * @param int $trackId Track ID
     * @return array|null Track data or null if not found
     * @throws StorageException
     */
    public function getTrack(int $trackId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, artist_id, title, duration_seconds, 
                       album_id, album_title, cover_url, local_path
                FROM tracks
                WHERE id = :id
            ");
            
            $stmt->execute(['id' => $trackId]);
            $track = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $track ?: null;
        } catch (\Exception $e) {
            throw new StorageException("Failed to get track: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Get tracks for artist
     * 
     * @param int $artistId Artist ID
     * @return array Tracks data
     * @throws StorageException
     */
    public function getArtistTracks(int $artistId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, artist_id, title, duration_seconds, 
                       album_id, album_title, cover_url, local_path
                FROM tracks
                WHERE artist_id = :artist_id
            ");
            
            $stmt->execute(['artist_id' => $artistId]);
            $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $tracks ?: [];
        } catch (\Exception $e) {
            throw new StorageException("Failed to get artist tracks: " . $e->getMessage(), 0, $e);
        }
    }
}