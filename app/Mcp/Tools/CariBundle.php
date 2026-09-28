<?php

namespace App\Mcp\Tools;

use App\Models\PenawaranItem;
use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Cari bundle (paket price list) beserta rincian dan harganya. Semua kata kunci harus cocok dengan nama, kode, deskripsi, atau rincian bundle. Diurutkan dari yang paling sering dipakai di penawaran.')]
#[IsReadOnly]
class CariBundle extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'q' => $schema->string()->description('Kata kunci, mis. "AWLR radar" atau "instalasi jawa tengah".')->required(),
            'limit' => $schema->integer()->min(1)->max(20)->default(8),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $query = Product::query()
            ->where('is_active', true)
            ->with('details')
            ->addSelect(['dipakai' => PenawaranItem::query()->selectRaw('count(*)')->whereColumn('product_id', 'products.id')]);

        foreach (preg_split('/\s+/', trim($data['q']), -1, PREG_SPLIT_NO_EMPTY) as $kata) {
            $like = "%{$kata}%";
            $query->where(fn ($q) => $q->where('nama', 'like', $like)
                ->orWhere('kode', 'like', $like)
                ->orWhere('deskripsi', 'like', $like)
                ->orWhereHas('details', fn ($d) => $d->where('nama', 'like', $like)->orWhere('spesifikasi', 'like', $like)));
        }

        $bundle = $query->orderByDesc('dipakai')->orderByDesc('id')->limit($data['limit'] ?? 8)->get();

        return Response::structured(['bundle' => $bundle->map(fn (Product $p) => [
            'id' => $p->id,
            'kode' => $p->kode,
            'nama' => $p->nama,
            'satuan' => $p->satuan,
            'deskripsi' => Str::limit((string) $p->deskripsi, 300),
            'dipakai_di_penawaran' => (int) $p->dipakai,
            // Sama dengan hitungan saat bundle ditambahkan ke penawaran.
            'harga_per_unit' => $p->details->sum(fn ($d) => (int) round((float) ($d->qty ?? 1) * (int) $d->harga)),
            'rincian' => $p->details->map(fn ($d) => [
                'nama' => $d->nama,
                'spesifikasi' => Str::limit((string) $d->spesifikasi, 200),
                'qty' => (float) ($d->qty ?? 1),
                'satuan' => $d->satuan,
                'harga' => (int) $d->harga,
            ])->all(),
        ])->all()]);
    }
}
