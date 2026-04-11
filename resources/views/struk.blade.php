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
        .total-row { display: flex; justify-content: space-between; font-weight: bold; font-size: 13px; }
        @media print {
            body { width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="center bold" style="font-size:14px;">DHAMMA VEGETARIAN</div>
    <div class="center" style="font-size:11px;">Struk Pembayaran</div>
    <div class="divider"></div>
    <div class="row"><span>Kode</span><span>{{ $transaksi->kode_transaksi }}</span></div>
    <div class="row"><span>Tanggal</span><span>{{ $transaksi->tanggal->format('d/m/Y H:i') }}</span></div>
    <div class="row"><span>Kasir</span><span>{{ $transaksi->user->name ?? '-' }}</span></div>
    <div class="divider"></div>
    @foreach($transaksi->detailPenjualan as $detail)
    <div style="margin: 3px 0;">
        <div>{{ $detail->produk->nama ?? '-' }}</div>
        <div class="row">
            <span>{{ $detail->jumlah }} x Rp {{ number_format($detail->harga_jual, 0, ',', '.') }}</span>
            <span>Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</span>
        </div>
    </div>
    @endforeach
    <div class="divider"></div>
    <div class="total-row"><span>TOTAL</span><span>Rp {{ number_format($transaksi->total, 0, ',', '.') }}</span></div>
    @if($transaksi->status === 'sudah_bayar')
    <div class="row"><span>Bayar</span><span>Rp {{ number_format($transaksi->bayar, 0, ',', '.') }}</span></div>
    <div class="row"><span>Kembalian</span><span>Rp {{ number_format($transaksi->kembalian, 0, ',', '.') }}</span></div>
    @endif
    <div class="divider"></div>
    <div class="center" style="margin-top:6px;">Terima kasih!</div>

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