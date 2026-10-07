<?php

namespace App\Services\Resumes;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class ProfileImageProcessor
{
    public const SIZE = 160;

    public static function directory(): string
    {
        return public_path('assets/images');
    }

    public static function filenameFor(int $userId): string
    {
        return "profile-{$userId}.png";
    }

    /**
     * Center-crop to a square and resize to SIZE×SIZE, saved as PNG.
     */
    public function store(UploadedFile $file, int $userId): string
    {
        $source = match ($file->getMimeType()) {
            'image/jpeg' => imagecreatefromjpeg($file->getRealPath()),
            'image/png' => imagecreatefrompng($file->getRealPath()),
            default => false,
        };

        if ($source === false) {
            throw new RuntimeException('Unreadable image.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);

        $target = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));

        imagecopyresampled(
            $target, $source,
            0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2),
            self::SIZE, self::SIZE, $side, $side,
        );

        if (! is_dir(self::directory())) {
            mkdir(self::directory(), 0755, true);
        }

        $filename = self::filenameFor($userId);
        imagepng($target, self::directory().'/'.$filename);

        return $filename;
    }
}
