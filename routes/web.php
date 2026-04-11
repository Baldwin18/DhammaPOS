<?php

use Illuminate\Support\Facades\Route;
use App\Models\TransaksiPenjualan;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/struk/{id}', function ($id) {
    $transaksi = TransaksiPenjualan::with(['user', 'detailPenjualan.produk'])->findOrFail($id);
    return view('struk', compact('transaksi'));
})->middleware(['web', 'auth']);
