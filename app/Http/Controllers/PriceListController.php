<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PriceListController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $data = Product::query()
            ->withCount('details')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('nama', 'like', "%{$q}%")
                    ->orWhere('kode', 'like', "%{$q}%");
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('price_list.index', compact('data', 'q'));
    }

    public function create()
    {
        return view('price_list.create');
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'kode' => ['nullable', 'string', 'max:255'],
            'nama' => ['required', 'string', 'max:255'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product = Product::create([
            'kode' => $payload['kode'] ?? null,
            'nama' => $payload['nama'],
            'satuan' => $payload['satuan'] ?? null,
            'deskripsi' => $payload['deskripsi'] ?? null,
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);

        return redirect()->route('price_list.show', $product->id);
    }

    public function show(Product $product)
    {
        $product->load(['details' => fn($q) => $q->orderBy('urutan')]);

        $unitPrice = 0;
        foreach ($product->details as $d) {
            $unitPrice += (int) ($d->harga ?? 0);
        }

        return view('price_list.show', compact('product', 'unitPrice'));
    }

    public function edit(Product $product)
    {
        $product->load(['details' => fn($q) => $q->orderBy('urutan')->orderBy('id')]);
        return view('price_list.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $payload = $request->validate([
            'kode' => ['nullable', 'string', 'max:255'],
            'nama' => ['required', 'string', 'max:255'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product->update([
            'kode' => $payload['kode'] ?? null,
            'nama' => $payload['nama'],
            'satuan' => $payload['satuan'] ?? null,
            'deskripsi' => $payload['deskripsi'] ?? null,
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);

        return redirect()->route('price_list.show', $product->id);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('price_list.index');
    }

    public function addDetail(Request $request, Product $product)
    {
        $payload = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'spesifikasi' => ['nullable', 'string'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'harga' => ['required', 'integer', 'min:0'],
        ]);

        return DB::transaction(function () use ($payload, $product) {
            $urutan = (int) ProductDetail::where('product_id', $product->id)->max('urutan');
            $urutan = $urutan > 0 ? $urutan + 1 : 1;

            $qty = (float) $payload['qty'];
            $harga = (int) $payload['harga'];
            $subtotal = (int) round($qty * $harga);

            ProductDetail::create([
                'product_id' => $product->id,
                'urutan' => $urutan,
                'nama' => $payload['nama'],
                'spesifikasi' => $payload['spesifikasi'] ?? null,
                'qty' => $qty,
                'satuan' => $payload['satuan'] ?? null,
                'harga' => $harga,
                'subtotal' => $subtotal,
            ]);

            if ($this->wantsPartial(request())) {
                return $this->detailsPartial($product);
            }

            return redirect()->route('price_list.show', $product->id);
        });
    }

    public function updateDetail(Request $request, Product $product, ProductDetail $detail)
    {
        if ((int) $detail->product_id !== (int) $product->id) abort(404);

        $payload = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'spesifikasi' => ['nullable', 'string'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'harga' => ['required', 'integer', 'min:0'],
        ]);

        $qty = (float) $payload['qty'];
        $harga = (int) $payload['harga'];
        $subtotal = (int) round($qty * $harga);

        $detail->update([
            'nama' => $payload['nama'],
            'spesifikasi' => $payload['spesifikasi'] ?? null,
            'qty' => $qty,
            'satuan' => $payload['satuan'] ?? null,
            'harga' => $harga,
            'subtotal' => $subtotal,
        ]);

        if ($this->wantsPartial($request)) {
            return $this->detailsPartial($product);
        }

        return redirect()->route('price_list.edit', $product->id);
    }

    public function deleteDetail(Product $product, ProductDetail $detail)
    {
        if ((int) $detail->product_id !== (int) $product->id) abort(404);

        $detail->delete();

        $this->reorderDetails($product->id);

        if ($this->wantsPartial(request())) {
            return $this->detailsPartial($product);
        }

        return redirect()->route('price_list.edit', $product->id);
    }

    public function duplicate(Product $product)
    {
        $product->load('details');

        return DB::transaction(function () use ($product) {
            $new = Product::create([
                'kode' => null,
                'nama' => $product->nama . ' (Copy)',
                'satuan' => $product->satuan,
                'deskripsi' => $product->deskripsi,
                'is_active' => $product->is_active,
            ]);

            foreach ($product->details as $d) {
                ProductDetail::create([
                    'product_id' => $new->id,
                    'urutan' => $d->urutan,
                    'nama' => $d->nama,
                    'spesifikasi' => $d->spesifikasi,
                    'qty' => (float) ($d->qty ?? 1),
                    'satuan' => $d->satuan,
                    'harga' => (int) ($d->harga ?? 0),
                    'subtotal' => (int) ($d->subtotal ?? 0),
                ]);
            }

            $this->reorderDetails($new->id);

            return redirect()->route('price_list.show', $new->id);
        });
    }

    private function reorderDetails(int $productId): void
    {
        $rows = ProductDetail::where('product_id', $productId)->orderBy('urutan')->orderBy('id')->get(['id']);
        $n = 1;
        foreach ($rows as $r) {
            ProductDetail::where('id', $r->id)->update(['urutan' => $n++]);
        }
    }

    public function detailsPartial(Product $product)
    {
        $product->load(['details' => fn($q) => $q->orderBy('urutan')->orderBy('id')]);

        $unitPrice = 0;
        foreach ($product->details as $d) {
            $unitPrice += (int) ($d->harga ?? 0);
        }

        return view('price_list.partials.details_table', compact('product', 'unitPrice'));
    }

    private function wantsPartial(Request $request): bool
    {
        return $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->expectsJson();
    }
}
