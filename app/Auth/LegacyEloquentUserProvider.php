<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Cho phép đăng nhập bằng mật khẩu {SSHA512} của NukeViet; đăng nhập đúng lần đầu thì đổi sang bcrypt.
 */
class LegacyEloquentUserProvider extends EloquentUserProvider
{
    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        $hash = $user->getAuthPassword();

        if (! str_starts_with((string) $hash, '{SSHA512}')) {
            return parent::validateCredentials($user, $credentials);
        }

        $plain = (string) ($credentials['password'] ?? '');
        $raw = base64_decode(substr($hash, 9), true);
        if ($raw === false || strlen($raw) <= 64) {
            return false;
        }

        $ok = hash_equals(substr($raw, 0, 64), hash('sha512', $plain.substr($raw, 64), true));
        if ($ok) {
            $user->forceFill(['password' => $plain])->save(); // cast 'hashed' → bcrypt
        }

        return $ok;
    }
}
