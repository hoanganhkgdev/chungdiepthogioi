<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Support\Avatar;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?string $modelLabel = 'tài khoản';

    protected static ?string $pluralModelLabel = 'Tài khoản';

    protected static ?string $navigationLabel = 'Tài khoản';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])->columnSpanFull()->schema([
                    Section::make('Thông tin đăng nhập')->columnSpan(['lg' => 2])->columns(2)->schema([
                        TextInput::make('name')->label('Họ tên')->required()->maxLength(255),
                        TextInput::make('email')->label('Email đăng nhập')->email()->required()->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')->label('Mật khẩu')->password()->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->minLength(8)
                            ->helperText(fn (string $operation) => $operation === 'edit' ? 'Để trống nếu không đổi mật khẩu.' : 'Tối thiểu 8 ký tự.')
                            ->suffixAction(Action::make('generate')->icon('heroicon-o-sparkles')->tooltip('Tạo mật khẩu ngẫu nhiên')
                                ->action(function (Set $set) {
                                    $password = Str::password(12, symbols: false);
                                    $set('password', $password);
                                    $set('password_confirmation', $password);
                                })),
                        TextInput::make('password_confirmation')->label('Nhập lại mật khẩu')->password()->revealable()
                            ->same('password')->dehydrated(false)
                            ->required(fn ($get) => filled($get('password'))),
                    ]),
                    Section::make('Phân quyền')->columnSpan(['lg' => 1])->schema([
                        Select::make('role')->label('Vai trò')->options(UserRole::class)->required()
                            ->default(UserRole::Editor->value)->native(false)
                            ->disabled(fn (?User $record) => $record?->is(auth()->user()))
                            ->dehydrated()
                            ->helperText(fn ($state) => UserRole::tryFrom((string) ($state instanceof UserRole ? $state->value : $state))?->getDescription()),
                        Toggle::make('is_active')->label('Cho phép đăng nhập')->default(true)
                            ->disabled(fn (?User $record) => $record?->is(auth()->user()))
                            ->dehydrated()
                            ->helperText('Tắt để khóa tài khoản nhưng vẫn giữ lại.'),
                        Text::make(fn (?User $record) => $record?->last_login_at
                            ? 'Đăng nhập gần nhất: '.$record->last_login_at->locale('vi')->diffForHumans()
                            : 'Chưa từng đăng nhập')->color('gray')->visibleOn('edit'),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                ImageColumn::make('avatar')->label('')->circular()->size(40)
                    ->defaultImageUrl(fn (User $u) => Avatar::initials($u->name)),
                TextColumn::make('name')->label('Họ tên')->weight('semibold')
                    ->description(fn (User $u) => $u->email)
                    ->searchable(['name', 'email'])->sortable(),
                TextColumn::make('role')->label('Vai trò')->badge()->sortable(),
                TextColumn::make('notes')->label('Cần chú ý')->badge()->color('warning')
                    ->state(fn (User $u) => array_values(array_filter([
                        $u->is_active ? null : 'Đã khóa',
                        $u->hasLegacyPassword() ? 'Chưa đổi mật khẩu' : null,
                    ]))),
                TextColumn::make('last_login_at')->label('Đăng nhập gần nhất')->color('gray')->sortable()
                    ->state(fn (User $u) => $u->last_login_at?->locale('vi')->diffForHumans() ?? 'Chưa từng'),
                ToggleColumn::make('is_active')->label('Hoạt động')
                    ->disabled(fn (User $u) => $u->is(auth()->user())),
            ])
            ->recordUrl(fn (User $u) => static::getUrl('edit', ['record' => $u]))
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('reset')->label('Đặt lại mật khẩu')->icon('heroicon-o-key')
                        ->requiresConfirmation()
                        ->modalDescription('Hệ thống tạo mật khẩu mới ngẫu nhiên và chỉ hiển thị một lần. Mật khẩu cũ sẽ không dùng được nữa.')
                        ->action(function (User $record) {
                            $password = Str::password(12, symbols: false);
                            $record->update(['password' => $password]);
                            Notification::make()->success()->persistent()
                                ->title('Mật khẩu mới của '.$record->name)
                                ->body(new HtmlString('<b style="font-size:1.1rem;letter-spacing:.05em">'.e($password).'</b><br>Chỉ hiển thị một lần, hãy gửi cho người dùng ngay.'))
                                ->send();
                        }),
                    DeleteAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
