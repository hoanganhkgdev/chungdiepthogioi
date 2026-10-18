<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Mã tỉnh/thành 3 chữ số dùng làm 3 số đầu của Mã số giới tử (giống mã tỉnh trên số CCCD).
 * Cần thêm hoặc sửa mã thì chỉ sửa danh sách này.
 */
class ProvinceCodes
{
    public const LIST = [
        '001' => 'Hà Nội', '002' => 'Hà Giang', '004' => 'Cao Bằng', '006' => 'Bắc Kạn', '008' => 'Tuyên Quang',
        '010' => 'Lào Cai', '011' => 'Điện Biên', '012' => 'Lai Châu', '014' => 'Sơn La', '015' => 'Yên Bái',
        '017' => 'Hòa Bình', '019' => 'Thái Nguyên', '020' => 'Lạng Sơn', '022' => 'Quảng Ninh', '024' => 'Bắc Giang',
        '025' => 'Phú Thọ', '026' => 'Vĩnh Phúc', '027' => 'Bắc Ninh', '030' => 'Hải Dương', '031' => 'Hải Phòng',
        '033' => 'Hưng Yên', '034' => 'Thái Bình', '035' => 'Hà Nam', '036' => 'Nam Định', '037' => 'Ninh Bình',
        '038' => 'Thanh Hóa', '040' => 'Nghệ An', '042' => 'Hà Tĩnh', '044' => 'Quảng Bình', '045' => 'Quảng Trị',
        '046' => 'Thừa Thiên Huế', '048' => 'Đà Nẵng', '049' => 'Quảng Nam', '051' => 'Quảng Ngãi', '052' => 'Bình Định',
        '054' => 'Phú Yên', '056' => 'Khánh Hòa', '058' => 'Ninh Thuận', '060' => 'Bình Thuận', '062' => 'Kon Tum',
        '064' => 'Gia Lai', '066' => 'Đắk Lắk', '067' => 'Đắk Nông', '068' => 'Lâm Đồng', '070' => 'Bình Phước',
        '072' => 'Tây Ninh', '074' => 'Bình Dương', '075' => 'Đồng Nai', '077' => 'Bà Rịa - Vũng Tàu',
        '079' => 'TP. Hồ Chí Minh', '080' => 'Long An', '082' => 'Tiền Giang', '083' => 'Bến Tre', '084' => 'Trà Vinh',
        '086' => 'Vĩnh Long', '087' => 'Đồng Tháp', '089' => 'An Giang', '091' => 'Kiên Giang', '092' => 'Cần Thơ',
        '093' => 'Hậu Giang', '094' => 'Sóc Trăng', '095' => 'Bạc Liêu', '096' => 'Cà Mau',
    ];

    /** Tên gọi khác thường gặp -> mã. */
    private const ALIASES = [
        'saigon' => '079', 'hcm' => '079', 'tphcm' => '079', 'hochiminh' => '079', 'hue' => '046',
        'baria' => '077', 'vungtau' => '077', 'bariavungtau' => '077',
    ];

    /** Danh sách cho ô chọn: '079' => '079 – TP. Hồ Chí Minh'. */
    public static function options(): array
    {
        return collect(self::LIST)->mapWithKeys(fn ($name, $code) => [$code => "$code – $name"])->all();
    }

    /**
     * Nhận mã (79, 079, "079") hoặc tên tỉnh (có/không dấu, có/không "Tỉnh", "TP.") và trả về mã 3 số; null nếu không nhận ra.
     * Excel hay làm mất số 0 đầu của mã nên "79" cũng được chấp nhận.
     */
    public static function resolve(mixed $input): ?string
    {
        $s = trim((string) $input);
        if ($s === '') {
            return null;
        }
        if (ctype_digit($s) && strlen($s) <= 3) {
            $code = str_pad($s, 3, '0', STR_PAD_LEFT);

            return isset(self::LIST[$code]) ? $code : null;
        }

        $key = self::norm($s);
        foreach (self::LIST as $code => $name) {
            if (self::norm($name) === $key) {
                return $code;
            }
        }

        return self::ALIASES[$key] ?? null;
    }

    private static function norm(string $s): string
    {
        $n = preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($s)));

        return (string) preg_replace('/^(thanhpho|tinh|tp)/', '', $n);
    }
}
