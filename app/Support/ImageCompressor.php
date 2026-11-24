<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Accepts large uploads (up to MAX_UPLOAD_KB, 100 MB) and stores a visually
 * near-identical JPEG of at most TARGET_BYTES (500 KB).
 *
 * Quality strategy: keep the pixels first, give up detail last.
 *  1. Never touch images already <= target (no re-encode, no loss).
 *  2. At the current resolution, binary-search the highest JPEG quality
 *     between QUALITY_MAX and QUALITY_MIN that fits the target.
 *  3. Only if even QUALITY_MIN is too big, shrink the resolution by 15% and retry.
 * Result is a fresh progressive JPEG (EXIF/GPS stripped, orientation applied).
 */
class ImageCompressor
{
    public const MAX_UPLOAD_KB = 102400;      // 100 MB

    public const TARGET_BYTES = 512000;       // 500 KB

    public const MAX_SIDE = 3000;             // first-pass longest side

    public const MIN_SIDE = 700;

    public const QUALITY_MAX = 92;

    public const QUALITY_MIN = 74;

    private const MAX_SOURCE_PIXELS = 200_000_000;

    public static function extensions(): string
    {
        return 'jpg,jpeg,png,webp';
    }

    /** Validation rules for an image field that may be up to 100 MB. */
    public static function rules(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'file', 'image', 'mimes:'.self::extensions(), 'max:'.self::MAX_UPLOAD_KB];
    }

    /** Compress and store on $disk/$directory; returns the stored relative path. */
    public static function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $path = $file->getRealPath();
        $info = @getimagesize($path);
        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw ValidationException::withMessages(['image' => 'The file is not a valid JPG, PNG or WEBP image.']);
        }
        if ($info[0] * $info[1] > self::MAX_SOURCE_PIXELS) {
            throw ValidationException::withMessages(['image' => 'The image dimensions are too large to process.']);
        }

        // Small enough already: store untouched (zero quality loss), but only the real bytes of a verified image.
        if (filesize($path) <= self::TARGET_BYTES && $info[2] === IMAGETYPE_JPEG) {
            $name = trim($directory, '/').'/'.Str::random(40).'.jpg';
            Storage::disk($disk)->put($name, file_get_contents($path));

            return $name;
        }

        $jpeg = self::compress($path);
        $name = trim($directory, '/').'/'.Str::random(40).'.jpg';
        Storage::disk($disk)->put($name, $jpeg);

        return $name;
    }

    /** Returns JPEG bytes <= TARGET_BYTES (best effort for extreme images). */
    public static function compress(string $path): string
    {
        @ini_set('memory_limit', '1536M');
        @set_time_limit(120);

        $source = @imagecreatefromstring((string) file_get_contents($path));
        if ($source === false) {
            throw ValidationException::withMessages(['image' => 'The image could not be read. Try a different file.']);
        }
        $source = self::orient($source, $path);
        $width = imagesx($source);
        $height = imagesy($source);

        // Start at MAX_SIDE (never upscale), flattening transparency onto white.
        $scale = min(1, self::MAX_SIDE / max($width, $height));
        $current = self::resample($source, (int) round($width * $scale), (int) round($height * $scale));
        imagedestroy($source);

        $best = null;
        while (true) {
            $best = self::bestQuality($current);
            if (strlen($best) <= self::TARGET_BYTES) {
                break;
            }
            $w = imagesx($current);
            $h = imagesy($current);
            if (max($w, $h) <= self::MIN_SIDE) {
                break; // give up on size rather than destroy the image
            }
            $next = self::resample($current, (int) round($w * 0.85), (int) round($h * 0.85));
            imagedestroy($current);
            $current = $next;
        }
        imagedestroy($current);

        return $best;
    }

    /** Highest quality in [MIN, MAX] that fits; falls back to MIN-quality bytes if none fit. */
    private static function bestQuality(\GdImage $image): string
    {
        $lo = self::QUALITY_MIN;
        $hi = self::QUALITY_MAX;
        $fit = null;
        $atMin = null;

        while ($lo <= $hi) {
            $mid = intdiv($lo + $hi, 2);
            $bytes = self::encode($image, $mid);
            if ($mid === self::QUALITY_MIN) {
                $atMin = $bytes;
            }
            if (strlen($bytes) <= self::TARGET_BYTES) {
                $fit = $bytes;
                $lo = $mid + 1;
            } else {
                $hi = $mid - 1;
            }
        }

        return $fit ?? $atMin ?? self::encode($image, self::QUALITY_MIN);
    }

    private static function encode(\GdImage $image, int $quality): string
    {
        imageinterlace($image, true); // progressive: usually a bit smaller
        ob_start();
        imagejpeg($image, null, $quality);

        return (string) ob_get_clean();
    }

    private static function resample(\GdImage $src, int $w, int $h): \GdImage
    {
        $w = max(1, $w);
        $h = max(1, $h);
        $dst = imagecreatetruecolor($w, $h);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));

        return $dst;
    }

    private static function orient(\GdImage $image, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }
        $exif = @exif_read_data($path);
        $rotated = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated ?: $image;
    }
}
