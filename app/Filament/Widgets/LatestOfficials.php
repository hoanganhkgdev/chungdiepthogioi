<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Officials\OfficialResource;
use App\Models\Official;
use App\Support\Avatar;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOfficials extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Chứng điệp mới nhập';

    public function table(Table $table): Table
    {
        $t = (new Official)->getTable();

        return $table
            ->query(fn () => Official::query()->with('part')->orderByDesc("$t.id"))
            ->paginated([8, 15, 30])
            ->defaultPaginationPageOption(8)
            ->columns([
                TextColumn::make('stt')->label('STT')->color('gray')
                    ->state(function ($livewire, $rowLoop) {
                        $records = $livewire->getTableRecords();

                        return Official::count() - ($records->currentPage() - 1) * $records->perPage() - ($rowLoop->iteration - 1);
                    }),
                ImageColumn::make('image')->label('')
                    ->disk('official')->circular()->size(44)
                    ->defaultImageUrl(fn (Official $o) => Avatar::initials($o->phapdanh ?: $o->ten)),
                TextColumn::make('phapdanh')->label('Pháp danh / Họ tên / Số chứng điệp')
                    ->weight('semibold')
                    ->placeholder('(chưa có pháp danh)')
                    ->description(fn (Official $o) => trim($o->ho.' '.$o->ten).($o->chungdiepso !== '' ? ' · Số '.$o->chungdiepso : '')),
                TextColumn::make('part.title')->label('Giới đàn')->badge()->color('success'),
                TextColumn::make('thogioi')->label('Thọ giới')->badge()
                    ->color(fn (?string $state) => match (true) {
                        str_contains((string) $state, 'Ni') => 'danger',
                        str_contains((string) $state, 'Sa Di') => 'warning',
                        str_contains((string) $state, 'Tỳ Kheo') => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('notes')->label('Cần chú ý')->badge()->color('warning')
                    ->state(fn (Official $o, $livewire) => array_values(array_filter([
                        $o->status ? null : 'Đang ẩn',
                        $o->image ? null : 'Thiếu ảnh',
                        Official::isDuplicate($o, $livewire->getTableRecords()) ? 'Trùng số' : null,
                    ]))),
                TextColumn::make('addtime')->label('Nhập lúc')
                    ->state(fn (Official $o) => $o->addtime ? Carbon::createFromTimestamp($o->addtime)->locale('vi')->diffForHumans() : '—')
                    ->color('gray'),
            ])
            ->recordUrl(fn (Official $o) => auth()->user()->canEdit() ? OfficialResource::getUrl('edit', ['record' => $o]) : null)
            ->recordActions([
                Action::make('print')->label('In')->icon('heroicon-o-printer')->color('gray')
                    ->url(fn (Official $o) => url($o->path()).'?print=1')->openUrlInNewTab(),
                Action::make('edit')->label('Sửa')->icon('heroicon-o-pencil-square')
                    ->url(fn (Official $o) => OfficialResource::getUrl('edit', ['record' => $o]))
                    ->visible(fn () => auth()->user()->canEdit()),
            ])
            ->headerActions([
                Action::make('all')->label('Xem tất cả')->url(OfficialResource::getUrl('index'))->link(),
            ]);
    }
}
