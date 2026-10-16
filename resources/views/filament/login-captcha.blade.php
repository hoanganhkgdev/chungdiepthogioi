<div class="captcha-box">
    <img src="{{ $image }}" alt="Mã xác nhận" width="180" height="56">
    <button type="button" wire:click="refreshCaptcha" class="captcha-refresh" title="Đổi mã khác">
        <x-filament::icon icon="heroicon-m-arrow-path" class="h-5 w-5" wire:loading.class="animate-spin" wire:target="refreshCaptcha" />
        <span>Đổi mã</span>
    </button>
</div>
