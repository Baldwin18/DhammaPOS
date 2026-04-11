<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiPenjualanResource\Pages;
use App\Models\Produk;
use App\Models\TransaksiPenjualan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransaksiPenjualanResource extends Resource
{
    protected static ?string $model = TransaksiPenjualan::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Transaksi Penjualan';
    protected static ?string $modelLabel = 'Transaksi Penjualan';
    protected static ?string $pluralModelLabel = 'Transaksi Penjualan';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('kode_transaksi')
                ->default(fn () => 'TRX-' . strtoupper(uniqid()))
                ->readOnly()->required(),
            DatePicker::make('tanggal')->required()->default(now()),
            Repeater::make('detailPenjualan')
                ->relationship()
                ->schema([
                    Select::make('produk_id')
                        ->label('Produk')
                        ->options(Produk::where('stok', '>', 0)->where('tersedia', true)->pluck('nama', 'id'))
                        ->required()->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            $produk = Produk::find($state);
                            if ($produk) {
                                $set('harga_jual', number_format($produk->harga, 0, ',', '.'));
                                $jumlah = floatval($get('jumlah') ?: 1);
                                $set('subtotal', number_format($produk->harga * $jumlah, 0, ',', '.'));
                            }
                        }),
                    TextInput::make('jumlah')->numeric()->required()->default(1)
                        ->live(onBlur: true)
                        ->rules(fn (Get $get) => [
                            'required',
                            'numeric',
                            'min:1',
                            function ($attribute, $value, $fail) use ($get) {
                                $produk = \App\Models\Produk::find($get('produk_id'));
                                if ($produk && $value > $produk->stok) {
                                    $fail("Stok tidak cukup. Stok tersedia: {$produk->stok}");
                                }
                            },
                        ])
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $harga = (float) str_replace('.', '', $get('harga_jual') ?? '');
                            $set('subtotal', number_format(floatval($get('jumlah')) * $harga, 0, ',', '.'));
                        }),
                    TextInput::make('harga_jual')->prefix('Rp')->readOnly()
                        ->dehydrateStateUsing(fn ($state) => (float) str_replace('.', '', $state ?? ''))
                        ->formatStateUsing(fn ($state) => $state ? number_format((float)$state, 0, ',', '.') : ''),
                    TextInput::make('subtotal')->prefix('Rp')->readOnly()
                        ->dehydrateStateUsing(fn ($state) => (float) str_replace('.', '', $state ?? ''))
                        ->formatStateUsing(fn ($state) => $state ? number_format((float)$state, 0, ',', '.') : ''),
                ])->columns(4)->label('Item Pesanan')->addActionLabel('+ Tambah Item')->columnSpanFull()
                ->grid(1)
                ->itemLabel(fn (array $state): ?string => 
                    $state['produk_id'] 
                        ? (Produk::find($state['produk_id'])?->nama ?? 'Item') . ' x' . ($state['jumlah'] ?? 1) . ' = Rp ' . number_format(floatval($state['subtotal'] ?? 0), 0, ',', '.')
                        : null
                )
                ->collapsed(fn (array $state): bool => !empty($state['produk_id'])),
            Placeholder::make('total_label')
                ->label('Total Pembayaran')
                ->content(function (Get $get) {
                    $items = $get('detailPenjualan') ?? [];
                    $total = collect($items)->sum(fn($i) => (float) str_replace('.', '', $i['subtotal'] ?? '0'));
                    return 'Rp ' . number_format($total, 0, ',', '.');
                })->live()->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('kode_transaksi')->searchable()->label('Kode Transaksi'),
            TextColumn::make('tanggal')->date('d/m/Y')->sortable(),
            TextColumn::make('total')->money('IDR')->label('Total'),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn ($state) => $state === 'sudah_bayar' ? 'success' : 'warning')
                ->formatStateUsing(fn ($state) => $state === 'sudah_bayar' ? 'Sudah Bayar' : 'Belum Bayar'),
            TextColumn::make('bayar')
                ->money('IDR')
                ->label('Bayar')
                ->placeholder('-')
                ->formatStateUsing(fn ($state, $record) => $record->status === 'sudah_bayar' ? 'Rp ' . number_format($state, 0, ',', '.') : '-'),
            TextColumn::make('kembalian')
                ->money('IDR')
                ->label('Kembalian')
                ->formatStateUsing(fn ($state, $record) => $record->status === 'sudah_bayar' ? 'Rp ' . number_format($state, 0, ',', '.') : '-'),
            TextColumn::make('user.name')->label('Kasir'),
        ])
        ->defaultSort('id', 'desc')
        ->actions([
            Action::make('cetak_struk')
                ->label('Cetak Struk')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->visible(fn ($record) => $record->status === 'sudah_bayar')
                ->url(fn ($record) => 'javascript:cetakStruk(' . $record->id . ')')
                ->extraAttributes(['wire:navigate.prevent' => true]),
            Action::make('konfirmasi_bayar')
                ->label('Konfirmasi Pembayaran')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn ($record) => $record->status === 'belum_bayar')
                ->form([
                    TextInput::make('bayar')
                        ->label('Nominal Bayar')
                        ->prefix('Rp')
                        ->required()
                        ->placeholder('Contoh: 200.000')
                        ->hint('Gunakan titik sebagai pemisah ribuan. Contoh: 200.000')
                        ->rule('required')
                        ->dehydrateStateUsing(fn ($state) => (float) str_replace('.', '', $state))
                        ->formatStateUsing(fn ($state) => $state ? number_format((float)$state, 0, ',', '.') : '')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Set $set) {
                            $clean = (float) str_replace('.', '', $state ?? '');
                            $set('bayar', $clean > 0 ? number_format($clean, 0, ',', '.') : '');
                        }),
                ])
                ->action(function ($record, array $data) {
                    $bayar = (float) str_replace('.', '', $data['bayar']);
                    if ($bayar < $record->total) {
                        \Filament\Notifications\Notification::make()
                            ->title('Uang Kurang!')
                            ->body('Uang yang diberikan kurang. Segera cek kembali. Total: Rp ' . number_format($record->total, 0, ',', '.') . ', Bayar: Rp ' . number_format($bayar, 0, ',', '.'))
                            ->danger()
                            ->send();
                        return;
                    }
                    // Kurangi stok saat pembayaran dikonfirmasi
                    foreach ($record->detailPenjualan as $detail) {
                        $detail->produk->decrement('stok', $detail->jumlah);
                    }
                    $record->updateQuietly([
                        'bayar' => $bayar,
                        'kembalian' => $bayar - $record->total,
                        'status' => 'sudah_bayar',
                    ]);
                    // Kurangi stok bahan baku sesuai resep (dengan konversi satuan)
                    foreach ($record->detailPenjualan as $detail) {
                        foreach ($detail->produk->resep as $resep) {
                            $kebutuhanBase = \App\Observers\BahanBakuObserver::convertToBase(
                                $resep->jumlah * $detail->jumlah,
                                $resep->satuan
                            );
                            $stokBase = \App\Observers\BahanBakuObserver::convertToBase(
                                1,
                                $resep->bahanBaku->satuan
                            );
                            $pengurangan = $kebutuhanBase / $stokBase;
                            $resep->bahanBaku->decrement('stok', $pengurangan);
                        }
                    }
                    \Filament\Notifications\Notification::make()
                        ->title('Pembayaran Berhasil!')
                        ->body('Kembalian: Rp ' . number_format($bayar - $record->total, 0, ',', '.'))
                        ->success()
                        ->send();
                }),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransaksiPenjualans::route('/'),
            'create' => Pages\CreateTransaksiPenjualan::route('/create'),
            'edit' => Pages\EditTransaksiPenjualan::route('/{record}/edit'),
        ];
    }
}
