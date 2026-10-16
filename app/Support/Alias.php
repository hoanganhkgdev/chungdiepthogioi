<?php

namespace App\Support;

class Alias
{
    /**
     * Tương đương change_alias() của NukeViet: giữ nguyên thuật toán để URL/QR cũ khớp.
     */
    public static function make(string $text): string
    {
        $text = preg_replace('/[\x{0300}\x{0301}\x{0303}\x{0309}\x{0323}]/u', '', $text);
        $entities = ['&amp;', '&#039;', '&quot;', '&lt;', '&gt;', '&#x005C;', '&#x002F;', '&#40;', '&#41;', '&#42;', '&#91;', '&#93;', '&#33;', '&#x3D;', '&#x23;', '&#x25;', '&#x5E;', '&#x3A;', '&#x7B;', '&#x7D;', '&#x60;', '&#x7E;'];
        $text = strtr(str_replace($entities, ' ', $text), config('romanize'));

        return preg_replace(
            ['/[^a-zA-Z0-9]/', '/[ ]+/', '/^[\-]+/', '/[\-]+$/'],
            [' ', '-', '', ''],
            $text
        );
    }
}
