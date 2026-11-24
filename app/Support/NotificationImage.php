<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Images attached to push notifications.
 *
 * Phones download the image themselves, so it must be reachable without login. To keep that safe:
 *  - every upload is decoded and re-encoded as a fresh JPEG (no EXIF/GPS, no embedded payloads),
 *  - it is resized to at most MAX_WIDTH px wide (FCM recommends small images, ~2:1 ratio),
 *  - it gets an unguessable 40-character random name and is served only through a strict route.
 */
class NotificationImage
{
    public const DISK = 'local';

    public const DIRECTORY = 'notification-images';

    public const MAX_WIDTH = 1200;

    public const MAX_KB = 5120;

    public const FILENAME_PATTERN = '[A-Za-z0-9]{40}\.jpg';

    public static function validationRules(): array
    {
        return [
            'bail',
            'nullable',
            'file',
            'image',
            'mimes:'.implode(',', ProfilePhoto::acceptedExtensions()),
            'max:'.self::MAX_KB,
            'dimensions:min_width=100,min_height=50,max_width=8000,max_height=8000',
        ];
    }

    /** Returns the stored file name (not a path). */
    public static function store(UploadedFile $file): string
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if ($source === false) {
            throw ValidationException::withMessages(['image' => 'This file could not be read as an image. Please choose a JPG or PNG.']);
        }

        $w = imagesx($source);
        $h = imagesy($source);
        $scale = min(1, self::MAX_WIDTH / $w);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $canvas = imagecreatetruecolor($nw, $nh);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagejpeg($canvas, null, 82);
        $jpeg = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($canvas);

        self::ensureWritableDirectory();
        $name = Str::random(40).'.jpg';

        try {
            $saved = Storage::disk(self::DISK)->put(self::DIRECTORY.'/'.$name, $jpeg, ['visibility' => 'public']);
        } catch (\Throwable $e) {
            report($e);
            $saved = false;
        }

        if (! $saved) {
            throw ValidationException::withMessages(['image' => 'The image could not be saved on the server (storage folder is not writable).']);
        }

        return $name;
    }

    public static function url(string $name): string
    {
        return route('notification-images.show', ['file' => $name]);
    }

    /** True when phones on the internet can download images from this server (https + not localhost). */
    public static function publiclyReachable(): bool
    {
        $url = route('notification-images.show', ['file' => str_repeat('a', 40).'.jpg']);
        $host = (string) parse_url($url, PHP_URL_HOST);

        if (! str_starts_with($url, 'https://') || $host === '' || $host === 'localhost') {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            // A raw IP must be a public one (not 127.x, 192.168.x, 10.x …).
            return (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return ! preg_match('/\.(local|localhost|test|internal|lan)$/i', $host);
    }

    public static function delete(?string $name): void
    {
        if ($name && preg_match('/^'.self::FILENAME_PATTERN.'$/', $name)) {
            Storage::disk(self::DISK)->delete(self::DIRECTORY.'/'.$name);
        }
    }

    private static function ensureWritableDirectory(): void
    {
        $dir = Storage::disk(self::DISK)->path(self::DIRECTORY);
        if (! is_dir($dir)) {
            $old = umask(0);
            @mkdir($dir, 0777, true);
            umask($old);
        }
    }
}
