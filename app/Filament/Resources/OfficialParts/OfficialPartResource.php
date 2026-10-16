<?php

namespace App\Filament\Resources\OfficialParts;

use App\Filament\Resources\OfficialParts\Pages\CreateOfficialPart;
use App\Filament\Resources\OfficialParts\Pages\EditOfficialPart;
use App\Filament\Resources\OfficialParts\Pages\ListOfficialParts;
use App\Filament\Resources\Officials\OfficialResource;
use App\Models\OfficialPart;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OfficialPartResource extends Resource
{
    protected static ?string $model = OfficialPart::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|\UnitEnum|null $navigationGroup = 'Dữ liệu';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'giới đàn';

    protected static ?string $pluralModelLabel = 'Giới đàn';

    protected static ?string $navigationLabel = 'Giới đàn';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withOfficialsCount();
    }

    public static function officialsUrl(OfficialPart $part): string
    {
        return OfficialResource::getUrl('index').'?tableFilters[part_id][value]='.$part->id;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])->columnSpanFull()->schema([
                    Section::make('Thông tin giới đàn')->columnSpan(['lg' => 2])->schema([
                        TextInput::make('title')->label('Tên giới đàn')->required()->maxLength(255),
                        TextInput::make('alias')->label('Alias (đường dẫn, tự sinh)')
                            ->visibleOn('edit')->disabled()->dehydrated(false),
                        TextInput::make('note')->label('Ghi chú')->maxLength(255),
                        Toggle::make('status')->label('Hiển thị')->default(true),
                    ]),
                    Section::make('Thống kê')->columnSpan(['lg' => 1])->visibleOn('edit')->schema([
                        Text::make(fn (?OfficialPart $record) => $record
                            ? number_format($record->officials()->count()).' chứng điệp' : '')->weight('bold')->size('lg'),
                        Text::make(function (?OfficialPart $record) {
                            $last = $record?->officials()->max('addtime');

                            return $last ? 'Nhập gần nhất: '.Carbon::createFromTimestamp($last)->locale('vi')->diffForHumans() : 'Chưa có chứng điệp nào';
                        })->color('gray'),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $max = fn () => (int) max([0, ...array_values(OfficialPart::officialCounts())]);

        return $table
            ->defaultSort('weight')
            ->reorderable('weight')
            ->columns([
                TextColumn::make('title')->label('Giới đàn')->weight('semibold')
                    ->description(fn (OfficialPart $p) => $p->alias)
                    ->searchable(['title', 'alias'])->sortable(),
                ViewColumn::make('officials_count')->label('Số chứng điệp')
                    ->view('filament.tables.count-bar')->viewData(['max' => $max()])->sortable(),
                TextColumn::make('note')->label('Ghi chú')->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('status')->label('Hiển thị'),
            ])
            ->recordUrl(fn (OfficialPart $p) => static::getUrl('edit', ['record' => $p]))
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (OfficialPart $p) => $p->officials_count > 0)
                    ->tooltip(fn (OfficialPart $p) => $p->officials_count > 0 ? 'Còn '.number_format($p->officials_count).' chứng điệp, không thể xóa' : null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->before(function (DeleteBulkAction $action, Collection $records) {
                        $used = $records->filter(fn ($p) => $p->officials_count > 0);
                        if ($used->isNotEmpty()) {
                            Notification::make()->danger()->title('Không thể xóa')
                                ->body('Giới đàn còn chứng điệp: '.$used->pluck('title')->implode(', '))->send();
                            $action->halt();
                        }
                    }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOfficialParts::route('/'),
            'create' => CreateOfficialPart::route('/create'),
            'edit' => EditOfficialPart::route('/{record}/edit'),
        ];
    }
}
