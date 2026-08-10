<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

/**
 * Bentuk data lonceng notifikasi.
 *
 * Dipakai render awal blade dan polling AJAX, jadi kedua jalur itu selalu
 * memakai format yang sama persis.
 */
class DaftarNotifikasi
{
    public const LIMIT = 10;

    /** @return Collection<int, array<string, mixed>> */
    public static function untuk(User $user, int $limit = self::LIMIT): Collection
    {
        return $user->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (DatabaseNotification $item) => [
                'id' => $item->id,
                'judul' => data_get($item->data, 'judul', 'Notifikasi'),
                'pesan' => data_get($item->data, 'pesan', ''),
                'jenis' => data_get($item->data, 'jenis'),
                'url' => route('notifications.open', $item->id),
                'dibaca' => $item->read_at !== null,
                'waktu' => $item->created_at?->diffForHumans(),
            ]);
    }

    public static function belumDibaca(User $user): int
    {
        return $user->unreadNotifications()->count();
    }
}
