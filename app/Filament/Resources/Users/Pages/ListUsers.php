<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\UserStats;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    protected function getHeaderWidgets(): array
    {
        return [UserStats::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tất cả')->badge(User::count()),
            'admin' => Tab::make('Quản trị')->badge(User::where('role', UserRole::Admin->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', UserRole::Admin->value)),
            'editor' => Tab::make('Nhập liệu')->badge(User::where('role', UserRole::Editor->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', UserRole::Editor->value)),
            'locked' => Tab::make('Đã khóa')->badge(User::where('is_active', false)->count())->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
        ];
    }
}
