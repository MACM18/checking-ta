<?php

use App\Http\Controllers\ChecklistTemplateController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentLockController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\DocumentVersionController;
use App\Http\Controllers\ItemPriceApiController;
use App\Http\Controllers\ItemPriceTrackerController;
use App\Http\Controllers\OrderReservationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShipmentOrderController;
use App\Http\Controllers\UserController;
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

    // Trusted Devices Management
    Route::delete('/profile/devices/{device}', [DeviceController::class, 'destroy'])->name('profile.devices.destroy');
    Route::post('/profile/devices/revoke-others', [DeviceController::class, 'revokeOthers'])->name('profile.devices.revoke-others');

    // Document Management
    Route::get('/documents/{document}/print', [DocumentController::class, 'print'])->name('documents.print');
    Route::resource('documents', DocumentController::class);

    // Document Locking API
    Route::post('/documents/{document}/heartbeat', [DocumentLockController::class, 'heartbeat'])->name('documents.lock.heartbeat');
    Route::post('/documents/{document}/release-lock', [DocumentLockController::class, 'release'])->name('documents.lock.release');
    Route::get('/documents/{document}/lock-status', [DocumentLockController::class, 'status'])->name('documents.lock.status');
    Route::get('/api/documents/live-locks', [DocumentLockController::class, 'allLocks'])->name('documents.lock.all');

    // Document Versions & Restore
    Route::post('/documents/{document}/versions', [DocumentVersionController::class, 'store'])->name('documents.versions.store');
    Route::get('/documents/{document}/versions/{versionNumber}', [DocumentVersionController::class, 'show'])->name('documents.versions.show');
    Route::post('/documents/{document}/versions/{versionNumber}/restore', [DocumentVersionController::class, 'restore'])->name('documents.versions.restore');

    // Real-time Type Detection & Checklists
    Route::get('/api/documents/detect', [DocumentController::class, 'detectType'])->name('api.documents.detect');
    Route::get('/api/documents/source-data/{identifier}', [DocumentController::class, 'getSourceData'])->name('api.documents.sourceData');
    Route::get('/api/checklists/{type}', [ChecklistTemplateController::class, 'getChecklistApi'])->name('api.checklists.byType');

    // Checklist Template Management
    Route::resource('checklists', ChecklistTemplateController::class)->except(['create', 'show', 'edit']);

    // Document Types Management
    Route::resource('document-types', DocumentTypeController::class);

    // Shipment Order Progress Tracker
    Route::resource('shipment-orders', ShipmentOrderController::class);
    Route::post('/shipment-orders/{shipmentOrder}/milestones/{milestone}/toggle', [ShipmentOrderController::class, 'toggleMilestone'])->name('shipment-orders.milestones.toggle');

    // Order Reservations & Warehouse Shortage Tracker
    Route::get('/order-reservations/{orderReservation}/print-shortage', [OrderReservationController::class, 'printShortage'])->name('order-reservations.print-shortage');
    Route::post('/order-reservations/{orderReservation}/confirm-all', [OrderReservationController::class, 'confirmAll'])->name('order-reservations.confirm-all');
    Route::post('/order-reservations/{orderReservation}/items', [OrderReservationController::class, 'updateItems'])->name('order-reservations.update-items');
    Route::post('/order-reservations/{orderReservation}/add-item', [OrderReservationController::class, 'addShortItem'])->name('order-reservations.add-short-item');
    Route::resource('order-reservations', OrderReservationController::class);

    // Reports & Exports Center (Excel & PDF)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/freight-weights', [ReportController::class, 'exportFreightWeights'])->name('reports.freight-weights');
    Route::get('/reports/ongoing-orders', [ReportController::class, 'exportOngoingOrders'])->name('reports.ongoing-orders');
    Route::get('/reports/master-shortage', [ReportController::class, 'exportMasterShortage'])->name('reports.master-shortage');
    Route::get('/reports/reservation-shortage/{orderReservation}', [ReportController::class, 'exportReservationShortage'])->name('reports.reservation-shortage');

    // Admin User Management
    Route::post('/users/{user}/resend-invitation', [UserController::class, 'resendInvitation'])->name('users.resend-invitation');
    Route::post('/users/{user}/invitation-link', [UserController::class, 'generateInvitationLink'])->name('users.invitation-link');
    Route::resource('users', UserController::class)->except(['show']);

    // Item Price Tracker & Excel Importer
    Route::get('/price-tracker', [ItemPriceTrackerController::class, 'index'])->name('price-tracker.index');
    Route::get('/price-tracker/import', [ItemPriceTrackerController::class, 'import'])->name('price-tracker.import');
    Route::post('/price-tracker/import', [ItemPriceTrackerController::class, 'storeImport'])->name('price-tracker.import.store');
    Route::delete('/price-tracker/{item}', [ItemPriceTrackerController::class, 'destroy'])->name('price-tracker.destroy');

    // Price Items Autocomplete & Lookup APIs
    Route::get('/api/price-items/search', [ItemPriceApiController::class, 'search'])->name('api.price-items.search');
    Route::get('/api/price-items/lookup', [ItemPriceApiController::class, 'lookup'])->name('api.price-items.lookup');
    Route::get('/api/price-items/labels', [ItemPriceApiController::class, 'labels'])->name('api.price-items.labels');

    // Admin Permission Management
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::get('/permissions/{user}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
    Route::put('/permissions/{user}', [PermissionController::class, 'update'])->name('permissions.update');
});

require __DIR__.'/auth.php';
