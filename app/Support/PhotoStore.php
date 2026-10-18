<?php

namespace App\Support;

use App\Models\Official;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Lưu hàng loạt ảnh chân dung vào public/uploads/official (disk "official") và gắn vào chứng điệp
 * đã điền tên file đó ở cột "Ảnh chân dung" (nhập tay hoặc import Excel).
 */
class PhotoStore
{
    public const MAX_SIDE = 1500;   // cỡ tương đương ảnh cũ (khoảng 1100-1500px)

    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(private bool $overwrite = false)
    {
    }

    /**
     * @param  iterable<object>  $files  đối tượng có getClientOriginalName() và getRealPath()
     * @return array{saved:string[], skipped:array<int,array{0:string,1:string}>, errors:string[], linked:int, unmatched:string[]}
     */
    public function store(iterable $files): array
    {
        $disk = Storage::disk('official');
        $report = ['saved' => [], 'skipped' => [], 'errors' => [], 'linked' => 0, 'unmatched' => []];
        $seen = [];

        foreach ($files as $file) {
            $original = (string) $file->getClientOriginalName();
            $name = self::cleanName($original);

            if ($name === null) {
                $report['errors'][] = "$original: không phải ảnh jpg, png hoặc webp";

                continue;
            }
            if (isset($seen[$name])) {
                $report['skipped'][] = [$original, 'trùng tên với ảnh khác trong cùng đợt tải'];

                continue;
            }
            $seen[$name] = true;

            $exists = $disk->exists($name);
            if ($exists && ! $this->overwrite) {
                $report['skipped'][] = [$name, 'đã có file cùng tên (bật "Ghi đè" nếu muốn thay)'];
            } else {
                try {
                    $this->write((string) $file->getRealPath(), $disk->path($name));
                    $report['saved'][] = $name;
                } catch (\Throwable $e) {
                    $report['errors'][] = "$original: ".$e->getMessage();

                    continue;
                }
            }

            // Gắn vào chứng điệp đang tham chiếu tên này (kể cả khi file đã có sẵn): so khớp không phân biệt hoa/thường và dấu
            $linked = $this->link($name, $original);
            $report['linked'] += $linked;
            if ($linked === 0 && ! Official::where('image', $name)->exists()) {
                $report['unmatched'][] = $name;
            }
        }

        Cache::forget(Official::TAB_COUNTS_KEY);

        return $report;
    }

    /** "Nguyễn Văn A.JPG" -> "nguyen-van-a.jpg"; null nếu đuôi file không được phép. */
    public static function cleanName(string $original): ?string
    {
        $info = pathinfo(str_replace('\\', '/', $original));
        $ext = strtolower($info['extension'] ?? '');
        if (! in_array($ext, self::EXTENSIONS, true)) {
            return null;
        }
        $base = Str::of(Str::ascii($info['filename'] ?? ''))->lower()
            ->replaceMatches('/[^a-z0-9._-]+/', '-')->trim('-._')->toString();

        return ($base !== '' ? $base : 'anh-'.Str::lower(Str::random(8))).'.'.$ext;
    }

    /** Cập nhật cột image của các chứng điệp đang ghi tên file này (MySQL unicode_ci bỏ qua hoa/thường và dấu). */
    private function link(string $saved, string $original): int
    {
        $candidates = array_values(array_unique(array_filter([
            $saved,
            trim(basename(str_replace('\\', '/', $original))),
            str_replace(' ', '-', trim(basename(str_replace('\\', '/', $original)))),
        ])));

        $ids = Official::whereIn('image', $candidates)->where('image', '!=', $saved)->pluck('id');
        if ($ids->isNotEmpty()) {
            Official::whereIn('id', $ids)->toBase()->update(['image' => $saved]);   // MyISAM, không cần sự kiện model
        }

        return $ids->count() + Official::where('image', $saved)->whereNotIn('id', $ids)->count();
    }

    /** Chép nguyên file nếu đã đủ nhỏ và đúng chiều; ngược lại xoay đúng hướng và thu nhỏ. */
    private function write(string $from, string $to): void
    {
        $raw = @file_get_contents($from);
        $info = $raw === false ? false : @getimagesizefromstring($raw);
        if ($info === false) {
            throw new \RuntimeException('không đọc được ảnh');
        }
        [$w, $h] = $info;
        $type = $info[2];
        $orientation = 1;
        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $orientation = (int) (@exif_read_data($from)['Orientation'] ?? 1);
        }

        if (max($w, $h) <= self::MAX_SIDE && $orientation === 1) {
            if (! @copy($from, $to)) {
                throw new \RuntimeException('không ghi được file');
            }

            return;
        }

        $img = imagecreatefromstring($raw);
        if ($img === false) {
            throw new \RuntimeException('không xử lý được ảnh');
        }
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
        if ($angle !== 0) {
            $img = imagerotate($img, $angle, 0) ?: $img;
        }
        $w = imagesx($img);
        $h = imagesy($img);
        if (max($w, $h) > self::MAX_SIDE) {
            $scale = self::MAX_SIDE / max($w, $h);
            $img = imagescale($img, (int) round($w * $scale), (int) round($h * $scale), IMG_BICUBIC) ?: $img;
        }

        $ok = match ($type) {
            IMAGETYPE_PNG => (function () use ($img, $to) {
                imagesavealpha($img, true);

                return imagepng($img, $to, 6);
            })(),
            IMAGETYPE_WEBP => imagewebp($img, $to, 85),
            default => imagejpeg($img, $to, 85),
        };
        if (! $ok) {
            throw new \RuntimeException('không ghi được file');
        }
    }
}
