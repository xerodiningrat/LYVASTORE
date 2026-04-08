<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\AffiliateController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth'])->group(function () {
    Route::get('profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profil', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('profil/affiliate/daftar', [AffiliateController::class, 'apply'])->name('profile.affiliate.apply');
    Route::post('profil/affiliate/tarik', [AffiliateController::class, 'withdraw'])->name('profile.affiliate.withdraw');
});

Route::middleware(['auth', 'whatsapp.verified'])->group(function () {

    Route::get('keamanan-akun', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('keamanan-akun', [PasswordController::class, 'update'])->name('password.update');

    Route::redirect('settings', 'profil');
    Route::redirect('settings/profile', 'profil');
    Route::redirect('settings/password', 'keamanan-akun');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');
});
