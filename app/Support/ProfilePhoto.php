<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores profile photos safely:
 *  - decodes the upload with GD (rejects anything that is not a real image),
 *  - center-crops to a square and resizes to SIZE x SIZE,
 *  - re-encodes as a fresh JPEG (strips EXIF/GPS data and any embedded payload),
 *  - saves on the private "local" disk under a random name (never web-accessible directly).
 */
class ProfilePhoto
{
    public const DISK = 'local';

    public const DIRECTORY = 'app-users/photos';

    public const SIZE = 400;

    public const MAX_KB = 4096;

    /** Largest source dimension we will decode (keeps GD memory use bounded). */
    public const MAX_SOURCE_PX = 6000;

    /** File extensions this server can actually decode. */
    public static function acceptedExtensions(): array
    {
        $types = ['jpg', 'jpeg', 'png'];

        if (function_exists('imagecreatefromwebp') && (gd_info()['WebP Support'] ?? false)) {
            $types[] = 'webp';
        }

        return $types;
    }

    public static function validationRules(): array
    {
        return [
            'bail',
            'nullable',
            'file',
            'image',
            'mimes:'.implode(',', self::acceptedExtensions()),
            'max:'.self::MAX_KB,
            'dimensions:min_width=64,min_height=64,max_width='.self::MAX_SOURCE_PX.',max_height='.self::MAX_SOURCE_PX,
        ];
    }

    /** Process and store an uploaded photo; returns the stored path on the private disk. */
    public static function store(UploadedFile $file): string
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            throw ValidationException::withMessages(['photo' => 'This file could not be read as an image. Please choose a JPG or PNG photo.']);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);

        $canvas = imagecreatetruecolor(self::SIZE, self::SIZE);
        // White background so transparent PNGs do not turn black.
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled(
            $canvas, $source,
            0, 0,
            (int) (($width - $side) / 2), (int) (($height - $side) / 2),
            self::SIZE, self::SIZE,
            $side, $side,
        );

        ob_start();
        imagejpeg($canvas, null, 86);
        $jpeg = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        self::ensureWritableDirectory();

        $path = self::DIRECTORY.'/'.Str::random(40).'.jpg';

        try {
            $saved = Storage::disk(self::DISK)->put($path, $jpeg, ['visibility' => 'public']); // 0644: readable by web server and CLI
        } catch (\Throwable $e) {
            report($e);
            $saved = false;
        }

        if (! $saved) {
            throw ValidationException::withMessages([
                'photo' => 'The photo could not be saved on the server (storage folder is not writable). Please contact the administrator.',
            ]);
        }

        return $path;
    }

    /**
     * Creates the photo folder with the same open permissions as the rest of storage/.
     * Without this, Flysystem creates it as 0700 for whichever OS user ran first
     * (e.g. "php artisan" as the developer), and Apache ("daemon") can then no longer write to it.
     */
    private static function ensureWritableDirectory(): void
    {
        $dir = Storage::disk(self::DISK)->path(self::DIRECTORY);

        if (! is_dir($dir)) {
            $old = umask(0);
            @mkdir($dir, 0777, true);
            umask($old);
        }

        // Fix folders created earlier with owner-only permissions (only possible if we own them).
        foreach ([dirname($dir), $dir] as $folder) {
            if (is_dir($folder) && is_writable($folder) && (fileperms($folder) & 0777) !== 0777) {
                @chmod($folder, 0777);
            }
        }
    }

    public static function delete(?string $path): void
    {
        if ($path && str_starts_with($path, self::DIRECTORY.'/')) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
