<?php

namespace App\Mcp\Tools;

use App\Models\Komponen;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Cari komponen satuan di katalog (sensor, panel surya, enclosure, jasa, dll.) beserta harganya. Semua kata kunci harus cocok dengan nama, kode, atau spesifikasi. Pakai `id`-nya sebagai `komponen_id` di rincian `buat-penawaran`.')]
#[IsReadOnly]
class CariKomponen extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'q' => $schema->string()->description('Kata kunci, mis. "sensor radar" atau kode "MPPT-30A".')->required(),
            'limit' => $schema->integer()->min(1)->max(30)->default(15),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $query = Komponen::query()->where('is_active', true);

        foreach (preg_split('/\s+/', trim($data['q']), -1, PREG_SPLIT_NO_EMPTY) as $kata) {
            $like = "%{$kata}%";
            $query->where(fn ($q) => $q->where('nama', 'like', $like)
                ->orWhere('kode', 'like', $like)
                ->orWhere('spesifikasi', 'like', $like));
        }

        $komponen = $query->orderBy('nama')
            ->limit($data['limit'] ?? 15)
            ->get(['id', 'kode', 'nama', 'spesifikasi', 'satuan', 'harga']);

        return Response::structured(['komponen' => $komponen->toArray()]);
    }
}
