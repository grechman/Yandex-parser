<?php

namespace App\Providers;

use App\Contracts\ProviderInterface;
use App\Exceptions\ApiException;
use Client; // From the LuckyWins/yandex-music-api repository

class YandexMusicProvider implements ProviderInterface
{
    /**
     * @var Client Yandex Music API client
     */
    private $client;
    
    /**
     * @var string Yandex Music API token
     */
    private $token;
    
    /**
     * YandexMusicProvider constructor
     * 
     * @param string $token Yandex Music API token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
        
        // Initialize the Yandex Music API client from the external library
        $this->client = new Client($token);
        
        // Verify that the client is authenticated
        $account = $this->client->getAccount();
        if ($account === null) {
            throw new ApiException("Failed to authenticate with Yandex Music API. Invalid token.");
        }
    }
    
    /**
     * Get artist information from API
     * 
     * @param int $artistId Artist ID
     * @return array|null Artist data or null on failure
     * @throws ApiException
     */
    public function getArtistInfo(int $artistId): ?array
{
    try {
        // Получаем данные от API
        $briefInfo = $this->client->artistsBriefInfo($artistId);
        
        // Логирование для отладки
        file_put_contents('api_response_debug.json', json_encode($briefInfo, JSON_PRETTY_PRINT));
        
        // Если ответ пустой или содержит ошибку, пробуем альтернативный метод
        if (!$briefInfo || isset($briefInfo['error'])) {
            return $this->getArtistInfoAlternative($artistId);
        }
        
        // Извлекаем данные, обрабатывая любую структуру
        $result = [
            'id' => $artistId,
            'name' => 'Unknown Artist',
            'subscribers_count' => 0,
            'monthly_listeners' => 0,
            'albums_count' => 0,
            'tracks_count' => 0,
            'cover_url' => null
        ];
        
        // Обрабатываем разные возможные структуры ответа
        if (is_object($briefInfo)) {
            // Пробуем извлечь из объекта
            if (isset($briefInfo->artist)) {
                $artist = $briefInfo->artist;
                $result['name'] = $artist->name ?? $result['name'];
                $result['subscribers_count'] = $artist->likesCount ?? $result['subscribers_count'];
                
                if (isset($artist->counts)) {
                    $result['albums_count'] = $artist->counts->directAlbums ?? $result['albums_count'];
                    $result['tracks_count'] = $artist->counts->tracks ?? $result['tracks_count'];
                }
                
                if (isset($briefInfo->stats)) {
                    $result['monthly_listeners'] = $briefInfo->stats->lastMonthListeners ?? $result['monthly_listeners'];
                }
                
                if (isset($artist->cover) && isset($artist->cover->uri)) {
                    $result['cover_url'] = 'https://' . $artist->cover->uri;
                }
            } else if (isset($briefInfo->result) && isset($briefInfo->result->artist)) {
                // Пробуем другую структуру
                $artist = $briefInfo->result->artist;
                $result['name'] = $artist->name ?? $result['name'];
                $result['subscribers_count'] = $artist->likesCount ?? $result['subscribers_count'];
                
                if (isset($artist->counts)) {
                    $result['albums_count'] = $artist->counts->directAlbums ?? $result['albums_count'];
                    $result['tracks_count'] = $artist->counts->tracks ?? $result['tracks_count'];
                }
                
                if (isset($briefInfo->result->stats)) {
                    $result['monthly_listeners'] = $briefInfo->result->stats->lastMonthListeners ?? $result['monthly_listeners'];
                }
                
                if (isset($artist->cover) && isset($artist->cover->uri)) {
                    $result['cover_url'] = 'https://' . $artist->cover->uri;
                }
            }
        } else if (is_array($briefInfo)) {
            // Пробуем извлечь из массива
            if (isset($briefInfo['artist'])) {
                $artist = $briefInfo['artist'];
                $result['name'] = $artist['name'] ?? $result['name'];
                $result['subscribers_count'] = $artist['likesCount'] ?? $result['subscribers_count'];
                
                if (isset($artist['counts'])) {
                    $result['albums_count'] = $artist['counts']['directAlbums'] ?? $result['albums_count'];
                    $result['tracks_count'] = $artist['counts']['tracks'] ?? $result['tracks_count'];
                }
                
                if (isset($briefInfo['stats'])) {
                    $result['monthly_listeners'] = $briefInfo['stats']['lastMonthListeners'] ?? $result['monthly_listeners'];
                }
                
                if (isset($artist['cover']) && isset($artist['cover']['uri'])) {
                    $result['cover_url'] = 'https://' . $artist['cover']['uri'];
                }
            } else if (isset($briefInfo['result']) && isset($briefInfo['result']['artist'])) {
                // Пробуем другую структуру
                $artist = $briefInfo['result']['artist'];
                $result['name'] = $artist['name'] ?? $result['name'];
                $result['subscribers_count'] = $artist['likesCount'] ?? $result['subscribers_count'];
                
                if (isset($artist['counts'])) {
                    $result['albums_count'] = $artist['counts']['directAlbums'] ?? $result['albums_count'];
                    $result['tracks_count'] = $artist['counts']['tracks'] ?? $result['tracks_count'];
                }
                
                if (isset($briefInfo['result']['stats'])) {
                    $result['monthly_listeners'] = $briefInfo['result']['stats']['lastMonthListeners'] ?? $result['monthly_listeners'];
                }
                
                if (isset($artist['cover']) && isset($artist['cover']['uri'])) {
                    $result['cover_url'] = 'https://' . $artist['cover']['uri'];
                }
            }
        }
        
        return $result;
    } catch (\Exception $e) {
        file_put_contents('error_log.txt', date('Y-m-d H:i:s') . ': ' . $e->getMessage() . "\n", FILE_APPEND);
        throw new ApiException("Failed to get artist info: " . $e->getMessage(), 0, $e);
    }
}

// Альтернативный метод получения информации об артисте
private function getArtistInfoAlternative(int $artistId): ?array
{
    try {
        // Прямой запрос к API через наш метод performCustomRequest
        $response = $this->performCustomRequest("artists/$artistId");
        $data = json_decode($response, true);
        
        // Логирование для отладки
        file_put_contents('api_response_alt_debug.json', json_encode($data, JSON_PRETTY_PRINT));
        
        // Формируем базовый результат
        $result = [
            'id' => $artistId,
            'name' => 'Unknown Artist',
            'subscribers_count' => 0,
            'monthly_listeners' => 0,
            'albums_count' => 0,
            'tracks_count' => 0,
            'cover_url' => null
        ];
        
        // Пытаемся извлечь данные из любой доступной структуры
        if (isset($data['result'])) {
            if (isset($data['result']['name'])) {
                $result['name'] = $data['result']['name'];
            }
            
            // Пытаемся найти другие поля в различных местах структуры
            // ...
        }
        
        return $result;
    } catch (\Exception $e) {
        file_put_contents('error_log.txt', date('Y-m-d H:i:s') . ': Alt method: ' . $e->getMessage() . "\n", FILE_APPEND);
        // Возвращаем базовую информацию вместо выброса исключения
        return [
            'id' => $artistId,
            'name' => "Artist #$artistId",
            'subscribers_count' => 0,
            'monthly_listeners' => 0,
            'albums_count' => 0,
            'tracks_count' => 0,
            'cover_url' => null
        ];
    }
}
    
