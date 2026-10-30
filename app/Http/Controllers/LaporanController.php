<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class LaporanController extends Controller
{
    public function generatePDF(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        if (
            isset($validated['from'], $validated['to'])
            && $validated['to'] < $validated['from']
        ) {
            throw ValidationException::withMessages([
                'to' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
            ]);
        }

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? now()->toDateString())->endOfDay();

        $sales = Sale::with(['cashier', 'items'])
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $items = $sales->flatMap(fn (Sale $sale) => $sale->items);
        $revenue = $sales->sum('total');
        $uncosted = $items->whereNull('unit_cost')->count();
        $cost = $items->whereNotNull('unit_cost')
            ->sum(fn ($item) => $item->unit_cost * $item->quantity);

        $summary = [
            'transactions' => $sales->count(),
            'revenue' => $revenue,
            'cost' => $cost,
            'uncosted' => $uncosted,
            'profit' => $uncosted > 0 ? null : $revenue - $cost,
        ];

        $data = [
            'sales' => $sales,
            'summary' => $summary,
            'from' => $from,
            'to' => $to,
        ];

        $pdf = Pdf::loadView('laporan.penjualan', $data);

        return $pdf->download(
            'laporan-penjualan-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf'
        );
    }
}