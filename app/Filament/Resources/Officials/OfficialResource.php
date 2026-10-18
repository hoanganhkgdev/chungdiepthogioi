<?php

namespace App\Filament\Resources\Officials;

use App\Filament\Resources\Officials\Pages\CreateOfficial;
use App\Filament\Resources\Officials\Pages\EditOfficial;
use App\Filament\Resources\Officials\Pages\ListOfficials;
use App\Models\Official;
use App\Support\Avatar;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class OfficialResource extends Resource
{
    public const THO_GIOI = ['Tỳ Kheo', 'Tỳ Kheo Ni', 'Sa Di', 'Sa Di Ni', 'Thức Xoa Ma Na', 'Tu Nữ'];

    protected static ?string $model = Official::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|\UnitEnum|null $navigationGroup = 'Dữ liệu';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'chứng điệp';

    protected static ?string $pluralModelLabel = 'Chứng điệp';

    protected static ?string $navigationLabel = 'Chứng điệp';

    protected static ?string $recordTitleAttribute = 'phapdanh';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('part');
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])->columnSpanFull()->schema([
                    Tabs::make('Chứng điệp')->columnSpan(['lg' => 2])->persistTabInQueryString()->tabs([
                        Tab::make('Thông tin cá nhân')->icon('heroicon-o-user')->columns(2)->schema([
                            TextInput::make('phapdanh')->label('Pháp danh')->required()->maxLength(50),
                            TextInput::make('chungdiepso')->label('Chứng điệp số')->required()->maxLength(255),
                            // Trường ảo: lưu xuống hai cột ho/ten của bảng cũ khi bấm Lưu (xem CreateOfficial/EditOfficial)
                            TextInput::make('hoten')->label('Họ và tên')->required()->maxLength(255)->columnSpanFull()
                                ->afterStateHydrated(function (TextInput $component, ?Official $record) {
                                    if ($record) {
                                        $component->state($record->fullname);
                                    }
                                }),
                            TextInput::make('namsinh')->label('Năm sinh')->placeholder('dd/mm/yyyy')->maxLength(50),
                            TextInput::make('noisinh')->label('Nơi sinh')->maxLength(255),
                            TextInput::make('ngayxuatgia')->label('Ngày xuất gia')->placeholder('dd/mm/yyyy')->maxLength(50),
                            TextInput::make('noixuatgia')->label('Nơi xuất gia')->maxLength(255),
                            TextInput::make('diachi')->label('Nơi thường trú')->maxLength(255),
                            TextInput::make('masothe')->label('Mã số giới tử')->maxLength(255)
                                ->placeholder('Để trống: hệ thống tự tạo')
                                ->live(onBlur: true)
                                ->helperText(function ($state, ?Official $record) {
                                    $state = trim((string) $state);
                                    if ($state === '') {
                                        return 'Để trống: khi lưu hệ thống tự tạo mã = mã tỉnh của giới đàn + 2/3 + năm + 5 số ngẫu nhiên.';
                                    }
                                    $dup = Official::where('masothe', $state)->when($record, fn ($q) => $q->where('id', '!=', $record->id))->first();

                                    return $dup ? new HtmlString('<span style="color:#b45309">⚠ Mã này đã có ở chứng điệp #'.$dup->id.' ('.e(trim($dup->ho.' '.$dup->ten)).'). Mỗi người nên có một mã riêng.</span>') : null;
                                })
                                ->suffixAction(Action::make('generateCode')->icon('heroicon-o-sparkles')->tooltip('Tạo mã theo quy tắc')
                                    ->action(function (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) {
                                        try {
                                            $province = \App\Models\OfficialPart::find($get('part_id'))?->province_code;
                                            if (blank($province)) {
                                                throw new \InvalidArgumentException('Hãy chọn Giới đàn (ở tab "Thọ giới & giới đàn") có mã tỉnh trước khi tạo mã.');
                                            }
                                            $set('masothe', Official::generateMasothe($province, $get('thogioi'), $get('tochucngay'), (int) $get('nam_cap')));
                                        } catch (\InvalidArgumentException $e) {
                                            \Filament\Notifications\Notification::make()->danger()->title($e->getMessage())->send();
                                        }
                                    })),
                        ]),
                        Tab::make('Thọ giới & giới đàn')->icon('heroicon-o-building-library')->columns(2)->schema([
                            TextInput::make('thogioi')->label('Thọ giới')->maxLength(255)
                                ->datalist(self::THO_GIOI)->placeholder('Chọn gợi ý hoặc gõ tay'),
                            Select::make('part_id')->label('Giới đàn')->relationship('part', 'title')
                                ->searchable()->preload()->required()->live(onBlur: true),
                            TextInput::make('tochucngay')->label('Tổ chức ngày')->maxLength(255),
                            TextInput::make('diachigioidan')->label('Địa chỉ giới đàn')->maxLength(255),
                            TextInput::make('daigioidan')->label('Đại giới đàn')->maxLength(255)->columnSpanFull(),
                            Grid::make(3)->columnSpanFull()->schema([
                                TextInput::make('ngay_cap')->label('Ngày cấp')->numeric()->minValue(1)->maxValue(31),
                                TextInput::make('thang_cap')->label('Tháng cấp')->numeric()->minValue(1)->maxValue(12),
                                TextInput::make('nam_cap')->label('Năm cấp')->numeric()->minValue(1900)->maxValue(2200),
                            ]),
                        ]),
                        Tab::make('Hội đồng chứng minh')->icon('heroicon-o-users')->columns(2)->schema([
                            TextInput::make('htdandau')->label('Hòa thượng đàn đầu')->maxLength(255),
                            TextInput::make('yetma')->label('Yết ma A-Xà-Lê')->maxLength(255),
                            TextInput::make('giaotho')->label('Giáo thọ A-Xà-Lê')->maxLength(255),
                            TextInput::make('denhat')->label('Đệ nhất')->maxLength(255),
                            TextInput::make('denhi')->label('Đệ nhị')->maxLength(255),
                            TextInput::make('detam')->label('Đệ tam')->maxLength(255),
                            TextInput::make('detu')->label('Đệ tứ')->maxLength(255),
                            TextInput::make('dengu')->label('Đệ ngũ')->maxLength(255),
                            TextInput::make('deluc')->label('Đệ lục')->maxLength(255),
                            TextInput::make('dethat')->label('Đệ thất')->maxLength(255),
                        ]),
                    ]),
                    Group::make([
                        Section::make('Ảnh & hiển thị')->schema([
                            FileUpload::make('image')->label('Ảnh chân dung')
                                ->disk('official')->image()->imageEditor()->visibility('public')
                                ->getUploadedFileNameForStorageUsing(fn ($file) => str($file->getClientOriginalName())
                                    ->beforeLast('.')->slug()->append('-'.time().'.'.$file->getClientOriginalExtension())->toString()),
                            Toggle::make('status')->label('Hiển thị')->default(true),
                        ]),
                    ])->columnSpan(['lg' => 1])->extraAttributes(['class' => 'lg:sticky lg:top-4 self-start']),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                // STT giảm dần từ trên xuống: dòng đầu = tổng số bản ghi đang lọc, theo từng trang
                TextColumn::make('stt')->label('STT')->color('gray')
                    ->state(function ($livewire, $rowLoop) {
                        $records = $livewire->getTableRecords();

                        return $records->total() - ($records->currentPage() - 1) * $records->perPage() - ($rowLoop->iteration - 1);
                    }),
                ImageColumn::make('image')->label('')
                    ->disk('official')->circular()->size(44)
                    ->defaultImageUrl(fn (Official $o) => Avatar::initials($o->phapdanh ?: $o->ten)),
                TextColumn::make('phapdanh')->label('Pháp danh / Họ tên / Số chứng điệp')
                    ->weight('semibold')->placeholder('(chưa có pháp danh)')
                    ->description(fn (Official $o) => trim($o->ho.' '.$o->ten).($o->chungdiepso !== '' ? ' · Số '.$o->chungdiepso : ''))
                    ->searchable(['phapdanh', 'ho', 'ten', 'chungdiepso']),
                TextColumn::make('masothe')->label('Mã số thẻ')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('part.title')->label('Giới đàn')->badge()->color('success')->sortable(),
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
                        Official::isDuplicateCode($o, $livewire->getTableRecords()) ? 'Trùng mã' : null,
                    ]))),
                TextColumn::make('addtime')->label('Nhập lúc')->sortable()->color('gray')
                    ->state(fn (Official $o) => $o->addtime ? Carbon::createFromTimestamp($o->addtime)->locale('vi')->diffForHumans() : '—'),
                ToggleColumn::make('status')->label('Hiển thị')->disabled(fn () => ! auth()->user()->canEdit()),
            ])
            ->filters([
                SelectFilter::make('part_id')->label('Giới đàn')->relationship('part', 'title')->searchable()->preload(),
                SelectFilter::make('thogioi')->label('Thọ giới')->options(array_combine(self::THO_GIOI, self::THO_GIOI)),
                SelectFilter::make('nam_cap')->label('Năm cấp')
                    ->options(fn () => \Illuminate\Support\Facades\Cache::remember('official.nam-cap', 3600, fn () => Official::where('nam_cap', '>', 0)->distinct()->orderByDesc('nam_cap')->pluck('nam_cap', 'nam_cap')->all())),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->deferFilters(false)
            ->recordUrl(fn (Official $o) => auth()->user()->canEdit() ? static::getUrl('edit', ['record' => $o]) : null)
            ->recordActions([
                Action::make('print')->label('In')->icon('heroicon-o-printer')->color('gray')
                    ->url(fn (Official $o) => url($o->path()).'?print=1')->openUrlInNewTab(),
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])->visible(fn () => auth()->user()->canEdit()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('show')->label('Hiển thị')->icon('heroicon-o-eye')->visible(fn () => auth()->user()->canEdit())
                        ->action(fn (Collection $records) => $records->each->update(['status' => 1]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('hide')->label('Ẩn')->icon('heroicon-o-eye-slash')->visible(fn () => auth()->user()->canEdit())
                        ->action(fn (Collection $records) => $records->each->update(['status' => 0]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOfficials::route('/'),
            'create' => CreateOfficial::route('/create'),
            'edit' => EditOfficial::route('/{record}/edit'),
        ];
    }
}
