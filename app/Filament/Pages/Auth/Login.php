<?php

namespace App\Filament\Pages\Auth;

use App\Support\Captcha;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public string $captchaImage = '';

    public function mount(): void
    {
        parent::mount();

        $this->refreshCaptcha();
    }

    public function refreshCaptcha(): void
    {
        $this->captchaImage = Captcha::make();
        $this->data['captcha'] = '';
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            return parent::authenticate();
        } catch (ValidationException $e) {
            // Sai captcha hoặc sai tài khoản: luôn đổi mã mới
            $this->refreshCaptcha();

            throw $e;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getCaptchaFormComponent(),
            $this->getRememberFormComponent(),
        ]);
    }

    protected function getCaptchaFormComponent(): Component
    {
        return \Filament\Schemas\Components\Group::make([
            View::make('filament.login-captcha')->viewData(fn () => ['image' => $this->captchaImage]),
            TextInput::make('captcha')
                ->label('Mã xác nhận')
                ->placeholder('Nhập các ký tự trong ảnh')
                ->required()
                ->autocomplete('off')
                ->extraInputAttributes(['autocapitalize' => 'characters', 'maxlength' => 5])
                ->rules([fn () => function (string $attribute, $value, \Closure $fail) {
                    if (! Captcha::check($value)) {
                        $fail('Mã xác nhận không đúng.');
                    }
                }]),
        ]);
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Đăng nhập hệ thống quản trị';
    }
}
