<?php

namespace App\Mcp\Tools;

use App\Models\Penawaran;
use App\Models\PenawaranItem;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Cari penawaran lama yang mirip, lengkap dengan item, rincian, harga, diskon, dan statusnya, sebagai contoh komposisi dan harga. Semua kata kunci harus cocok dengan judul, pekerjaan, instansi, lokasi, atau judul item. Terbaru lebih dulu.')]
#[IsReadOnly]
class CariPenawaranSerupa extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'q' => $schema->string()->description('Kata kunci, mis. "AWLR sumur pantau" atau "BBWS Bengawan Solo".')->required(),
            'limit' => $schema->integer()->min(1)->max(8)->default(3),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:8'],
        ]);

        $user = $request->user();

        // Di web, pengguna tanpa hak lihat-semua hanya melihat penawarannya sendiri
        // (plus yang ia setujui atau dibagikan ke perusahaannya). Di sini dipersempit
        // ke miliknya sendiri saja -- tidak pernah lebih luas dari web.
        $query = Penawaran::query()
            ->with(['docNumber', 'company:id,code', 'approval:id,status', 'items.details'])
            ->when(
                ! ($user->hasRole('admin') || $user->hasPermission('view-all-penawaran')),
                fn ($q) => $q->where('id_user', $user->id),
            );

        foreach (preg_split('/\s+/', trim($data['q']), -1, PREG_SPLIT_NO_EMPTY) as $kata) {
            $like = "%{$kata}%";
            $query->where(fn ($q) => $q->where('judul', 'like', $like)
                ->orWhere('nama_pekerjaan', 'like', $like)
                ->orWhere('instansi_tujuan', 'like', $like)
                ->orWhere('lokasi_pekerjaan', 'like', $like)
                ->orWhereHas('items', fn ($i) => $i->where('judul', 'like', $like)));
        }

        $penawaran = $query->latest('id')->limit($data['limit'] ?? 3)->get();

        return Response::structured(['penawaran' => $penawaran->map(fn (Penawaran $p) => [
            'id' => $p->id,
            'no_dokumen' => $p->docNumber?->doc_no,
            'tanggal' => ($p->tanggal_penawaran ?? $p->created_at)?->toDateString(),
            'perusahaan' => $p->company?->code,
            'judul' => $p->judul,
            'instansi_tujuan' => $p->instansi_tujuan,
            'nama_pekerjaan' => $p->nama_pekerjaan,
            'lokasi_pekerjaan' => $p->lokasi_pekerjaan,
            'status_approval' => $p->approval?->status,
            'goal' => (bool) $p->is_goal,
            'diskon' => $p->discount_enabled ? ['tipe' => $p->discount_type, 'nilai' => (float) $p->discount_value] : null,
            'pajak_persen' => $p->tax_enabled ? (float) $p->tax_rate : null,
            'total' => $p->calcGrandTotal(),
            'item' => $p->items->map(fn (PenawaranItem $i) => [
                'judul' => $i->judul,
                'bundle_id' => $i->product_id,
                'qty' => $i->resolvedQty(),
                'satuan' => $i->satuan,
                'markup' => $i->resolvedMarkup(),
                'diskon' => $i->discount_enabled ? ['tipe' => $i->discount_type, 'nilai' => (float) $i->discount_value] : null,
                'subtotal' => $i->calcSubtotal(),
                'rincian' => $i->details->map(fn ($d) => [
                    'nama' => $d->nama,
                    'qty' => $d->resolvedQty(),
                    'satuan' => $d->satuan,
                    'harga_satuan' => $d->calcUnitPrice(),
                ])->all(),
            ])->all(),
        ])->all()]);
    }
}
