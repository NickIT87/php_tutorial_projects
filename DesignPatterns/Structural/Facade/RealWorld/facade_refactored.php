<?php
declare(strict_types=1);

namespace DesignPatterns\Structural\Facade\RealWorld;

/**
 * The Facade provides a single method for downloading videos from YouTube.
 * It hides all complexity of the YouTube API and FFmpeg library.
 */
class YouTubeDownloader
{
    private YouTube $youtube;
    private FFMpeg $ffmpeg;

    /**
     * Facade manages the lifecycle of its subsystems.
     */
    public function __construct(string $youtubeApiKey)
    {
        $this->youtube = new YouTube($youtubeApiKey);
        $this->ffmpeg  = FFMpeg::create();
    }

    /**
     * Downloads and converts a YouTube video.
     */
    public function downloadVideo(string $url): void
    {
        echo "Fetching video metadata from YouTube...\n";
        $video = $this->youtube->fetchVideo($url);
        $title = $video->getTitle();

        echo "Saving video file to a temporary file...\n";
        $this->youtube->saveAs($url, 'video.mpg');

        echo "Processing source video...\n";
        $ffmpegVideo = $this->ffmpeg->open('video.mpg');

        echo "Normalizing and resizing the video...\n";
        $ffmpegVideo
            ->filters()
            ->resize(320, 240)
            ->synchronize();

        echo "Capturing preview image...\n";
        $ffmpegVideo
            ->frame(10)
            ->save($title . '_frame.jpg');

        echo "Saving video in target formats...\n";
        $ffmpegVideo
            ->save($title . '.mp4')
            ->save($title . '.wmv')
            ->save($title . '.webm');

        echo "Done!\n";
    }
}

/**
 * =========================
 * Subsystem: YouTube API
 * =========================
 */
class YouTube
{
    public function __construct(
        private string $apiKey
    ) {}

    public function fetchVideo(string $url): YouTubeVideo
    {
        return new YouTubeVideo('example-video');
    }

    public function saveAs(string $url, string $path): void
    {
        // download video
    }
}

class YouTubeVideo
{
    public function __construct(
        private string $title
    ) {}

    public function getTitle(): string
    {
        return $this->title;
    }
}

/**
 * =========================
 * Subsystem: FFmpeg
 * =========================
 */
class FFMpeg
{
    public static function create(): self
    {
        return new self();
    }

    public function open(string $video): FFMpegVideo
    {
        return new FFMpegVideo();
    }
}

class FFMpegVideo
{
    public function filters(): self
    {
        return $this;
    }

    public function resize(int $width, int $height): self
    {
        return $this;
    }

    public function synchronize(): self
    {
        return $this;
    }

    public function frame(int $seconds): self
    {
        return $this;
    }

    public function save(string $path): self
    {
        return $this;
    }
}

/**
 * =========================
 * Client code
 * =========================
 */
function clientCode(YouTubeDownloader $facade): void
{
    $facade->downloadVideo(
        'https://www.youtube.com/watch?v=QH2-TGUlwu4'
    );
}

$facade = new YouTubeDownloader('APIKEY-XXXXXXXXX');
clientCode($facade);
