<?php

namespace App\Filament\Widgets;

use App\Models\OfficialPart;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Thẻ thống kê riêng cho trang Giới đàn (không hiện ở dashboard). */
class PartStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $parts = OfficialPart::withOfficialsCount()->get();
        $top = $parts->sortByDesc('officials_count')->first();
        $empty = $parts->where('officials_count', 0)->count();

        return [
            Stat::make('Tổng giới đàn', number_format($parts->count()))
                ->description($parts->where('status', 1)->count().' đang hiển thị')
                ->descriptionIcon(Heroicon::OutlinedBuildingLibrary),
            Stat::make('Chưa có chứng điệp', number_format($empty))
                ->description($empty ? 'Xem ở tab "Chưa có chứng điệp"' : 'Giới đàn nào cũng có dữ liệu')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($empty ? 'warning' : 'success'),
            Stat::make('Lớn nhất', $top ? number_format($top->officials_count) : '0')
                ->description($top?->title ?? '—')
                ->descriptionIcon(Heroicon::OutlinedTrophy)
                ->color('success'),
        ];
    }
}
