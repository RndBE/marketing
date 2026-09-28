<?php

namespace App\Mcp\Tools;

use App\Models\Pic;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Cari PIC (kontak pelanggan) berdasarkan nama, jabatan, atau instansi. Pakai `id`-nya sebagai `id_pic` di `buat-penawaran`.')]
#[IsReadOnly]
class CariPic extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'q' => $schema->string()->description('Nama orang atau instansi, mis. "BBWS Serayu".')->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate(['q' => ['required', 'string', 'max:100']]);

        $query = Pic::query();

        foreach (preg_split('/\s+/', trim($data['q']), -1, PREG_SPLIT_NO_EMPTY) as $kata) {
            $like = "%{$kata}%";
            $query->where(fn ($q) => $q->where('nama', 'like', $like)
                ->orWhere('jabatan', 'like', $like)
                ->orWhere('instansi', 'like', $like));
        }

        // Kontak pribadi (email, no. HP, alamat) sengaja tidak dikirim; untuk
        // menyusun penawaran cukup tahu siapa dan dari instansi mana.
        $pic = $query->orderBy('nama')->limit(10)->get(['id', 'honorific', 'nama', 'jabatan', 'instansi']);

        return Response::structured(['pic' => $pic->toArray()]);
    }
}
