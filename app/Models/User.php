<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_active;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /** Được thêm/sửa dữ liệu (Quản trị hoặc Nhập liệu). */
    public function canEdit(): bool
    {
        return in_array($this->role, [UserRole::Admin, UserRole::Editor], true);
    }

    /** Còn mật khẩu {SSHA512} của NukeViet, chưa được đổi sang bcrypt. */
    public function hasLegacyPassword(): bool
    {
        return str_starts_with((string) $this->password, '{SSHA512}');
    }

    public static function otherActiveAdminsExist(self $except): bool
    {
        return static::where('role', UserRole::Admin->value)->where('is_active', true)->where('id', '!=', $except->id)->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
}
