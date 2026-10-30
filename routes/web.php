<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockReceiptController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\FileUploadController; // <-- Tambahan Import Controller Upload
use App\Http\Controllers\LaporanController;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistem Minimarket
|--------------------------------------------------------------------------
*/

// 1. Route untuk Halaman Utama / Beranda
Route::get('/', [ProductController::class, 'index'])
    ->name('home');

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('login.store');
Route::get('/register', [LoginController::class, 'registration'])->name('register');
Route::post('/register', [LoginController::class, 'register'])
    ->middleware('throttle:5,1')
    ->name('register.store');
Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('admin')->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');
    Route::get('/admin/transaksi', [AdminController::class, 'transactions'])->name('admin.transactions');
    Route::resource('products', ProductController::class)->except('show');
    Route::get('/admin/laporan', [ReportController::class, 'index'])->name('admin.reports');
    Route::get('/admin/laporan/export', [ReportController::class, 'export'])->name('admin.reports.export');
    Route::get('/admin/stok', [StockReceiptController::class, 'index'])->name('admin.stock');
    Route::post('/admin/stok', [StockReceiptController::class, 'store'])->name('admin.stock.store');
    Route::resource('admin/kategori', CategoryController::class)
        ->except(['create', 'show', 'edit'])
        ->parameters(['kategori' => 'category'])
        ->names('admin.categories');
    Route::resource('admin/supplier', SupplierController::class)
        ->except(['create', 'show', 'edit'])
        ->names('admin.suppliers');
    Route::get('/admin/pengguna', [UserManagementController::class, 'index'])->name('admin.users.index');
    Route::post('/admin/pengguna', [UserManagementController::class, 'store'])->name('admin.users.store');
    Route::put('/admin/pengguna/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/pengguna/{user}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
});

Route::middleware('kasir')->group(function () {
    Route::get('/kasir', [CashierController::class, 'index'])->name('kasir');
    Route::post('/kasir/transaksi', [CashierController::class, 'store'])->name('kasir.sales.store');
    Route::get('/kasir/transaksi', [CashierController::class, 'transactions'])->name('kasir.transactions');
});

Route::get('/transaksi/{sale}', [SaleController::class, 'show'])
    ->middleware('auth')
    ->name('sales.show');

// <-- Tambahan Route untuk Upload File (Modul Minggu 9) -->
Route::get('/upload', [FileUploadController::class, 'create'])->name('file.create');
Route::post('/upload', [FileUploadController::class, 'upload'])->name('file.upload');

Route::get('/laporan-pdf', [LaporanController::class, 'generatePDF'])
    ->middleware('admin')
    ->name('laporan.pdf');