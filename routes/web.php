<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PenawaranController;
use App\Http\Controllers\PenawaranTermTemplateController;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\ProfileController;


Route::get('/', function () {
    return redirect()->route('penawaran.index');
})->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::prefix('penawaran')->name('penawaran.')->group(function () {

        Route::get('/', [PenawaranController::class, 'index'])->name('index');
        Route::get('/create', [PenawaranController::class, 'create'])->name('create');
        Route::post('/', [PenawaranController::class, 'store'])->name('store');

        Route::get('/{penawaran}', [PenawaranController::class, 'show'])->name('show');
        Route::get('/{penawaran}/edit', [PenawaranController::class, 'edit'])->name('edit');
        Route::put('/{penawaran}', [PenawaranController::class, 'update'])->name('update');
        Route::delete('/{penawaran}', [PenawaranController::class, 'destroy'])->name('destroy');

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

        Route::get('/{penawaran}/pdf', [PenawaranController::class, 'downloadPdf'])->name('pdf');
        Route::put('/{penawaran}/pricing', [PenawaranController::class, 'upsertPricing'])->name('pricing.upsert');
        Route::post('/{penawaran}/keterangan', [PenawaranController::class, 'upsertKeterangan'])->name('keterangan.upsert');
    });

    /*
    |---------------- PRICE LIST ----------------|
    */
    Route::prefix('price-list')->name('price_list.')->group(function () {
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

    Route::prefix('term-templates')->name('term_templates.')->group(function () {
        Route::get('/', [PenawaranTermTemplateController::class, 'index'])->name('index');
        Route::get('/create', [PenawaranTermTemplateController::class, 'create'])->name('create');
        Route::post('/', [PenawaranTermTemplateController::class, 'store'])->name('store');

        Route::get('/{template}/edit', [PenawaranTermTemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [PenawaranTermTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [PenawaranTermTemplateController::class, 'destroy'])->name('destroy');
    });
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
require __DIR__ . '/auth.php';
