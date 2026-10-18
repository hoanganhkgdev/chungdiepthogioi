<?php

namespace App\Filament\Resources\Officials\Pages;

use App\Filament\Resources\Officials\OfficialResource;
use App\Support\OfficialImporter;
use App\Support\PhotoStore;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use App\Models\Official;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Storage;

class ListOfficials extends ListRecords
{
    protected static string $resource = OfficialResource::class;

    /** Cho phép liên kết kèm bộ lọc, ví dụ từ trang Giới đàn: ?tableFilters[part_id][value]=2 */
    #[Url]
    public ?array $tableFilters = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('template')
                ->label('Tải file mẫu')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn () => auth()->user()->canEdit())
                ->action(function () {
                    $path = tempnam(sys_get_temp_dir(), 'tpl');
                    OfficialImporter::writeTemplate($path);

                    return response()->download($path, 'mau-import-chung-diep.xlsx')->deleteFileAfterSend();
                }),
            Action::make('bulkPhotos')
                ->label('Tải ảnh hàng loạt')
                ->icon('heroicon-o-photo')
                ->color('gray')
                ->visible(fn () => auth()->user()->canEdit())
                ->modalHeading('Tải ảnh chân dung hàng loạt')
                ->modalDescription('Chọn nhiều ảnh cùng lúc (tối đa 200 ảnh, mỗi ảnh tối đa 8 MB). Tên file ảnh phải trùng với tên đã điền ở cột "Ảnh chân dung" (không phân biệt hoa thường và dấu). Ảnh lớn tự thu nhỏ còn tối đa 1500px.')
                ->modalSubmitActionLabel('Tải lên')
                ->schema([
                    FileUpload::make('photos')->label('Ảnh chân dung')->required()
                        ->multiple()->maxFiles(200)->maxSize(8192)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->storeFiles(false)->panelLayout('grid'),
                    Toggle::make('overwrite')->label('Ghi đè nếu đã có file cùng tên')
                        ->helperText('Tắt: ảnh trùng tên với file đã có sẽ được bỏ qua, ảnh cũ giữ nguyên.'),
                ])
                ->action(function (array $data) {
                    $overwrite = (bool) ($data['overwrite'] ?? false);
                    $r = (new PhotoStore($overwrite))->store($data['photos'] ?? []);

                    $saved = count($r['saved']);
                    $existing = count($r['existing']);
                    $dupInBatch = count($r['skipped']) - $existing;
                    $list = fn (array $items, int $max = 12) => e(implode(', ', array_slice($items, 0, $max))).(count($items) > $max ? ' …' : '');

                    $lines = [];
                    if ($saved > 0) {
                        $lines[] = "✔ Đã lưu <b>$saved</b> ảnh".($overwrite ? ' (thay ảnh cũ nếu trùng tên)' : ' mới').'.';
                    }
                    if ($r['linked'] > 0) {
                        $lines[] = "✔ Đã gắn ảnh vào <b>{$r['linked']}</b> chứng điệp.";
                    }
                    if ($existing > 0) {
                        $lines[] = "ℹ <b>$existing</b> ảnh đã có sẵn trên hệ thống nên được giữ nguyên, không ghi đè."
                            .' Muốn thay bằng ảnh vừa chọn: bật “Ghi đè nếu đã có file cùng tên” rồi tải lại.';
                    }
                    if ($dupInBatch > 0) {
                        $lines[] = "⚠ $dupInBatch ảnh bị bỏ qua vì trùng tên với ảnh khác trong cùng lần tải.";
                    }
                    if ($r['unmatched']) {
                        $lines[] = '⚠ <b>'.count($r['unmatched']).'</b> ảnh đã lưu nhưng chưa có chứng điệp nào ghi tên file này (kiểm tra lại cột “Ảnh chân dung”): '.$list($r['unmatched']);
                    }
                    if ($r['errors']) {
                        $lines[] = '✖ '.count($r['errors']).' ảnh bị lỗi: '.$list($r['errors'], 6);
                    }

                    $title = match (true) {
                        $saved === 0 && $existing > 0 && ! $r['errors'] => 'Các ảnh này đã có sẵn trên hệ thống',
                        $saved === 0 && $r['errors'] !== [] => 'Không tải được ảnh',
                        default => 'Tải ảnh xong',
                    };

                    Notification::make()
                        ->title($title)
                        ->body(new \Illuminate\Support\HtmlString(implode('<br>', $lines) ?: 'Không có ảnh nào được xử lý.'))
                        ->status($r['errors'] || $r['unmatched'] ? 'warning' : ($saved === 0 && $existing > 0 ? 'info' : 'success'))
                        ->persistent()
                        ->send();
                }),
            Action::make('import')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(fn () => auth()->user()->canEdit())
                ->modalHeading('Import chứng điệp từ Excel')
                ->modalDescription('Cột được nhận theo tên tiêu đề (không phụ thuộc thứ tự). Bắt buộc có cột "Họ và tên" (file cũ có hai cột "Họ và đệm" và "Tên" vẫn dùng được). Mã số giới tử được hệ thống tự tạo từ mã tỉnh của giới đàn. Cột "Ảnh chân dung" chỉ điền tên file (ví dụ thich-minh-tru.jpg), sau đó dùng nút "Tải ảnh hàng loạt" để đưa ảnh lên. Dùng "Tải file mẫu" để lấy đúng tiêu đề.')
                ->modalSubmitActionLabel('Import')
                ->schema([
                    FileUpload::make('file')->label('File .xlsx hoặc .csv')->required()
                        ->disk('local')->directory('imports')->visibility('private')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv', 'text/plain', 'application/csv',
                        ]),
                    Select::make('part_id')->label('Giới đàn mặc định')
                        ->helperText('Dùng cho dòng nào ở cột "Giới đàn" để trống hoặc không khớp tên giới đàn.')
                        ->options(fn () => \App\Models\OfficialPart::orderBy('weight')->pluck('title', 'id'))
                        ->searchable(),
                    Toggle::make('update')->label('Cập nhật chứng điệp đã có')->default(true)
                        ->helperText('Hệ thống không bao giờ tạo bản ghi lặp: dòng trùng Mã số giới tử hoặc trùng Chứng điệp số trong cùng giới đàn được coi là đã có. Bật: ghi đè các ô có dữ liệu. Tắt: bỏ qua dòng đó. Ô Mã số giới tử để trống sẽ được tự tạo.'),
                ])
                ->action(function (array $data) {
                    $path = Storage::disk('local')->path($data['file']);
                    try {
                        $r = (new OfficialImporter($data['part_id'] ?? null, (bool) ($data['update'] ?? true)))
                            ->import($path, pathinfo($path, PATHINFO_EXTENSION));
                    } finally {
                        Storage::disk('local')->delete($data['file']);
                    }

                    $body = "Thêm mới: {$r['created']} · Cập nhật: {$r['updated']} · Bỏ qua (đã có): {$r['duplicates']} · Lỗi: {$r['skipped']}";
                    if ($r['errors']) {
                        $body .= '<br>'.e(implode(' | ', array_slice($r['errors'], 0, 15)))
                            .(count($r['errors']) > 15 ? ' …' : '');
                    }

                    Notification::make()
                        ->title('Import xong')
                        ->body(new \Illuminate\Support\HtmlString($body))
                        ->status($r['skipped'] || $r['errors'] ? 'warning' : 'success')
                        ->persistent()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $c = Official::tabCounts();

        return [
            'all' => Tab::make('Tất cả')->badge($c['all']),
            'hidden' => Tab::make('Đang ẩn')->badge($c['hidden'])->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('nv4_vi_official_rows.status', 0)),
            'noimage' => Tab::make('Thiếu ảnh')->badge($c['noimage'])->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('nv4_vi_official_rows.image', '')),
        ];
    }
}
