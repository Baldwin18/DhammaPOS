<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Struk - {{ $transaksi->kode_transaksi }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: monospace; font-size: 12px; width: 300px; margin: 0 auto; padding: 10px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; margin: 2px 0; }
        .right { text-align: right; }
        @media print {
            body { width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    {{-- Header toko --}}
    <div class="center bold" style="font-size:14px;">Dhamma Vegetarian</div>
    <div class="center" style="font-size:10px; margin-top:3px;">Jl. DR. Sutomo No.99, Tj. Garbus Satu,</div>
    <div class="center" style="font-size:10px;">Kec. Lubuk Pakam, Kab. Deli Serdang,</div>
    <div class="center" style="font-size:10px;">Sumatera Utara 20518</div>

    <div class="divider"></div>

    {{-- Info transaksi --}}
    <div class="row">
        <span>{{ $transaksi->tanggal->format('d/m/Y') }}</span>
        <span>Kasir: {{ $transaksi->user->name ?? '-' }}</span>
    </div>
    <div class="row">
        <span>{{ $transaksi->tanggal->format('H:i:s') }}</span>
    </div>
    <div style="margin: 2px 0;">No. {{ $transaksi->kode_transaksi }}</div>

    <div class="divider"></div>

    {{-- Detail item --}}
    @foreach($transaksi->detailPenjualan as $detail)
    <div style="margin: 4px 0;">
        <div>{{ $detail->produk->nama ?? '-' }}</div>
        <div class="row">
            <span>{{ $detail->jumlah }} x Rp {{ number_format($detail->harga_jual, 0, ',', '.') }}</span>
            <span>Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</span>
        </div>
    </div>
    @endforeach

    <div class="divider"></div>

    {{-- Summary --}}
    <div class="row">
        <span>Total Qty</span>
        <span>{{ $transaksi->detailPenjualan->sum('jumlah') }}</span>
    </div>
    <div class="row">
        <span>Sub Total</span>
        <span>Rp {{ number_format($transaksi->total, 0, ',', '.') }}</span>
    </div>
    <div class="row bold">
        <span>Total Harga</span>
        <span>Rp {{ number_format($transaksi->total, 0, ',', '.') }}</span>
    </div>
    @if($transaksi->status === 'sudah_bayar')
    <div class="row">
        <span>Bayar (Cash)</span>
        <span>Rp {{ number_format($transaksi->bayar, 0, ',', '.') }}</span>
    </div>
    <div class="row">
        <span>Kembali</span>
        <span>Rp {{ number_format($transaksi->kembalian, 0, ',', '.') }}</span>
    </div>
    @endif

    <div class="divider"></div>

    <div class="center" style="margin-top:8px; font-size:11px;">Terima kasih telah makan di tempat kami</div>

    @if(request('autoprint'))
    <script>window.onload = () => window.print();</script>
    @else
    <div class="no-print" style="margin-top:20px;text-align:center;">
        <button onclick="window.print()" style="padding:8px 20px;background:#f59e0b;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px;">
            🖨 Cetak Struk
        </button>
        <button onclick="window.close()" style="padding:8px 20px;background:#6b7280;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px;margin-left:8px;">
            Tutup
        </button>
    </div>
    @endif
</body>
</html>
