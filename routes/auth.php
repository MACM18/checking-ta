<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetOtpController;
use App\Http\Controllers\Auth\PasswordSetupController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// 24-Hour Magic Invitation Link (Accessible anytime)
Route::get('invitation/{token}', [InvitationController::class, 'accept'])
    ->name('invitation.accept');

Route::middleware('guest')->group(function () {

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // 15-Minute Email OTP Forgot Password
    Route::get('forgot-password', [PasswordResetOtpController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetOtpController::class, 'sendOtp'])
        ->name('password.email');

    Route::get('forgot-password/verify', [PasswordResetOtpController::class, 'showVerify'])
        ->name('password.otp.verify');

    Route::post('forgot-password/reset', [PasswordResetOtpController::class, 'verifyAndReset'])
        ->name('password.otp.reset');

    Route::post('forgot-password/resend', [PasswordResetOtpController::class, 'resendOtp'])
        ->name('password.otp.resend');
});

Route::middleware('auth')->group(function () {
    // Mandatory First-Time Password Setup for Invited Users
    Route::get('set-password', [PasswordSetupController::class, 'create'])
        ->name('password.setup');

    Route::post('set-password', [PasswordSetupController::class, 'store'])
        ->name('password.setup.store');

    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
