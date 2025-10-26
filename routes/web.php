<?php

use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('/verify', [VerificationController::class, 'show'])->name('verify.show');
    Route::post('/verify/create-inquiry', [VerificationController::class, 'createInquiry'])->name('verify.create');
    Route::get('/verify/status', [VerificationController::class, 'status'])->name('verify.status');
});
Route::post('/webhooks/persona', [VerificationController::class, 'webhook'])->name('webhooks.persona');


require __DIR__.'/settings.php';
