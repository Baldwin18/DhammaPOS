<?php

namespace App\Filament\Pages;

use App\Models\Pembelian;
use App\Models\TransaksiPenjualan;
use Filament\Pages\Page;

class LaporanPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Laporan';
    protected static ?string $title = 'Laporan Penjualan & Pembelian';
    protected static ?string $navigationGroup = 'Laporan';
    protected static string $view = 'filament.pages.laporan-page';

    public static function canAccess(): bool { return true; }

    public string $tanggal_mulai = '';
    public string $tanggal_selesai = '';

    public function mount(): void
    {
        $this->tanggal_mulai = now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_selesai = now()->format('Y-m-d');
    }

    public function getPenjualan()
    {
        return TransaksiPenjualan::with(['user'])
            ->whereDate('tanggal', '>=', $this->tanggal_mulai)
            ->whereDate('tanggal', '<=', $this->tanggal_selesai)
            ->orderBy('tanggal', 'desc')
            ->get();
    }

    public function getPembelian()
    {
        return Pembelian::with(['supplier'])
            ->whereDate('tanggal', '>=', $this->tanggal_mulai)
            ->whereDate('tanggal', '<=', $this->tanggal_selesai)
            ->orderBy('tanggal', 'desc')
            ->get();
    }

    public function getTotalPenjualan(): float
    {
        if (auth()->user()->isKasir()) return 0;
        return (float) TransaksiPenjualan::whereDate('tanggal', '>=', $this->tanggal_mulai)
            ->whereDate('tanggal', '<=', $this->tanggal_selesai)
            ->sum('total');
    }

    public function getTotalPembelian(): float
    {
        if (auth()->user()->isKasir()) return 0;
        return (float) Pembelian::whereDate('tanggal', '>=', $this->tanggal_mulai)
            ->whereDate('tanggal', '<=', $this->tanggal_selesai)
            ->sum('total');
    }

    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $penjualan = $this->getPenjualan();
        $pembelian = $this->getPembelian();
        $filename = 'laporan-' . $this->tanggal_mulai . '-sd-' . $this->tanggal_selesai . '.csv';

        return response()->streamDownload(function () use ($penjualan, $pembelian) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['LAPORAN PENJUALAN']);
            fputcsv($out, ['Kode Transaksi', 'Tanggal', 'Kasir', 'Status', 'Total']);
            foreach ($penjualan as $trx) {
                fputcsv($out, [
                    $trx->kode_transaksi,
                    $trx->tanggal->format('d/m/Y'),
                    $trx->user->name ?? '-',
                    $trx->status === 'sudah_bayar' ? 'Sudah Bayar' : 'Belum Bayar',
                    $trx->total,
                ]);
            }
            fputcsv($out, ['', '', '', 'TOTAL', $penjualan->sum('total')]);
            fputcsv($out, []);
            fputcsv($out, ['LAPORAN PEMBELIAN']);
            fputcsv($out, ['ID', 'Tanggal', 'Supplier', 'Status', 'Total']);
            foreach ($pembelian as $beli) {
                fputcsv($out, [
                    '#' . $beli->id,
                    $beli->tanggal->format('d/m/Y'),
                    $beli->supplier->nama ?? '-',
                    $beli->status === 'lunas' ? 'Lunas' : 'Belum Lunas',
                    $beli->total,
                ]);
            }
            fputcsv($out, ['', '', '', 'TOTAL', $pembelian->sum('total')]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
