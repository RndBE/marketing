<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductDetail;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            'kode' => ['nullable', 'string', 'max:255', Rule::unique('products', 'kode')],
            'nama' => ['required', 'string', 'max:255'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'kode.unique' => 'Kode bundle sudah dipakai. Gunakan kode lain.',
        ]);

        $kode = trim((string) ($payload['kode'] ?? ''));
        $kode = $kode !== '' ? $kode : null;

        try {
            $product = Product::create([
                'kode' => $kode,
                'nama' => $payload['nama'],
                'satuan' => $payload['satuan'] ?? null,
                'deskripsi' => $payload['deskripsi'] ?? null,
                'is_active' => (bool) ($payload['is_active'] ?? true),
            ]);
        } catch (QueryException $e) {
            if ($this->isDuplicateKodeError($e)) {
                return back()->withInput()->withErrors([
                    'kode' => 'Kode bundle sudah dipakai. Gunakan kode lain.',
                ]);
            }

            throw $e;
        }

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
            'kode' => ['nullable', 'string', 'max:255', Rule::unique('products', 'kode')->ignore($product->id)],
            'nama' => ['required', 'string', 'max:255'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'kode.unique' => 'Kode bundle sudah dipakai. Gunakan kode lain.',
        ]);

        $kode = trim((string) ($payload['kode'] ?? ''));
        $kode = $kode !== '' ? $kode : null;

        try {
            $product->update([
                'kode' => $kode,
                'nama' => $payload['nama'],
                'satuan' => $payload['satuan'] ?? null,
                'deskripsi' => $payload['deskripsi'] ?? null,
                'is_active' => (bool) ($payload['is_active'] ?? true),
            ]);
        } catch (QueryException $e) {
            if ($this->isDuplicateKodeError($e)) {
                return back()->withInput()->withErrors([
                    'kode' => 'Kode bundle sudah dipakai. Gunakan kode lain.',
                ]);
            }

            throw $e;
        }

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

    public function reorderDetail(Request $request, Product $product)
    {
        $payload = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $ids = $payload['ids'];
        $count = ProductDetail::where('product_id', $product->id)
            ->whereIn('id', $ids)
            ->count();

        if ($count !== count($ids)) {
            return response()->json(['message' => 'Data detail tidak valid.'], 422);
        }

        $n = 1;
        foreach ($ids as $id) {
            ProductDetail::where('product_id', $product->id)
                ->where('id', $id)
                ->update(['urutan' => $n++]);
        }

        return response()->json(['message' => 'Urutan rincian diperbarui']);
    }

    public function bulkImport(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        if (($handle = fopen($path, 'r')) === false) {
            return redirect()->route('price_list.index')->with('error', 'Gagal membaca file CSV');
        }

        $header = fgetcsv($handle);
        $headerMap = $this->mapCsvHeader($header);

        $imported = 0;
        $updated = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) {
                continue;
            }

            $data = $this->extractProductRow($row, $headerMap);

            if ($data['nama'] === '') {
                continue;
            }

            $payload = [
                'kode' => $data['kode'] !== '' ? $data['kode'] : null,
                'nama' => $data['nama'],
                'satuan' => $data['satuan'] !== '' ? $data['satuan'] : null,
                'deskripsi' => $data['deskripsi'] !== '' ? $data['deskripsi'] : null,
            ];

            try {
                $isActive = $this->parseCsvBoolean($data['is_active']);
                if ($payload['kode']) {
                    $existing = Product::where('kode', $payload['kode'])->first();
                    if ($existing) {
                        if ($isActive !== null) {
                            $payload['is_active'] = $isActive;
                        }
                        $existing->update($payload);
                        $updated++;
                        continue;
                    }
                }

                if ($isActive === null) {
                    $payload['is_active'] = true;
                } else {
                    $payload['is_active'] = $isActive;
                }

                Product::create($payload);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Baris '{$data['nama']}': " . $e->getMessage();
            }
        }

        fclose($handle);

        $message = "Import selesai. {$imported} data baru ditambahkan, {$updated} data diupdate.";

        if (count($errors) > 0) {
            $message .= ' Dengan ' . count($errors) . ' error.';
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'imported' => $imported,
                'updated' => $updated,
                'errors' => $errors,
            ]);
        }

        return redirect()->route('price_list.index')->with('success', $message);
    }

    private function mapCsvHeader(?array $header): array
    {
        if (empty($header)) {
            return [];
        }

        $map = [];
        foreach ($header as $i => $col) {
            $key = strtolower(trim((string) $col));
            if ($key === 'kode') $map['kode'] = $i;
            if ($key === 'nama') $map['nama'] = $i;
            if ($key === 'satuan') $map['satuan'] = $i;
            if ($key === 'deskripsi') $map['deskripsi'] = $i;
            if (in_array($key, ['is_active', 'active', 'status'], true)) $map['is_active'] = $i;
        }

        return $map;
    }

    private function extractProductRow(array $row, array $headerMap): array
    {
        if (!empty($headerMap)) {
            return [
                'kode' => trim((string) ($row[$headerMap['kode']] ?? '')),
                'nama' => trim((string) ($row[$headerMap['nama']] ?? '')),
                'satuan' => trim((string) ($row[$headerMap['satuan']] ?? '')),
                'deskripsi' => trim((string) ($row[$headerMap['deskripsi']] ?? '')),
                'is_active' => trim((string) ($row[$headerMap['is_active']] ?? '')),
            ];
        }

        return [
            'kode' => trim((string) ($row[0] ?? '')),
            'nama' => trim((string) ($row[1] ?? '')),
            'satuan' => trim((string) ($row[2] ?? '')),
            'deskripsi' => trim((string) ($row[3] ?? '')),
            'is_active' => trim((string) ($row[4] ?? '')),
        ];
    }

    private function parseCsvBoolean(string $value): ?bool
    {
        $value = strtolower(trim($value));
        if ($value === '') return null;
        if (in_array($value, ['1', 'true', 'ya', 'yes', 'aktif', 'active'], true)) return true;
        if (in_array($value, ['0', 'false', 'no', 'tidak', 'nonaktif', 'inactive'], true)) return false;
        return null;
    }

    private function isDuplicateKodeError(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (string) ($e->errorInfo[1] ?? '');
        $message = strtolower($e->getMessage());

        if (!in_array($sqlState, ['23000', '23505'], true)) {
            return false;
        }

        if (str_contains($message, 'products_kode_unique') || str_contains($message, 'duplicate') || $driverCode === '1062') {
            return true;
        }

        return false;
    }
}
