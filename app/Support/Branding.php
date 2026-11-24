<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Logo + favicon handling. Uploads are re-encoded with GD (strips anything hidden in the file),
 * stored on the private disk with random names and served by BrandingController.
 * Without uploads, the built-in icon set in resources/brand is used.
 */
class Branding
{
    public const DISK = 'local';

    public const DIR = 'branding';

    public const FILE_PATTERN = '[A-Za-z0-9-]{1,80}\.(png|ico|svg)';

    public const ICON_SIZES = [32, 180, 192, 512];

    public const MAX_KB = 4096;

    public static function uploadRules(): array
    {
        return ['bail', 'nullable', 'file', 'max:'.self::MAX_KB, 'mimes:png,jpg,jpeg', 'mimetypes:image/png,image/jpeg', 'dimensions:min_width=32,min_height=32,max_width=6000,max_height=6000'];
    }

    /** Store a logo (max 600x200, keeps transparency). Returns the stored file name. */
    public static function storeLogo(UploadedFile $file): string
    {
        $src = self::load($file, 'logo');
        [$w, $h] = [imagesx($src), imagesy($src)];
        $scale = min(1, 600 / $w, 200 / $h);
        $dst = self::canvas(max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale)));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, imagesx($dst), imagesy($dst), $w, $h);
        $name = 'logo-'.Str::lower(Str::random(24)).'.png';
        self::put($name, self::png($dst));

        return $name;
    }

    /** Store a favicon set from one square-ish image. Returns the name prefix (e.g. "fav-abc123"). */
    public static function storeFavicon(UploadedFile $file): string
    {
        $src = self::load($file, 'favicon');
        [$w, $h] = [imagesx($src), imagesy($src)];
        $side = min($w, $h);
        $prefix = 'fav-'.Str::lower(Str::random(24));
        foreach (self::buildSet($src, (int) (($w - $side) / 2), (int) (($h - $side) / 2), $side) as $name => $bytes) {
            self::put($prefix.'-'.$name, $bytes);
        }

        return $prefix;
    }

    /** Build the PNG sizes and a multi-size .ico from a square region of $src. */
    public static function buildSet(\GdImage $src, int $x, int $y, int $side): array
    {
        $files = [];
        $pngs = [];
        foreach (array_unique([...self::ICON_SIZES, 16, 48]) as $size) {
            $dst = self::canvas($size, $size);
            imagecopyresampled($dst, $src, 0, 0, $x, $y, $size, $size, $side, $side);
            $pngs[$size] = self::png($dst);
            if (in_array($size, self::ICON_SIZES, true)) {
                $files[$size.'.png'] = $pngs[$size];
            }
        }
        $files['favicon.ico'] = self::ico([16 => $pngs[16], 32 => $pngs[32], 48 => $pngs[48]]);

        return $files;
    }

    public static function delete(?string $name): void
    {
        if (! $name) {
            return;
        }
        $disk = Storage::disk(self::DISK);
        if (str_starts_with($name, 'fav-')) {
            foreach ([...array_map(fn ($s) => $s.'.png', self::ICON_SIZES), 'favicon.ico'] as $suffix) {
                $disk->delete(self::DIR.'/'.$name.'-'.$suffix);
            }
        } else {
            $disk->delete(self::DIR.'/'.basename($name));
        }
    }

    /** ICO container with embedded PNG images (supported by every current browser). */
    private static function ico(array $pngs): string
    {
        $count = count($pngs);
        $header = pack('vvv', 0, 1, $count);
        $dir = '';
        $data = '';
        $offset = 6 + 16 * $count;
        foreach ($pngs as $size => $bytes) {
            $dir .= pack('CCCCvvVV', $size >= 256 ? 0 : $size, $size >= 256 ? 0 : $size, 0, 0, 1, 32, strlen($bytes), $offset);
            $data .= $bytes;
            $offset += strlen($bytes);
        }

        return $header.$dir.$data;
    }

    private static function load(UploadedFile $file, string $field): \GdImage
    {
        $path = $file->getRealPath();
        $info = $path ? @getimagesize($path) : false;
        $img = match ($info[2] ?? null) {
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            default => false,
        };
        if (! $img) {
            throw ValidationException::withMessages([$field => 'This image could not be read. Please upload a PNG or JPG file.']);
        }
        if (! imageistruecolor($img)) {
            imagepalettetotruecolor($img);
        }
        imagealphablending($img, true);
        imagesavealpha($img, true);

        return $img;
    }

    private static function canvas(int $w, int $h): \GdImage
    {
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));

        return $img;
    }

    private static function png(\GdImage $img): string
    {
        ob_start();
        imagepng($img, null, 9);

        return (string) ob_get_clean();
    }

    private static function put(string $name, string $bytes): void
    {
        $disk = Storage::disk(self::DISK);
        $dir = $disk->path(self::DIR);
        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
            @chmod($dir, 0777);
        }
        if (! $disk->put(self::DIR.'/'.$name, $bytes, ['visibility' => 'public'])) {
            throw ValidationException::withMessages(['logo' => 'Could not save the image. Please check storage folder permissions.']);
        }
    }
}
