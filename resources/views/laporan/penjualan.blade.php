<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>
    <style>
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1, h2, p { margin-top: 0; }
        h1 { margin-bottom: 4px; }
        .period { margin-bottom: 18px; color: #555; }
        .summary { width: 100%; margin-bottom: 24px; border-collapse: collapse; }
        .summary td { padding: 7px; border: 1px solid #ccc; }
        .invoice { margin-bottom: 24px; }
        .invoice + .invoice { page-break-before: always; }
        .invoice-header { margin-bottom: 14px; text-align: center; }
        .invoice-header h2 { margin-bottom: 5px; font-size: 18px; }
        .invoice-number { margin-bottom: 0; color: #555; }
        .details { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
        .details td { padding: 4px 6px; }
        .details td:first-child { width: 100px; color: #555; }
        table.items, table.totals { width: 100%; border-collapse: collapse; }
        .items th, .items td { padding: 7px 6px; border: 1px solid #ccc; text-align: left; }
        .items th { background: #f0f0f0; }
        .number { text-align: right !important; white-space: nowrap; }
        .totals { margin-top: 12px; }
        .totals td { padding: 4px 6px; }
        .totals td:first-child { text-align: right; }
        .grand-total td { border-top: 1px solid #888; font-size: 12px; font-weight: bold; }
        .empty { padding: 12px; border: 1px solid #ccc; text-align: center; }
    </style>
</head>
<body>
    <h1>Laporan Penjualan</h1>
    <p class="period">Periode {{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</p>

    <table class="summary">
        <tr>
            <td>Transaksi: {{ number_format($summary['transactions'], 0, ',', '.') }}</td>
            <td>Omzet: Rp {{ number_format($summary['revenue'], 0, ',', '.') }}</td>
            <td>Harga modal: Rp {{ number_format($summary['cost'], 0, ',', '.') }}</td>
            <td>Laba kotor: {{ $summary['profit'] === null ? 'Belum akurat' : 'Rp '.number_format($summary['profit'], 0, ',', '.') }}</td>
        </tr>
    </table>

    @if($summary['uncosted'] > 0)
        <p>{{ $summary['uncosted'] }} item tidak memiliki catatan harga modal, sehingga laba kotor belum dapat dihitung akurat.</p>
    @endif

    @forelse($sales as $sale)
        <section class="invoice">
            <header class="invoice-header">
                <h2>INVOICE</h2>
                <p class="invoice-number">No: {{ $sale->invoice_number }}</p>
            </header>

            <table class="details">
                <tr><td>Tanggal</td><td>{{ $sale->created_at->format('d/m/Y H:i') }}</td></tr>
                <tr><td>Kasir</td><td>{{ $sale->cashier?->name ?? '-' }}</td></tr>
                <tr><td>Pelanggan</td><td>{{ $sale->customer_name ?: 'Umum' }}</td></tr>
                @if($sale->customer_reference)
                    <tr><td>Referensi</td><td>{{ $sale->customer_reference }}</td></tr>
                @endif
                <tr><td>Pembayaran</td><td>{{ strtoupper(str_replace('_', ' ', $sale->payment_method ?: 'cash')) }}</td></tr>
            </table>

            <table class="items">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th class="number">Jumlah</th>
                        <th class="number">Harga satuan</th>
                        <th class="number">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sale->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td class="number">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                            <td class="number">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="number">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">Tidak ada rincian item.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="totals">
                <tr><td>Subtotal</td><td class="number">Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</td></tr>
                @if($sale->discount_amount > 0)
                    <tr><td>Diskon</td><td class="number">-Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}</td></tr>
                @endif
                @if($sale->tax_amount > 0)
                    <tr><td>Pajak/PPN</td><td class="number">Rp {{ number_format($sale->tax_amount, 0, ',', '.') }}</td></tr>
                @endif
                @if($sale->additional_fee > 0)
                    <tr><td>Biaya lain</td><td class="number">Rp {{ number_format($sale->additional_fee, 0, ',', '.') }}</td></tr>
                @endif
                <tr class="grand-total"><td>TOTAL</td><td class="number">Rp {{ number_format($sale->total, 0, ',', '.') }}</td></tr>
                <tr><td>Dibayar</td><td class="number">Rp {{ number_format($sale->amount_paid, 0, ',', '.') }}</td></tr>
                <tr><td>Kembalian</td><td class="number">Rp {{ number_format($sale->change_due, 0, ',', '.') }}</td></tr>
            </table>
        </section>
    @empty
        <p class="empty">Tidak ada transaksi pada periode ini.</p>
    @endforelse
</body>
</html>
