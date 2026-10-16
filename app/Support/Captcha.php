<?php

namespace App\Support;

/** Captcha ảnh tự sinh bằng GD (không cần khóa/dịch vụ ngoài). Đáp án giữ trong session, dùng một lần. */
class Captcha
{
    private const SESSION_KEY = 'login_captcha';

    private const CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /** @return string data URI của ảnh PNG */
    public static function make(int $length = 5): string
    {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= self::CHARS[random_int(0, strlen(self::CHARS) - 1)];
        }
        session()->put(self::SESSION_KEY, self::digest($code));

        return self::render($code);
    }

    /** So khớp (không phân biệt hoa/thường) và xóa đáp án để không dùng lại được. */
    public static function check(?string $input): bool
    {
        $expected = session()->pull(self::SESSION_KEY);

        return is_string($expected) && $input !== null && hash_equals($expected, self::digest($input));
    }

    private static function digest(string $code): string
    {
        return hash_hmac('sha256', strtoupper(trim($code)), (string) config('app.key'));
    }

    private static function render(string $code): string
    {
        [$w, $h] = [180, 56];
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, 241, 248, 243));

        $rand = fn (int $a, int $b) => random_int($a, $b);
        for ($i = 0; $i < 7; $i++) {
            $c = imagecolorallocate($img, $rand(150, 210), $rand(170, 220), $rand(150, 210));
            imageline($img, $rand(0, $w), $rand(0, $h), $rand(0, $w), $rand(0, $h), $c);
        }

        // Mỗi ký tự vẽ bằng font GD có sẵn rồi phóng to, xoay nhẹ và lệch vị trí.
        $step = intdiv($w - 20, strlen($code));
        foreach (str_split($code) as $i => $ch) {
            $tmp = imagecreatetruecolor(14, 20);
            $bg = imagecolorallocate($tmp, 241, 248, 243);
            imagefill($tmp, 0, 0, $bg);
            imagestring($tmp, 5, 2, 2, $ch, imagecolorallocate($tmp, $rand(0, 40), $rand(70, 120), $rand(30, 80)));
            $tmp = imagerotate($tmp, $rand(-18, 18), $bg);
            $tw = imagesx($tmp);
            $th = imagesy($tmp);
            $size = $rand(34, 42);
            imagecopyresampled($img, $tmp, 10 + $i * $step + $rand(-3, 3), $rand(2, 12), 0, 0, $size, $size + 6, $tw, $th);
        }

        for ($i = 0; $i < 220; $i++) {
            imagesetpixel($img, $rand(0, $w - 1), $rand(0, $h - 1), imagecolorallocate($img, $rand(80, 200), $rand(120, 200), $rand(80, 200)));
        }
        for ($i = 0; $i < 3; $i++) {
            imagearc($img, $rand(0, $w), $rand(0, $h), $rand(60, 160), $rand(20, 60), 0, 360, imagecolorallocate($img, $rand(60, 140), $rand(120, 180), $rand(70, 140)));
        }

        ob_start();
        imagepng($img);
        $png = ob_get_clean();

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
