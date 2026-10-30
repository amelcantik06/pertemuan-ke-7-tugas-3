<?php

namespace App\Http\Controllers;

use App\Events\OrderPlaced;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CashierController extends Controller
{
    public function index(): View
    {
        $products = Product::with('category')->where('stock', '>', 0)->orderBy('name')->get();
        $todaySales = Sale::where('user_id', auth()->id())->whereDate('created_at', today())->count();
        $todayRevenue = Sale::where('user_id', auth()->id())->whereDate('created_at', today())->sum('total');
        $recentSales = Sale::where('user_id', auth()->id())->latest()->limit(5)->get();
        $invoicePreview = 'TRX-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4));
        $productData = $products->map(function (Product $product): array {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => $product->barcode,
                'category' => $product->category?->name,
                'price' => $product->price,
                'stock' => $product->stock,
            ];
        })->values();

        return view('cashier.index', compact('products', 'todaySales', 'todayRevenue', 'recentSales', 'invoicePreview', 'productData'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'invoice_number' => ['required', 'string', 'max:40', 'unique:sales,invoice_number'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_reference' => ['nullable', 'string', 'max:100'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'tax_amount' => ['nullable', 'integer', 'min:0'],
            'additional_fee' => ['nullable', 'integer', 'min:0'],
            'amount_paid' => ['required', 'integer', 'min:0'],
            'payment_method' => ['required', 'in:cash,qris,debit,credit,e_wallet,transfer'],
        ]);

        $sale = DB::transaction(function () use ($validated) {
            $quantities = collect($validated['products'])
                ->mapWithKeys(fn (array $item) => [(int) $item['product_id'] => (int) $item['quantity']]);

            $products = Product::whereIn('id', $quantities->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $quantities->count()) {
                throw ValidationException::withMessages([
                    'products' => 'Salah satu produk tidak lagi tersedia.',
                ]);
            }

            $total = 0;
            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);
                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'products' => "Stok {$product->name} tidak mencukupi. Tersedia {$product->stock}.",
                    ]);
                }
                $total += $product->price * $quantity;
            }

            $subtotal = $total;
            $discountPercent = (float) ($validated['discount_percent'] ?? 0);
            $discountFromPercent = (int) round($subtotal * $discountPercent / 100);
            $discountAmount = (int) ($validated['discount_amount'] ?? 0);
            if ($discountFromPercent + $discountAmount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount_amount' => 'Total diskon tidak boleh melebihi subtotal belanja.',
                ]);
            }
            $taxAmount = (int) ($validated['tax_amount'] ?? 0);
            $additionalFee = (int) ($validated['additional_fee'] ?? 0);
            $total = $subtotal - $discountFromPercent - $discountAmount + $taxAmount + $additionalFee;

            if ($validated['amount_paid'] < $total) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Uang yang dibayarkan kurang dari total belanja.',
                ]);
            }

            $sale = Sale::create([
                'invoice_number' => $validated['invoice_number'],
                'user_id' => auth()->id(),
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_reference' => $validated['customer_reference'] ?? null,
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountFromPercent + $discountAmount,
                'tax_amount' => $taxAmount,
                'additional_fee' => $additionalFee,
                'total' => $total,
                'amount_paid' => $validated['amount_paid'],
                'change_due' => max(0, $validated['amount_paid'] - $total),
                'payment_method' => $validated['payment_method'],
            ]);

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);
                $sale->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'unit_cost' => $product->cost_price,
                    'quantity' => $quantity,
                    'subtotal' => $product->price * $quantity,
                ]);
                $product->decrement('stock', $quantity);
            }

            return $sale;
        });

        OrderPlaced::dispatch($sale, $sale->cashier);

        return redirect()->route('sales.show', $sale)->with('success', 'Transaksi berhasil disimpan.');
    }

    public function transactions(): View
    {
        $sales = Sale::where('user_id', auth()->id())
            ->withCount('items')
            ->latest()
            ->paginate(15);

        return view('cashier.transactions', compact('sales'));
    }
}
