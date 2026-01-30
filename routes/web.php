<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PenawaranController;
use App\Http\Controllers\PenawaranTermTemplateController;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PicController;
use App\Http\Controllers\AlurPenawaranController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KomponenController;
use App\Http\Controllers\UsulanPenawaranController;



Route::get('/', function () {
    return redirect()->route('penawaran.index');
})->middleware('auth');

Route::middleware(['auth'])->group(function () {
    /*
    |---------------- PENAWARAN ----------------|
    */
    Route::prefix('penawaran')->name('penawaran.')->group(function () {
        // Index (semua user bisa lihat)
        Route::get('/', [PenawaranController::class, 'index'])->name('index');

        // Create (butuh permission) - HARUS sebelum /{penawaran}
        Route::middleware('permission:create-penawaran')->group(function () {
            Route::get('/create', [PenawaranController::class, 'create'])->name('create');
            Route::post('/', [PenawaranController::class, 'store'])->name('store');
        });

        // View specific (setelah /create)
        Route::get('/{penawaran}', [PenawaranController::class, 'show'])->name('show');
        Route::get('/{penawaran}/pdf', [PenawaranController::class, 'downloadPdf'])->name('pdf');

        // Edit (butuh permission)
        Route::middleware('permission:edit-penawaran')->group(function () {
            Route::get('/{penawaran}/edit', [PenawaranController::class, 'edit'])->name('edit');
            Route::put('/{penawaran}', [PenawaranController::class, 'update'])->name('update');
            Route::post('/{penawaran}/cover', [PenawaranController::class, 'upsertCover'])->name('cover.upsert');
            Route::post('/{penawaran}/validity', [PenawaranController::class, 'upsertValidity'])->name('validity.upsert');
            Route::post('/{penawaran}/items/bundle', [PenawaranController::class, 'addBundle'])->name('items.bundle');
            Route::post('/{penawaran}/items/custom', [PenawaranController::class, 'addCustomItem'])->name('items.custom');
            Route::delete('/{penawaran}/items/{item}', [PenawaranController::class, 'deleteItem'])->name('items.delete');
            Route::post('/{penawaran}/items/{item}/details', [PenawaranController::class, 'addItemDetail'])->name('item_details.add');
            Route::put('/{penawaran}/items/{item}/details/{detail}', [PenawaranController::class, 'updateItemDetail'])->name('item_details.update');
            Route::delete('/{penawaran}/items/{item}/details/{detail}', [PenawaranController::class, 'deleteItemDetail'])->name('item_details.delete');
            Route::post('/{penawaran}/terms', [PenawaranController::class, 'addTerm'])->name('terms.add');
            Route::delete('/{penawaran}/terms/{term}', [PenawaranController::class, 'deleteTerm'])->name('terms.delete');
            Route::post('/{penawaran}/signatures', [PenawaranController::class, 'addSignature'])->name('signatures.add');
            Route::delete('/{penawaran}/signatures/{signature}', [PenawaranController::class, 'deleteSignature'])->name('signatures.delete');
            Route::post('/{penawaran}/attachments', [PenawaranController::class, 'addAttachment'])->name('attachments.add');
            Route::delete('/{penawaran}/attachments/{attachment}', [PenawaranController::class, 'deleteAttachment'])->name('attachments.delete');
            Route::put('/{penawaran}/pricing', [PenawaranController::class, 'upsertPricing'])->name('pricing.upsert');
            Route::post('/{penawaran}/keterangan', [PenawaranController::class, 'upsertKeterangan'])->name('keterangan.upsert');
        });

        // Delete (butuh permission)
        Route::delete('/{penawaran}', [PenawaranController::class, 'destroy'])
            ->middleware('permission:delete-penawaran')->name('destroy');

        // Deleted list dan request delete
        Route::get('/deleted/list', [PenawaranController::class, 'deletedList'])->name('deleted.list');
        Route::post('/{penawaran}/request-delete', [PenawaranController::class, 'requestDelete'])->name('request.delete');
    });

    /*
    |---------------- USULAN PENAWARAN ------------|
    */
    /*
    |---------------- USULAN PENAWARAN ------------|
    */
    Route::prefix('usulan')->name('usulan.')->group(function () {
        Route::get('/', [UsulanPenawaranController::class, 'index'])->name('index')->middleware('permission:view-usulan');
        Route::get('/create', [UsulanPenawaranController::class, 'create'])->name('create')->middleware('permission:create-usulan');
        Route::post('/', [UsulanPenawaranController::class, 'store'])->name('store')->middleware('permission:create-usulan');
        Route::get('/{usulan}', [UsulanPenawaranController::class, 'show'])->name('show')->middleware('permission:view-usulan');
        Route::get('/{usulan}/edit', [UsulanPenawaranController::class, 'edit'])->name('edit')->middleware('permission:edit-usulan');
        Route::put('/{usulan}', [UsulanPenawaranController::class, 'update'])->name('update')->middleware('permission:edit-usulan');
        Route::post('/{usulan}/tanggapi', [UsulanPenawaranController::class, 'tanggapi'])->name('tanggapi')->middleware('permission:respond-usulan');
        Route::post('/{usulan}/buat-penawaran', [UsulanPenawaranController::class, 'buatPenawaran'])->name('buat-penawaran')->middleware('permission:respond-usulan');
        Route::delete('/{usulan}', [UsulanPenawaranController::class, 'destroy'])->name('destroy')->middleware('permission:delete-usulan');
        Route::delete('/attachment/{attachment}', [UsulanPenawaranController::class, 'deleteAttachment'])->name('attachment.delete')->middleware('permission:edit-usulan');
    });

    /*
    |---------------- PRICE LIST ----------------|
    */
    Route::prefix('price-list')->name('price_list.')->middleware('permission:manage-pricelist')->group(function () {
        Route::get('/', [PriceListController::class, 'index'])->name('index');
        Route::get('/create', [PriceListController::class, 'create'])->name('create');
        Route::post('/', [PriceListController::class, 'store'])->name('store');
        Route::get('/{product}', [PriceListController::class, 'show'])->name('show');
        Route::get('/{product}/edit', [PriceListController::class, 'edit'])->name('edit');
        Route::put('/{product}', [PriceListController::class, 'update'])->name('update');
        Route::delete('/{product}', [PriceListController::class, 'destroy'])->name('destroy');
        Route::post('/{product}/details', [PriceListController::class, 'addDetail'])->name('details.add');
        Route::put('/{product}/details/{detail}', [PriceListController::class, 'updateDetail'])->name('details.update');
        Route::delete('/{product}/details/{detail}', [PriceListController::class, 'deleteDetail'])->name('details.delete');
        Route::post('/{product}/duplicate', [PriceListController::class, 'duplicate'])->name('duplicate');
        Route::get('/{product}/details/partial', [PriceListController::class, 'detailsPartial'])->name('details.partial');
    });

    /*
    |---------------- KOMPONEN ------------------|
    */
    Route::prefix('komponen')->name('komponen.')->middleware('permission:manage-pricelist')->group(function () {
        Route::get('/', [KomponenController::class, 'index'])->name('index');
        Route::post('/', [KomponenController::class, 'store'])->name('store');
        Route::put('/{komponen}', [KomponenController::class, 'update'])->name('update');
        Route::delete('/{komponen}', [KomponenController::class, 'destroy'])->name('destroy');
    });

    // API untuk komponen (tanpa middleware permission agar bisa diakses dari modal)
    Route::get('/api/komponen', [KomponenController::class, 'list'])->name('api.komponen.list');
    Route::get('/api/komponen/{komponen}', [KomponenController::class, 'show'])->name('api.komponen.show');

    /*
    |---------------- TERM TEMPLATES ------------|
    */
    Route::prefix('term-templates')->name('term_templates.')->middleware('permission:edit-penawaran')->group(function () {
        Route::get('/', [PenawaranTermTemplateController::class, 'index'])->name('index');
        Route::get('/create', [PenawaranTermTemplateController::class, 'create'])->name('create');
        Route::post('/', [PenawaranTermTemplateController::class, 'store'])->name('store');
        Route::get('/{template}/edit', [PenawaranTermTemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [PenawaranTermTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [PenawaranTermTemplateController::class, 'destroy'])->name('destroy');
    });

    /*
    |---------------- PIC -----------------------|
    */
    Route::prefix('pics')->name('pics.')->middleware('permission:manage-pic')->group(function () {
        Route::get('/', [PicController::class, 'index'])->name('index');
        Route::get('/create', [PicController::class, 'create'])->name('create');
        Route::post('/', [PicController::class, 'store'])->name('store');
        Route::get('/{pic}/edit', [PicController::class, 'edit'])->name('edit');
        Route::put('/{pic}', [PicController::class, 'update'])->name('update');
        Route::delete('/{pic}', [PicController::class, 'destroy'])->name('destroy');
    });

    /*
    |---------------- ALUR APPROVAL -------------|
    */
    Route::prefix('alur-penawaran')->name('alurpenawaran.')->middleware('permission:manage-alur')->group(function () {
        Route::get('/', [AlurPenawaranController::class, 'index'])->name('index');
        Route::post('/', [AlurPenawaranController::class, 'store'])->name('store');
        Route::put('/{id}', [AlurPenawaranController::class, 'update'])->name('update');
        Route::delete('/{id}', [AlurPenawaranController::class, 'destroy'])->name('destroy');
    });

    /*
    |---------------- APPROVAL ------------------|
    */
    Route::post('/penawaran/{id}/submit-approval', [ApprovalController::class, 'submitPenawaran'])
        ->name('penawaran.submitApproval');

    Route::post('/approval/process', [ApprovalController::class, 'processStep'])
        ->middleware('permission:approve-penawaran')
        ->name('approval.process');

    /*
    |---------------- PROFILE -------------------|
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |---------------- USER MANAGEMENT -----------|
    */
    Route::middleware('permission:manage-users')->group(function () {
        Route::resource('users', UserController::class);
    });

    /*
    |---------------- RBAC ----------------------|
    */
    Route::middleware('permission:manage-roles')->group(function () {
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::put('/{role}', [RoleController::class, 'update'])->name('update');
            Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
            Route::post('/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('syncPermissions');
        });

        Route::prefix('permissions')->name('permissions.')->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->name('index');
            Route::post('/', [PermissionController::class, 'store'])->name('store');
            Route::put('/{permission}', [PermissionController::class, 'update'])->name('update');
            Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('user-roles')->name('user-roles.')->group(function () {
            Route::get('/', [UserRoleController::class, 'index'])->name('index');
            Route::post('/{user}', [UserRoleController::class, 'updateRoles'])->name('update');
        });
    });
});

require __DIR__ . '/auth.php';