    /**
     * Get artist tracks from API
     * 
     * @param int $artistId Artist ID
     * @return array|null Array of tracks or null on failure
     * @throws ApiException
     */
    public function getArtistTracks(int $artistId): ?array
    {
        try {
                        
            // Create custom URL for API endpoint
            $url = "artists/$artistId/tracks?page=0&page-size=200";
            
            // Perform the request using a wrapper method
            $rawResponse = $this->performCustomRequest($url);
            $response = json_decode($rawResponse, true);
            
            if (!isset($response['result']) || empty($response['result']['tracks'])) {
                return [];
            }
            
            $tracks = [];
            foreach ($response['result']['tracks'] as $trackData) {
                // Extract track data
                $track = [
                    'id' => $trackData['id'],
                    'artist_id' => $artistId,
                    'title' => $trackData['title'],
                    'duration_seconds' => $trackData['durationMs'] / 1000,
                    'album_id' => isset($trackData['albums'][0]) ? $trackData['albums'][0]['id'] : null,
                    'album_title' => isset($trackData['albums'][0]) ? $trackData['albums'][0]['title'] : null,
                    'cover_url' => null,
                    'local_path' => null
                ];
                
                // Extract cover URL if available
                if (!empty($trackData['coverUri'])) {
                    $track['cover_url'] = 'https://' . $trackData['coverUri'];
                }
                
                $tracks[] = $track;
            }
            
            return $tracks;
        } catch (\Exception $e) {
            throw new ApiException("Failed to get artist tracks: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Perform a custom request to Yandex Music API
     * 
     * @param string $endpoint API endpoint
     * @return string Raw response
     */
    private function performCustomRequest(string $endpoint): string
    {
        $startTime = microtime(true);
        
        $client = new \GuzzleHttp\Client([
            'timeout' => 30,
            'connect_timeout' => 30
        ]);
        
        $url = 'https://api.music.yandex.net/' . $endpoint;
        
        $response = $client->request('GET', $url, [
            'headers' => [
                'Authorization' => 'OAuth ' . $this->token,
                'X-Yandex-Music-Client' => 'WindowsPhone/3.17',
                'User-Agent' => 'Windows 10',
                'Connection' => 'Keep-Alive'
            ]
        ]);
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $body = $response->getBody()->getContents();
        
        return $body;
    }
    
    /**
     * Get track download information
     * 
     * @param int $trackId Track ID
     * @return array|null Download info or null on failure
     * @throws ApiException
     */
    public function getTrackDownloadInfo(int $trackId): ?array
    {
        try {
            // Use the client's tracksDownloadInfo method with getDirectLinks parameter set to true
            $downloadInfo = $this->client->tracksDownloadInfo($trackId, true);
            
            if (empty($downloadInfo)) {
                return null;
            }
            
            // Find the highest quality MP3 download option
            $bestDownload = null;
            foreach ($downloadInfo as $item) {
                if ($item['codec'] === 'mp3') {
                    if (!$bestDownload || $item['bitrateInKbps'] > $bestDownload['bitrateInKbps']) {
                        $bestDownload = $item;
                    }
                }
            }
            
            return $bestDownload;
        } catch (\Exception $e) {
            throw new ApiException("Failed to get track download info: " . $e->getMessage(), 0, $e);
        }
    }
}