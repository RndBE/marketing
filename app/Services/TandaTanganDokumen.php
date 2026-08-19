<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Penempatan gambar tanda tangan pada dokumen PDF.
 *
 * Dipakai bersama oleh dokumen Penawaran Harga dan Purchase Order supaya coretan
 * tanda tangan tampil dengan ukuran dan posisi yang sama di semua dokumen.
 */
class TandaTanganDokumen
{
    /** Kotak tanda tangan pada PDF: 220x100 px. */
    private const CONTAINER_WIDTH = 220.0;

    private const CONTAINER_HEIGHT = 100.0;

    private const INK_TARGET_WIDTH = 100.0;

    private const INK_TARGET_HEIGHT = 100.0;

    /**
     * Mengubah path simpanan (mis. "signatures/ttd.png") menjadi path berkas yang bisa
     * dibaca DomPDF. Mengembalikan null bila berkasnya tidak ada.
     */
    public function resolvePublicImagePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $normalizedPath = preg_replace('#^(public|storage)/#', '', ltrim($path, '/\\'));

        foreach ([
            Storage::disk('public')->path($normalizedPath),
            public_path('storage/'.$normalizedPath),
            public_path($normalizedPath),
        ] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Posisi dan ukuran gambar tanda tangan di dalam kotak 220x100.
     *
     * @return array{left: float, top: float, width: float, height: float}|null
     */
    public function placement(?string $imagePath): ?array
    {
        if (! $imagePath || ! is_file($imagePath)) {
            return null;
        }

        $imageInfo = @getimagesize($imagePath);
        $sourceWidth = (int) ($imageInfo[0] ?? 0);
        $sourceHeight = (int) ($imageInfo[1] ?? 0);
        if ($sourceWidth < 1 || $sourceHeight < 1) {
            return null;
        }

        // Sasarannya coretan yang terlihat, bukan kanvasnya: gambar TTD hasil potong
        // biasanya punya ruang kosong di sekeliling coretan, dan porsinya berbeda-beda
        // tiap file. Menskala dari ukuran kanvas membuat coretan tampil besar-kecil
        // tak menentu.
        $ink = $this->inkBounds($imagePath, $sourceWidth, $sourceHeight);

        if ($ink === null) {
            // Gambar tanpa transparansi (mis. hasil pindai berlatar putih) atau tanpa
            // coretan yang terdeteksi: seluruh kanvas adalah isinya, jadi kanvas itu
            // sendiri yang diskala.
            $scale = min(self::INK_TARGET_WIDTH / $sourceWidth, self::CONTAINER_HEIGHT / $sourceHeight);
            $displayWidth = $sourceWidth * $scale;
            $displayHeight = $sourceHeight * $scale;

            return [
                'left' => round(max(0, (self::CONTAINER_WIDTH - $displayWidth) / 2), 2),
                'top' => round(max(0, self::CONTAINER_HEIGHT - $displayHeight), 2),
                'width' => round($displayWidth, 2),
                'height' => round($displayHeight, 2),
            ];
        }

        $scale = min(self::INK_TARGET_WIDTH / $ink['width'], self::INK_TARGET_HEIGHT / $ink['height']);
        $displayWidth = $sourceWidth * $scale;
        $displayHeight = $sourceHeight * $scale;

        // Coretan diletakkan di tengah kotak secara horizontal dan rapat ke dasarnya.
        // Ruang kosong kanvas boleh menjulur keluar kotak -- bagian itu transparan.
        $left = (self::CONTAINER_WIDTH / 2) - ($ink['centerX'] * $scale);
        $top = self::CONTAINER_HEIGHT - ($ink['bottom'] * $scale);

        return [
            'left' => round($left, 2),
            'top' => round($top, 2),
            'width' => round($displayWidth, 2),
            'height' => round($displayHeight, 2),
        ];
    }

    /**
     * Batas coretan tinta pada gambar tanda tangan.
     *
     * @return array{width: float, height: float, centerX: float, bottom: float}|null
     */
    private function inkBounds(string $imagePath, int $sourceWidth, int $sourceHeight): ?array
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $contents = @file_get_contents($imagePath);
        $image = $contents !== false ? @imagecreatefromstring($contents) : false;
        if ($image === false) {
            return null;
        }

        $stepX = max(1, (int) ceil($sourceWidth / 360));
        $stepY = max(1, (int) ceil($sourceHeight / 360));
        $hasTransparency = false;
        $weightTotal = 0.0;
        $weightedX = 0.0;
        $minX = $maxX = $minY = $maxY = null;

        for ($y = 0; $y < $sourceHeight; $y += $stepY) {
            for ($x = 0; $x < $sourceWidth; $x += $stepX) {
                $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                $alpha = (int) ($color['alpha'] ?? 0);
                if ($alpha > 8) {
                    $hasTransparency = true;
                }

                $opacity = (127 - $alpha) / 127;
                $luminance = (
                    (0.2126 * (int) $color['red'])
                    + (0.7152 * (int) $color['green'])
                    + (0.0722 * (int) $color['blue'])
                ) / 255;
                $inkWeight = $opacity * max(0, (1 - $luminance) - 0.04);

                if ($inkWeight <= 0) {
                    continue;
                }

                $weightTotal += $inkWeight;
                $weightedX += $x * $inkWeight;
                $minX = $minX === null ? $x : min($minX, $x);
                $maxX = $maxX === null ? $x : max($maxX, $x);
                $minY = $minY === null ? $y : min($minY, $y);
                $maxY = $maxY === null ? $y : max($maxY, $y);
            }
        }

        imagedestroy($image);

        if (! $hasTransparency || $minX === null || $weightTotal <= 0) {
            return null;
        }

        // Pemindaian melompat beberapa piksel, jadi tepi coretan bisa terlewat sebanyak
        // satu langkah; dilebarkan agar coretannya tidak terpotong.
        $minX = max(0, $minX - $stepX);
        $maxX = min($sourceWidth - 1, $maxX + $stepX);
        $minY = max(0, $minY - $stepY);
        $maxY = min($sourceHeight - 1, $maxY + $stepY);

        return [
            'width' => (float) max(1, $maxX - $minX + 1),
            'height' => (float) max(1, $maxY - $minY + 1),
            'centerX' => $weightedX / $weightTotal,
            'bottom' => (float) ($maxY + 1),
        ];
    }
}
