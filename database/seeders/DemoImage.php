<?php

namespace Database\Seeders;

/**
 * Draws simple placeholder product "photos" (a flat object on a wool background
 * with a woven band) so the demo store has images before real photography exists.
 */
class DemoImage
{
    public static function draw(string $shape, int $colour, int $accent): string
    {
        $w = 1200;
        $h = 1500;
        $img = imagecreatetruecolor($w, $h);
        $bg = self::c($img, 0xEFE8DC);
        $shadow = self::c($img, 0xDDD3C3);
        $main = self::c($img, $colour);
        $acc = self::c($img, $accent);
        $ink = self::c($img, 0x22262A);
        imagefill($img, 0, 0, $bg);

        $cx = $w / 2;
        $cy = (int) ($h * 0.52);
        imagefilledellipse($img, (int) $cx, $cy + 330, 760, 90, $shadow);

        match ($shape) {
            'cushion' => self::cushion($img, $cx, $cy, $main, $acc, $ink),
            'throw' => self::throw($img, $cx, $cy, $main, $acc, $ink),
            'candle' => self::candle($img, $cx, $cy, $main, $acc),
            'dallah' => self::dallah($img, $cx, $cy, $main, $ink),
            'cups' => self::cups($img, $cx, $cy, $main, $acc),
            'platter', 'bowl' => self::platter($img, $cx, $cy, $main, $acc, $shape === 'bowl'),
            'box' => self::box($img, $cx, $cy, $main, $acc, $ink),
            default => self::mabkhara($img, $cx, $cy, $main, $ink),
        };

        $path = tempnam(sys_get_temp_dir(), 'demo').'.png';
        imagepng($img, $path);
        imagedestroy($img);

        return $path;
    }

    private static function c($img, int $hex): int
    {
        return imagecolorallocate($img, ($hex >> 16) & 255, ($hex >> 8) & 255, $hex & 255);
    }

    /** A strip of woven triangles. */
    private static function band($img, int $x1, int $y, int $x2, int $size, int $a, int $b): void
    {
        $half = intdiv($size, 2);

        for ($x = $x1; $x + $size <= $x2; $x += $size) {
            imagefilledpolygon($img, [$x, $y + $size, $x + $half, $y, $x + $size, $y + $size], $a);
            if ($x + $size + $half <= $x2) {
                imagefilledpolygon($img, [$x + $half, $y, $x + $size + $half, $y, $x + $size, $y + $size], $b);
            }
        }
    }

    private static function cushion($img, $cx, $cy, $main, $acc, $ink): void
    {
        imagefilledrectangle($img, (int) $cx - 330, $cy - 330, (int) $cx + 330, $cy + 330, $main);
        self::band($img, (int) $cx - 330, $cy - 40, (int) $cx + 330, 60, $acc, $ink);
        self::band($img, (int) $cx - 330, $cy - 200, (int) $cx + 330, 30, $acc, $main);
        self::band($img, (int) $cx - 330, $cy + 150, (int) $cx + 330, 30, $acc, $main);
    }

    private static function throw($img, $cx, $cy, $main, $acc, $ink): void
    {
        for ($i = 0; $i < 4; $i++) {
            $y = $cy - 260 + $i * 130;
            imagefilledrectangle($img, (int) $cx - 380 + $i * 10, $y, (int) $cx + 380 - $i * 10, $y + 120, $i % 2 ? $acc : $main);
        }
        for ($x = (int) $cx - 370; $x < $cx + 370; $x += 24) {
            imageline($img, $x, $cy + 270, $x, $cy + 320, $ink);
        }
    }

    private static function candle($img, $cx, $cy, $main, $acc): void
    {
        imagefilledrectangle($img, (int) $cx - 210, $cy - 200, (int) $cx + 210, $cy + 330, $main);
        imagefilledellipse($img, (int) $cx, $cy - 200, 420, 90, $acc);
        imagefilledrectangle($img, (int) $cx - 210, $cy + 20, (int) $cx + 210, $cy + 140, $acc);
        imagefilledrectangle($img, (int) $cx - 4, $cy - 280, (int) $cx + 4, $cy - 200, $main);
    }

    private static function dallah($img, $cx, $cy, $main, $ink): void
    {
        imagefilledellipse($img, (int) $cx, $cy + 180, 460, 320, $main);
        imagefilledpolygon($img, [(int) $cx - 110, $cy + 60, (int) $cx + 110, $cy + 60, (int) $cx + 70, $cy - 220, (int) $cx - 70, $cy - 220], $main);
        imagefilledellipse($img, (int) $cx, $cy - 240, 190, 90, $main);
        imagefilledpolygon($img, [(int) $cx + 90, $cy - 120, (int) $cx + 340, $cy - 300, (int) $cx + 360, $cy - 270, (int) $cx + 150, $cy - 40], $main);
        imagesetthickness($img, 18);
        imagearc($img, (int) $cx - 230, $cy + 20, 200, 320, 90, 270, $ink);
    }

    private static function cups($img, $cx, $cy, $main, $acc): void
    {
        foreach ([[-300, 60], [0, -40], [300, 60]] as [$dx, $dy]) {
            $x = (int) $cx + $dx;
            imagefilledpolygon($img, [$x - 120, $cy + $dy, $x + 120, $cy + $dy, $x + 80, $cy + $dy + 220, $x - 80, $cy + $dy + 220], $main);
            imagefilledrectangle($img, $x - 120, $cy + $dy, $x + 120, $cy + $dy + 20, $acc);
        }
    }

    private static function platter($img, $cx, $cy, $main, $acc, bool $bowl): void
    {
        imagefilledellipse($img, (int) $cx, $cy + 120, 860, $bowl ? 520 : 360, $main);
        imagefilledellipse($img, (int) $cx, $cy + 100, 700, $bowl ? 380 : 260, $acc);
        if (! $bowl) {
            for ($i = 0; $i < 9; $i++) {
                imagefilledellipse($img, (int) $cx - 240 + $i * 60, $cy + 100 + ($i % 2) * 30, 70, 110, $main);
            }
        }
    }

    private static function box($img, $cx, $cy, $main, $acc, $ink): void
    {
        imagefilledrectangle($img, (int) $cx - 360, $cy - 200, (int) $cx + 360, $cy + 330, $main);
        imagefilledrectangle($img, (int) $cx - 380, $cy - 260, (int) $cx + 380, $cy - 180, $acc);
        self::band($img, (int) $cx - 360, $cy + 40, (int) $cx + 360, 50, $acc, $ink);
    }

    private static function mabkhara($img, $cx, $cy, $main, $ink): void
    {
        imagefilledpolygon($img, [(int) $cx - 220, $cy + 330, (int) $cx + 220, $cy + 330, (int) $cx + 120, $cy + 120, (int) $cx - 120, $cy + 120], $main);
        imagefilledrectangle($img, (int) $cx - 170, $cy - 120, (int) $cx + 170, $cy + 120, $main);
        imagefilledellipse($img, (int) $cx, $cy - 120, 340, 90, $ink);
        for ($i = 0; $i < 3; $i++) {
            imagesetthickness($img, 10);
            imagearc($img, (int) $cx - 40 + $i * 40, $cy - 260, 60, 160, 200, 340, $ink);
        }
    }
}
