<x-filament-panels::page>
    {{-- Filter tanggal --}}
    <div class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Dari Tanggal</label>
            <input type="date" wire:model.live="tanggal_mulai"
                class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-sm" />
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Sampai Tanggal</label>
            <input type="date" wire:model.live="tanggal_selesai"
                class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-sm" />
        </div>
        <button wire:click="exportCsv"
            style="background-color:#16a34a;color:white;"
            class="px-4 py-2 rounded-lg text-sm font-semibold hover:opacity-90 flex items-center gap-2">
            ⬇ Export CSV
        </button>
    </div>

    {{-- Summary cards - pemilik only --}}
    @if(auth()->user()->isPemilik())
    <div class="grid grid-cols-2 gap-4 mt-4">
        <div class="p-4 bg-green-100 dark:bg-green-900 rounded-lg">
            <p class="text-sm text-green-700 dark:text-green-300">Total Penjualan</p>
            <p class="text-2xl font-bold text-green-800 dark:text-green-100">
                Rp {{ number_format($this->getTotalPenjualan(), 0, ',', '.') }}
            </p>
        </div>
        <div class="p-4 bg-orange-100 dark:bg-orange-900 rounded-lg">
            <p class="text-sm text-orange-700 dark:text-orange-300">Total Pembelian</p>
            <p class="text-2xl font-bold text-orange-800 dark:text-orange-100">
                Rp {{ number_format($this->getTotalPembelian(), 0, ',', '.') }}
            </p>
        </div>
    </div>
    @endif

    {{-- Tabel Penjualan --}}
    <div class="mt-6">
        <h2 class="text-lg font-semibold mb-2">Transaksi Penjualan</h2>
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-2 text-left">Kode</th>
                        <th class="px-4 py-2 text-left">Tanggal</th>
                        <th class="px-4 py-2 text-left">Kasir</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->getPenjualan() as $trx)
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td class="px-4 py-2">{{ $trx->kode_transaksi }}</td>
                        <td class="px-4 py-2">{{ $trx->tanggal->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">{{ $trx->user->name ?? '-' }}</td>
                        <td class="px-4 py-2">
                            <span style="{{ $trx->status === 'sudah_bayar' ? 'background:#22c55e;color:white;' : 'background:#facc15;color:#1f2937;' }} padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;">
                                {{ $trx->status === 'sudah_bayar' ? 'Sudah Bayar' : 'Belum Bayar' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">Rp {{ number_format($trx->total, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-4 text-center text-gray-400">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tabel Pembelian - pemilik only --}}
    @if(auth()->user()->isPemilik())
    <div class="mt-6">
        <h2 class="text-lg font-semibold mb-2">Pembelian Bahan Baku</h2>
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-2 text-left">ID</th>
                        <th class="px-4 py-2 text-left">Tanggal</th>
                        <th class="px-4 py-2 text-left">Supplier</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->getPembelian() as $beli)
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td class="px-4 py-2">#{{ $beli->id }}</td>
                        <td class="px-4 py-2">{{ $beli->tanggal->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">{{ $beli->supplier->nama ?? '-' }}</td>
                        <td class="px-4 py-2">
                            <span style="{{ $beli->status === 'lunas' ? 'background:#22c55e;color:white;' : 'background:#facc15;color:#1f2937;' }} padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;">
                                {{ $beli->status === 'lunas' ? 'Lunas' : 'Belum Lunas' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">Rp {{ number_format($beli->total, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-4 text-center text-gray-400">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</x-filament-panels::page>
