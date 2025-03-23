<?php

declare(strict_types=1);

namespace App\Entities;

class Artist
{
    private ?int $id = null;
    private string $name = '';
    private string $yandexId = '';
    private int $followers = 0;
    private int $mounthlyListeners = 0;
    private int $albumsCount = 0;
    //Id 
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Artist
    {
        $this->id = $id;
        return $this;
    }
    //Name
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Artist
    {
        $this->name = $name;
        return $this;
    }   
    //YandexId
    public function getYandexId(): string
    {
        return $this->yandexId;
    }

    public function setYandexId(string $yandexId): Artist
    {
        $this->yandexId = $yandexId;
        return $this;
    }   
    //Followers
    public function getFollowers(): int
    {
        return $this->followers;
    }

    public function setFollowers(int $followers): Artist
    {
        $this->followers = $followers;
        return $this;
    }
    //MounthlyListeners
    public function getMounthlyListeners(): int
    {
        return $this->mounthlyListeners;
    }

    public function setMounthlyListeners(int $mounthlyListeners): Artist
    {
        $this->mounthlyListeners = $mounthlyListeners;
        return $this;
    }
    //AlbumsCount
    public function getAlbumsCount(): int
    {
        return $this->albumsCount;
    }

    public function setAlbumsCount(int $albumsCount): Artist
    {
        $this->albumsCount = $albumsCount;
        return $this;
    }
}

class Track
{
    private ?int $id = null;
    private string $yandexId = '';
    private string $title = '';
    private int $artistId = 0;
    private int $duration = 0;

    //Id
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Track
    {
        $this->id = $id;
        return $this;
    }

    //YandexId
    public function getYandexId(): string
    {
        return $this->yandexId;
    }

    public function setYandexId(string $yandexId): Track
    {
        $this->yandexId = $yandexId;
        return $this;
    }

    //Title

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Track
    {
        $this->title = $title;
        return $this;
    }

    //ArtistId

    public function getArtistId(): int
    {
        return $this->artistId;
    }

    public function setArtistId(int $artistId): Track
    {
        $this->artistId = $artistId;
        return $this;
    }

    //Duration

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): Track
    {
        $this->duration = $duration;
        return $this;
    }
}