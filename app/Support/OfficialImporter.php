<?php

namespace App\Support;

use App\Models\Official;
use App\Models\OfficialPart;
use DateTimeInterface;
use Illuminate\Support\Str;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use OpenSpout\Common\Entity\Row;

/** Nhập chứng điệp từ file Excel/CSV theo tên cột (không phụ thuộc thứ tự cột). */
class OfficialImporter
{
    /** Tiêu đề cột dùng cho file mẫu, theo thứ tự của file nhập cũ. */
    public const COLUMNS = [
        'hoten' => 'Họ và tên',
        'phapdanh' => 'Pháp danh',
        'namsinh' => 'Năm sinh',
        'noisinh' => 'Nơi sinh',
        'ngayxuatgia' => 'Ngày xuất gia',
        'noixuatgia' => 'Nơi xuất gia',
        'diachi' => 'Nơi thường trú',
        'thogioi' => 'Thọ giới',
        'part' => 'Giới đàn',
        'tochucngay' => 'Tổ chức ngày',
        'diachigioidan' => 'Địa chỉ giới đàn',
        'daigioidan' => 'Đại giới đàn',
        'htdandau' => 'Hoà thượng đàn đầu',
        'yetma' => 'Yết ma A-Xà-Lê',
        'giaotho' => 'Giáo thọ A-Xà-Lê',
        'denhat' => 'Đệ nhất',
        'denhi' => 'Đệ nhị',
        'detam' => 'Đệ tam',
        'detu' => 'Đệ tứ',
        'dengu' => 'Đệ ngũ',
        'deluc' => 'Đệ lục',
        'dethat' => 'Đệ thất',
        'image' => 'Ảnh chân dung',
        'chungdiepso' => 'Chứng điệp số',
        'ngaycap' => 'Ngày cấp',
    ];

    /** Tên cột khác được chấp nhận (đã chuẩn hóa) -> field. */
    private const ALIASES = [
        'masogioitu' => 'masothe', 'hinhmattruoc' => 'image', 'hinhmobile' => 'image', 'masothe' => 'masothe', 'hovadem' => 'ho', 'ho' => 'ho', 'ten' => 'ten', 'hovaten' => 'hoten', 'noithuongtru' => 'diachi', 'giodan' => 'part',
        'giaodan' => 'part', 'hoathuongdandau' => 'htdandau', 'yetmaaxale' => 'yetma', 'giaothoaxale' => 'giaotho',
    ];

    private array $parts = [];

    public function __construct(private ?int $defaultPartId = null, private bool $update = false, private bool $dryRun = false)
    {
        foreach (OfficialPart::all() as $p) {
            $this->parts['id:'.$p->id] = $p->id;
            $this->parts['t:'.self::norm($p->title)] = $p->id;
            $this->parts['t:'.self::norm($p->alias)] = $p->id;
        }
    }

    /** @return array{created:int, updated:int, skipped:int, duplicates:int, errors:string[]} */
    public function import(string $path, string $ext): array
    {
        $reader = strtolower($ext) === 'csv' ? new CsvReader() : new XlsxReader();
        $reader->open($path);

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'duplicates' => 0, 'errors' => []];
        $map = null;

        foreach ($reader->getSheetIterator() as $sheet) {
            $line = 0;
            foreach ($sheet->getRowIterator() as $row) {
                $line++;
                $cells = array_map(fn ($c) => $c->getValue(), $row->getCells());

                if ($map === null) {
                    $map = $this->headerMap($cells);
                    if (array_intersect(['ten', 'hoten'], $map) === []) {
                        $map = null; // chưa gặp dòng tiêu đề, thử dòng tiếp theo

                        continue;
                    }

                    continue;
                }

                if (implode('', array_map(fn ($v) => $this->text($v), $cells)) === '') {
                    continue;
                }

                try {
                    $this->importRow($map, $cells, $result);
                } catch (\Throwable $e) {
                    $result['skipped']++;
                    $result['errors'][] = "Dòng $line: ".$e->getMessage();
                }
            }
            break; // chỉ đọc sheet đầu tiên
        }
        $reader->close();

        if ($map === null) {
            $result['errors'][] = 'Không tìm thấy dòng tiêu đề (cần có cột "Họ và tên"). Hãy tải file mẫu.';
        }

