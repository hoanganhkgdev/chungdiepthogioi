<?php

use App\Http\Controllers\OfficialController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// "/" và "/index.php" cùng trỏ vào đây (Laravel coi index.php là script). Có tham số nv= thì là link NukeViet cũ;
// còn lại vào trang chủ = màn hình đăng nhập admin (đã đăng nhập thì Filament tự chuyển vào dashboard).
Route::get('/', fn (Request $r) => $r->has('nv')
    ? app(OfficialController::class)->legacy($r)
    : redirect('/admin/login'));
Route::redirect('/vi', '/', 301);

// Tiền tố ngôn ngữ cũ /vi/... -> bỏ tiền tố
Route::get('/vi/{path}', fn (string $path) => redirect('/' . $path, 301))->where('path', '.*');

// Chỉ còn trang chi tiết (đích của mã QR), công khai. Hậu tố / hoặc .html được chấp nhận như bản cũ.
Route::get('/official/{part}/{slugId}', [OfficialController::class, 'show'])
    ->where('slugId', '[A-Za-z0-9\-]+(\.html)?')->name('official.show');
