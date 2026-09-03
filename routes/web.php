<?php

use App\Http\Controllers\ChecklistTemplateController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentLockController;
use App\Http\Controllers\DocumentVersionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('documents.index') : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return redirect()->route('documents.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Document Management
    Route::resource('documents', DocumentController::class);

    // Document Locking API
    Route::post('/documents/{document}/heartbeat', [DocumentLockController::class, 'heartbeat'])->name('documents.lock.heartbeat');
    Route::post('/documents/{document}/release-lock', [DocumentLockController::class, 'release'])->name('documents.lock.release');
    Route::get('/documents/{document}/lock-status', [DocumentLockController::class, 'status'])->name('documents.lock.status');

    // Document Versions & Restore
    Route::get('/documents/{document}/versions/{versionNumber}', [DocumentVersionController::class, 'show'])->name('documents.versions.show');
    Route::post('/documents/{document}/versions/{versionNumber}/restore', [DocumentVersionController::class, 'restore'])->name('documents.versions.restore');

    // Real-time Type Detection & Checklists
    Route::get('/api/documents/detect', [DocumentController::class, 'detectType'])->name('api.documents.detect');
    Route::get('/api/checklists/{type}', [ChecklistTemplateController::class, 'getChecklistApi'])->name('api.checklists.byType');

    // Checklist Template Management
    Route::resource('checklists', ChecklistTemplateController::class)->except(['create', 'show', 'edit']);
});

require __DIR__.'/auth.php';