        return $result;
    }

    public static function writeTemplate(string $path): void
    {
        $w = new XlsxWriter();
        $w->openToFile($path);
        $w->addRow(Row::fromValues(array_values(self::COLUMNS)));
        $w->close();
    }

    private function headerMap(array $cells): array
    {
        $map = [];
        $byNorm = [];
        foreach (self::COLUMNS as $field => $title) {
            $byNorm[self::norm($title)] = $field;
            $byNorm[$field] = $field;
        }
        $byNorm += self::ALIASES;
        foreach ($cells as $i => $v) {
            $n = self::norm($this->text($v));
            if (isset($byNorm[$n])) {
                $map[$i] = $byNorm[$n];
            }
        }

        return $map;
    }

    private function importRow(array $map, array $cells, array &$result): void
    {
        $d = [];
        foreach ($map as $i => $field) {
            $d[$field] = $cells[$i] ?? null;
        }

        $str = fn ($k) => $this->text($d[$k] ?? null);
        $ho = $str('ho');
        $ten = $str('ten');
        // Cột "Họ và tên" (một cột) được ưu tiên; vẫn nhận file cũ có hai cột "Họ và đệm" + "Tên"
        if (($full = $str('hoten')) !== '') {
            [$ho, $ten] = Official::splitFullName($full);
        }
        if ($ten === '') {
            throw new \RuntimeException('thiếu Họ và tên');
        }

        $partId = $this->resolvePart($str('part'));
        if (! $partId) {
            throw new \RuntimeException('không xác định được Giới đàn "'.$str('part').'"');
        }

        $data = ['ho' => $ho, 'ten' => $ten, 'part_id' => $partId];
        foreach (['masothe', 'chungdiepso', 'phapdanh', 'namsinh', 'noisinh', 'ngayxuatgia', 'noixuatgia', 'diachi', 'thogioi',
            'tochucngay', 'diachigioidan', 'image', 'daigioidan', 'htdandau', 'yetma', 'giaotho', 'denhat', 'denhi',
            'detam', 'detu', 'dengu', 'deluc', 'dethat'] as $k) {
            $data[$k] = in_array($k, ['namsinh', 'ngayxuatgia']) ? $this->dateText($d[$k] ?? null) : $str($k);
            if ($k === 'image') {
                $data[$k] = basename(str_replace('\\', '/', $data[$k]));   // chỉ lấy tên file
            }
        }

        $cap = $this->parseDate($d['ngaycap'] ?? null);
        if ($cap) {
            [$data['ngay_cap'], $data['thang_cap'], $data['nam_cap']] = [$cap['d'], $cap['m'], $cap['y']];
        }

        // Không bao giờ tạo bản ghi lặp: dòng đã có thì cập nhật hoặc bỏ qua tùy lựa chọn
        $existing = $this->findExisting($data);

        if ($existing) {
            if ($this->update) {
                // Không ghi đè bằng ô trống
                if (! $this->dryRun) {
                    $existing->update(array_filter($data, fn ($v) => $v !== '' && $v !== null));
                }
                $result['updated']++;
            } else {
                $result['duplicates']++;
            }

            return;
        }

        if (! $this->dryRun) {
            Official::create($data + ['status' => 1]);
        }
        $result['created']++;
    }

    /**
     * Tìm bản ghi của đúng người này: trùng Mã số giới tử (và cùng người), hoặc trùng Chứng điệp số trong cùng giới đàn.
     * Mã số giới tử đã thuộc về NGƯỜI KHÁC (mã ngẫu nhiên từng bị trùng) thì báo lỗi thay vì ghi đè nhầm.
     */
    private function findExisting(array $data): ?Official
    {
        $name = self::norm($data['ho'].$data['ten']);
        $isSamePerson = fn (Official $o) => (int) $o->part_id === (int) $data['part_id']
            && (($data['chungdiepso'] !== '' && $o->chungdiepso === $data['chungdiepso']) || self::norm($o->ho.$o->ten) === $name);

        if ($data['masothe'] !== '') {
            $sameCode = Official::where('masothe', $data['masothe'])->get();
            if ($sameCode->isNotEmpty()) {
                $match = $sameCode->first($isSamePerson);
                if ($match) {
                    return $match;
                }
                $other = $sameCode->first();
                throw new \RuntimeException('Mã số giới tử '.$data['masothe'].' đã thuộc về chứng điệp #'.$other->id.' ('.trim($other->ho.' '.$other->ten).')');
            }
        }

        return $data['chungdiepso'] !== ''
            ? Official::where('chungdiepso', $data['chungdiepso'])->where('part_id', $data['part_id'])->first()
            : null;
    }

    private function resolvePart(string $value): ?int
    {
        if ($value === '') {
            return $this->defaultPartId;
        }
        if (ctype_digit($value) && isset($this->parts['id:'.$value])) {
            return (int) $value;
        }

        return $this->parts['t:'.self::norm($value)] ?? $this->defaultPartId;
    }

    private function text(mixed $v): string
    {
        if ($v instanceof DateTimeInterface) {
            return $v->format('d/m/Y');
        }
        if (is_float($v) && floor($v) == $v) {
            $v = (int) $v;
        }
        $v = trim((string) $v);

        // Ô công thức Excel không có giá trị đã tính (đọc ra '=AB2&...' hoặc '=') thì coi như trống
        return str_starts_with($v, '=') ? '' : $v;
    }

    /** Ngày giữ nguyên dạng chuỗi dd/mm/yyyy như dữ liệu cũ. */
    private function dateText(mixed $v): string
    {
        $v = $this->excelSerialToDate($v) ?? $v;

        return $v instanceof DateTimeInterface ? $v->format('d/m/Y') : $this->text($v);
    }

    /**
     * Ô ngày trong Excel đôi khi được đọc ra dạng số (ví dụ 31715 = 30/10/1986). Chỉ đổi số 5 chữ số
     * (khoảng 1927–2064) để không nhầm với năm 4 chữ số như 1986.
     */
    private function excelSerialToDate(mixed $v): ?\DateTimeImmutable
    {
        if ((is_int($v) || is_float($v) || (is_string($v) && ctype_digit(trim($v)))) && (float) $v >= 10000 && (float) $v <= 60000) {
            return (new \DateTimeImmutable('1899-12-30'))->modify('+'.(int) $v.' days');
        }

        return null;
    }

    /** @return array{d:int,m:int,y:int}|null */
    private function parseDate(mixed $v): ?array
    {
        $v = $this->excelSerialToDate($v) ?? $v;
        if ($v instanceof DateTimeInterface) {
            return ['d' => (int) $v->format('j'), 'm' => (int) $v->format('n'), 'y' => (int) $v->format('Y')];
        }
        $s = $this->text($v);
        if (preg_match('#^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{4})$#', $s, $m)) {
            return ['d' => (int) $m[1], 'm' => (int) $m[2], 'y' => (int) $m[3]];
        }
        if (preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})#', $s, $m)) {
            return ['d' => (int) $m[3], 'm' => (int) $m[2], 'y' => (int) $m[1]];
        }

        return null;
    }

    private static function norm(string $s): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($s)));
    }
}
