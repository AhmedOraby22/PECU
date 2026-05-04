<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LegacyApiController;

Route::get('/', function () {
    $path = public_path('index.html');
    if (is_file($path)) {
        return response()->file($path);
    }
    return response()->json(['ok' => true]);
});

Route::match(['GET', 'POST', 'OPTIONS'], '/api.php', [LegacyApiController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
