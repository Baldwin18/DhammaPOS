<?php

namespace App\Filament\Pages;

use App\Models\Kategori;
use App\Models\Produk;
use App\Models\TransaksiPenjualan;
use App\Models\DetailPenjualan;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class KasirPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Kasir';
    protected static ?string $title = 'Kasir - Input Pesanan';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?int $navigationSort = 0;
    protected static string $view = 'filament.pages.kasir-page';

    public static function canAccess(): bool { return true; }

    public array $cart = [];
    public ?int $kategori_id = null;
    public string $search = '';

    // State untuk modal bayar
    public bool $showBayarModal = false;
    public string $nominalBayar = '';
    public ?int $lastTransaksiId = null;
    public float $kembalian = 0;
    public bool $showKembalian = false;

    public function getKategoris() { return Kategori::all(); }

    public function getProduks()
    {
        return Produk::where('stok', '>', 0)
            ->where('tersedia', true)
            ->when($this->kategori_id, fn($q) => $q->where('kategori_id', $this->kategori_id))
            ->when($this->search, fn($q) => $q->where('nama', 'like', '%' . $this->search . '%'))
            ->get();
    }

    public function filterKategori(?int $id): void { $this->kategori_id = $id; }

    public function addToCart(int $produkId): void
    {
        $produk = Produk::find($produkId);
        if (!$produk) return;

        if (isset($this->cart[$produkId])) {
            if ($this->cart[$produkId]['jumlah'] >= $produk->stok) {
                Notification::make()->title('Stok tidak cukup!')->danger()->send();
                return;
            }
            $this->cart[$produkId]['jumlah']++;
        } else {
            $this->cart[$produkId] = [
                'produk_id' => $produkId,
                'nama' => $produk->nama,
                'harga' => $produk->harga,
                'jumlah' => 1,
                'stok' => $produk->stok,
            ];
        }
        $this->cart[$produkId]['subtotal'] = $this->cart[$produkId]['harga'] * $this->cart[$produkId]['jumlah'];
    }

    public function removeFromCart(int $produkId): void { unset($this->cart[$produkId]); }

    public function updateJumlah(int $produkId, int $jumlah): void
    {
        if ($jumlah <= 0) { $this->removeFromCart($produkId); return; }
        $produk = Produk::find($produkId);
        if ($jumlah > $produk->stok) {
            Notification::make()->title("Stok tidak cukup! Tersedia: {$produk->stok}")->danger()->send();
            return;
        }
        $this->cart[$produkId]['jumlah'] = $jumlah;
        $this->cart[$produkId]['subtotal'] = $this->cart[$produkId]['harga'] * $jumlah;
    }

    public function getTotal(): float { return collect($this->cart)->sum('subtotal'); }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->showBayarModal = false;
        $this->nominalBayar = '';
        $this->showKembalian = false;
    }

    // Buka modal bayar
    public function bukaBayar(): void
    {
        if (empty($this->cart)) {
            Notification::make()->title('Keranjang kosong!')->warning()->send();
            return;
        }
        $this->nominalBayar = '';
        $this->showKembalian = false;
        $this->showBayarModal = true;
    }

    public function tutupBayar(): void { $this->showBayarModal = false; }

    // Hitung kembalian live
    public function updatedNominalBayar(): void
    {
        $nominal = (float) str_replace('.', '', $this->nominalBayar);
        $this->kembalian = max(0, $nominal - $this->getTotal());
    }

    public function konfirmasiBayar(): void
    {
        $bayar = (float) str_replace('.', '', $this->nominalBayar);
        $total = $this->getTotal();

        if ($bayar < $total) {
            Notification::make()->title('Uang kurang!')->body('Kurang Rp ' . number_format($total - $bayar, 0, ',', '.'))->danger()->send();
            return;
        }

        // Simpan transaksi
        $transaksi = TransaksiPenjualan::withoutEvents(function () use ($total, $bayar) {
            return TransaksiPenjualan::create([
                'user_id' => auth()->id(),
                'kode_transaksi' => 'TRX-' . strtoupper(uniqid()),
                'tanggal' => now(),
                'total' => $total,
                'status' => 'sudah_bayar',
                'bayar' => $bayar,
                'kembalian' => $bayar - $total,
            ]);
        });

        foreach ($this->cart as $item) {
            DetailPenjualan::create([
                'transaksi_penjualan_id' => $transaksi->id,
                'produk_id' => $item['produk_id'],
                'jumlah' => $item['jumlah'],
                'harga_jual' => $item['harga'],
                'subtotal' => $item['subtotal'],
            ]);
        }

        // Kurangi stok produk & bahan baku
        foreach ($this->cart as $item) {
            $produk = Produk::find($item['produk_id']);
            if ($produk) {
                $produk->decrement('stok', $item['jumlah']);
                foreach ($produk->resep as $resep) {
                    $kebutuhanBase = \App\Observers\BahanBakuObserver::convertToBase($resep->jumlah * $item['jumlah'], $resep->satuan);
                    $stokBase = \App\Observers\BahanBakuObserver::convertToBase(1, $resep->bahanBaku->satuan);
                    $resep->bahanBaku->decrement('stok', $kebutuhanBase / $stokBase);
                }
            }
        }

        $this->kembalian = $bayar - $total;
        $this->showKembalian = true;
        $this->lastTransaksiId = $transaksi->id;
        $this->cart = [];

        // Dispatch event untuk buka struk otomatis
        $this->dispatch('buka-struk', id: $transaksi->id);
    }

    public function selesai(): void
    {
        $this->showBayarModal = false;
        $this->showKembalian = false;
        $this->nominalBayar = '';
    }

    // Simpan tanpa bayar (makan di tempat)
    public function simpanTransaksi(): void
    {
        if (empty($this->cart)) {
            Notification::make()->title('Keranjang kosong!')->warning()->send();
            return;
        }

        $total = $this->getTotal();

        $transaksi = TransaksiPenjualan::withoutEvents(function () use ($total) {
            return TransaksiPenjualan::create([
                'user_id' => auth()->id(),
                'kode_transaksi' => 'TRX-' . strtoupper(uniqid()),
                'tanggal' => now(),
                'total' => $total,
                'status' => 'belum_bayar',
                'bayar' => 0,
                'kembalian' => 0,
            ]);
        });

        foreach ($this->cart as $item) {
            DetailPenjualan::create([
                'transaksi_penjualan_id' => $transaksi->id,
                'produk_id' => $item['produk_id'],
                'jumlah' => $item['jumlah'],
                'harga_jual' => $item['harga'],
                'subtotal' => $item['subtotal'],
            ]);
        }

        $this->cart = [];
        Notification::make()
            ->title('Pesanan tersimpan!')
            ->body('Kode: ' . $transaksi->kode_transaksi . ' | Total: Rp ' . number_format($total, 0, ',', '.'))
            ->success()->send();
    }
}
