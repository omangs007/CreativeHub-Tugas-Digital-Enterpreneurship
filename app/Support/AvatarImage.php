<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Mengecilkan foto profil menjadi persegi (crop tengah) dan menyimpannya sebagai WebP.
 * Foto besar dari kamera HP (beberapa MB) menjadi beberapa puluh KB tanpa kehilangan
 * kualitas yang terlihat pada ukuran avatar.
 */
final class AvatarImage
{
    public const SIZE = 512;

    public const DISK = 'public';

    /**
     * Ubah file pada disk publik (path relatif) menjadi avatar WebP 512×512.
     * File asli dihapus; mengembalikan path file baru.
     */
    public static function normalize(string $path, int $userId): string
    {
        $disk = Storage::disk(self::DISK);
        $source = @imagecreatefromstring((string) $disk->get($path));

        if ($source === false) {
            throw new RuntimeException('Berkas foto tidak dapat dibaca sebagai gambar.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);
        $target = min(self::SIZE, $side);

        $canvas = imagecreatetruecolor($target, $target);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagecopyresampled(
            $canvas, $source,
            0, 0,
            intdiv($width - $side, 2), intdiv($height - $side, 2),
            $target, $target,
            $side, $side,
        );

        ob_start();
        imagewebp($canvas, null, 85);
        $binary = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        $newPath = "avatars/{$userId}/".Str::random(40).'.webp';
        $disk->put($newPath, $binary);

        if ($newPath !== $path) {
            $disk->delete($path);
        }

        return $newPath;
    }
}
