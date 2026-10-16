<?php

namespace App\Support;

class Avatar
{
    /** Ảnh tròn chữ cái đầu khi chưa có ảnh (SVG nhúng, không gọi dịch vụ ngoài). */
    public static function initials(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initial = mb_strtoupper(mb_substr(end($words) ?: '?', 0, 1));
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="88" height="88" viewBox="0 0 88 88">'
            .'<rect width="88" height="88" fill="#d8f3e1"/>'
            .'<text x="44" y="58" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-size="40" font-weight="600" fill="#006b32">'
            .htmlspecialchars($initial, ENT_XML1).'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
