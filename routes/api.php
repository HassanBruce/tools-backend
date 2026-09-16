<?php

use App\Http\Controllers\Api\ConversionController;
use App\Http\Controllers\Api\LeadsFinderController;
use App\Http\Controllers\Api\LinkCheckerController;
use App\Http\Controllers\Api\PdfToolController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:tools')->group(function () {

    Route::prefix('pdf')->group(function () {
        Route::post('merge', [PdfToolController::class, 'merge']);
        Route::post('split', [PdfToolController::class, 'split']);
        Route::post('compress', [PdfToolController::class, 'compress']);
        Route::post('lock', [PdfToolController::class, 'lock']);
        Route::post('unlock', [PdfToolController::class, 'unlock']);
    });

    Route::prefix('convert')->group(function () {
        Route::post('word-to-pdf', [ConversionController::class, 'wordToPdf']);
        Route::post('pdf-to-word', [ConversionController::class, 'pdfToWord']);
    });

    Route::post('link-checker', [LinkCheckerController::class, 'check']);

    Route::prefix('leads')->group(function () {
        Route::post('search-queries', [LeadsFinderController::class, 'searchQueries']);
    });
});
