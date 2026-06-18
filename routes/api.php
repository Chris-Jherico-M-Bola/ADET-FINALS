<?php

use App\Http\Controllers\PresentationController;
use Illuminate\Support\Facades\Route;

Route::get('/presentations', [PresentationController::class, 'index']);
Route::post('/presentations', [PresentationController::class, 'store']);
Route::delete('/presentations/{presentation}', [PresentationController::class, 'destroy']);
Route::post('/presentations/{presentation}/retry', [PresentationController::class, 'retry']);
Route::get('/presentations/{presentation}/pdf', [PresentationController::class, 'pdf'])->name('presentations.pdf');
Route::get('/presentations/{presentation}/pptx', [PresentationController::class, 'pptx'])->name('presentations.pptx');
Route::get('/presentations/{presentation}/notes', [PresentationController::class, 'notes'])->name('presentations.notes');
Route::post('/presentations/{presentation}/notes', [PresentationController::class, 'saveNotes']);
Route::get('/presentations/{presentation}', [PresentationController::class, 'show']);
