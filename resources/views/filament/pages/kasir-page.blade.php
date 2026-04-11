<x-filament-panels::page>
<div class="flex gap-4" style="height:calc(100vh - 120px)">

    {{-- KIRI: Menu --}}
    <div class="flex-1 flex flex-col gap-3 overflow-hidden">

        {{-- Search --}}
        <input type="text" wire:model.live="search" placeholder="Cari menu..."
            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm" />

        {{-- Kategori filter --}}
        <div class="flex gap-2 flex-wrap">
            <button wire:click="filterKategori(null)"
                style="{{ $kategori_id === null ? 'background:#f59e0b;color:white;' : 'background:#374151;color:#d1d5db;' }} padding:4px 12px;border-radius:999px;font-size:12px;font-weight:600;border:none;cursor:pointer;">
                Semua
            </button>
            @foreach($this->getKategoris() as $kat)
            <button wire:click="filterKategori({{ $kat->id }})"
                style="{{ $kategori_id === $kat->id ? 'background:#f59e0b;color:white;' : 'background:#374151;color:#d1d5db;' }} padding:4px 12px;border-radius:999px;font-size:12px;font-weight:600;border:none;cursor:pointer;">
                {{ $kat->nama }}
            </button>
            @endforeach
        </div>

        {{-- Grid produk --}}
        <div class="overflow-y-auto pr-1" style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;align-content:start;">
            @forelse($this->getProduks() as $produk)
            <button wire:click="addToCart({{ $produk->id }})"
                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-3 text-center hover:border-amber-400 hover:shadow-lg transition flex flex-col items-center gap-2">
                {{-- Gambar bulat --}}
                @if($produk->gambar)
                <img src="{{ Storage::url($produk->gambar) }}" class="w-24 h-24 object-cover rounded-full border-2 border-gray-100 dark:border-gray-600" />
                @else
                <div class="w-24 h-24 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 text-xs border-2 border-gray-200 dark:border-gray-600">No Image</div>
                @endif
                {{-- Info --}}
                <p class="font-semibold text-sm text-center leading-tight">{{ $produk->nama }}</p>
                <p class="text-amber-500 font-bold text-sm">Rp {{ number_format($produk->harga, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400">Stok: {{ $produk->stok }}</p>
            </button>
            @empty
            <div class="col-span-4 text-center text-gray-400 py-8">Tidak ada produk tersedia</div>
            @endforelse
        </div>
    </div>

    {{-- KANAN: Order --}}
    <div class="w-72 flex flex-col bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4" style="height:fit-content; max-height:100%; overflow-y:auto; position:sticky; top:0;">
        <h2 class="font-bold text-lg mb-3">Order</h2>

        {{-- Cart items --}}
        <div class="space-y-2 mb-3">
            @forelse($cart as $produkId => $item)
            <div class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate">{{ $item['nama'] }}</p>
                    <p class="text-xs text-amber-500">Rp {{ number_format($item['harga'], 0, ',', '.') }}</p>
                </div>
                <div class="flex items-center gap-1">
                    <button wire:click="updateJumlah({{ $produkId }}, {{ $item['jumlah'] - 1 }})"
                        class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-600 text-sm font-bold flex items-center justify-center hover:bg-red-200">-</button>
                    <span class="w-6 text-center text-sm font-semibold">{{ $item['jumlah'] }}</span>
                    <button wire:click="updateJumlah({{ $produkId }}, {{ $item['jumlah'] + 1 }})"
                        class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-600 text-sm font-bold flex items-center justify-center hover:bg-green-200">+</button>
                </div>
                <p class="text-xs font-semibold w-16 text-right">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</p>
            </div>
            @empty
            <div class="text-center text-gray-400 text-sm py-4">Belum ada pesanan</div>
            @endforelse
        </div>

        {{-- Total & actions --}}
        <div class="border-t border-gray-200 dark:border-gray-600 pt-3 space-y-2">
            <div class="flex justify-between font-bold text-lg">
                <span>Total</span>
                <span class="text-amber-500">Rp {{ number_format($this->getTotal(), 0, ',', '.') }}</span>
            </div>
            <button wire:click="bukaBayar"
                style="background-color:#16a34a;color:#ffffff;"
                class="w-full py-2 hover:opacity-90 font-semibold text-sm rounded-lg">
                Bayar Sekarang
            </button>
            <button wire:click="simpanTransaksi"
                style="background-color:#f59e0b;color:#ffffff;"
                class="w-full py-2 hover:opacity-90 font-semibold text-sm rounded-lg">
                Simpan (Bayar Nanti)
            </button>
            <button wire:click="clearCart"
                style="color:#374151;"
                class="w-full py-2 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 dark:!text-white rounded-lg text-sm font-medium">
                Batal
            </button>
        </div>
    </div>

</div>

{{-- Modal Bayar --}}
@if($showBayarModal)
<div class="fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,0.5);">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 w-96">
        @if(!$showKembalian)
            <h3 class="font-bold text-xl mb-4">Konfirmasi Pembayaran</h3>
            <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                @foreach($cart as $item)
                <div class="flex justify-between text-sm py-1">
                    <span>{{ $item['nama'] }} x{{ $item['jumlah'] }}</span>
                    <span>Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                </div>
                @endforeach
                <div class="flex justify-between font-bold text-base border-t border-gray-200 dark:border-gray-600 mt-2 pt-2">
                    <span>Total</span>
                    <span class="text-amber-500">Rp {{ number_format($this->getTotal(), 0, ',', '.') }}</span>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Nominal Bayar</label>
                <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden">
                    <span class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-sm font-medium">Rp</span>
                    <input type="text" wire:model.live="nominalBayar"
                        placeholder="Contoh: 50.000"
                        class="flex-1 px-3 py-2 bg-white dark:bg-gray-800 text-sm outline-none"
                        wire:keydown.enter="konfirmasiBayar" />
                </div>
                @if($nominalBayar && (float)str_replace('.', '', $nominalBayar) >= $this->getTotal())
                <p class="text-green-500 text-sm mt-1 font-medium">
                    Kembalian: Rp {{ number_format((float)str_replace('.', '', $nominalBayar) - $this->getTotal(), 0, ',', '.') }}
                </p>
                @elseif($nominalBayar)
                <p class="text-red-500 text-sm mt-1">Uang kurang!</p>
                @endif
            </div>
            <div class="flex gap-2">
                <button wire:click="tutupBayar"
                    class="flex-1 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg text-sm font-medium">
                    Batal
                </button>
                <button wire:click="konfirmasiBayar"
                    style="background-color:#16a34a;color:white;"
                    class="flex-1 py-2 rounded-lg text-sm font-semibold hover:opacity-90">
                    Konfirmasi
                </button>
            </div>
        @else
            {{-- Tampilan kembalian --}}
            <div class="text-center py-4">
                <div class="text-5xl mb-3">✅</div>
                <h3 class="font-bold text-xl mb-1">Pembayaran Berhasil!</h3>
                <p class="text-gray-500 text-sm mb-4">Kembalian untuk pelanggan:</p>
                <div class="text-4xl font-bold text-green-500 mb-6">
                    Rp {{ number_format($kembalian, 0, ',', '.') }}
                </div>
                <div class="flex gap-2">
                    <a href="/struk/{{ $lastTransaksiId }}" target="_blank"
                        style="background-color:#3b82f6;color:white;"
                        class="flex-1 py-3 rounded-lg font-semibold hover:opacity-90 text-center text-sm">
                        🖨 Cetak Struk
                    </a>
                    <button wire:click="selesai"
                        style="background-color:#f59e0b;color:white;"
                        class="flex-1 py-3 rounded-lg font-semibold hover:opacity-90 text-sm">
                        Transaksi Baru
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
@endif
</x-filament-panels::page>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('buka-struk', (event) => {
            cetakStruk(event.id);
        });
    });
</script>
