<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Siapa saja di sebuah perusahaan yang layak dikabari.
 *
 * Dipisah supaya seluruh modul memakai aturan yang sama: hanya orang yang
 * memang menangani modulnya, dan tidak pernah pelaku aksinya sendiri.
 */
class PenerimaNotifikasi
{
    /**
     * Kalau ternyata tak seorang pun di perusahaan itu punya izin yang diminta,
     * notifikasinya jangan sampai hilang -- jatuhkan ke seluruh user perusahaan.
     *
     * @return Collection<int, User>
     */
    public static function diPerusahaan(?int $companyId, string $permission, ?User $aktor = null): Collection
    {
        if (! $companyId) {
            return collect();
        }

        $users = User::query()
            ->where('company_id', $companyId)
            ->with('roles')
            ->get();

        $berhak = $users->filter(
            fn (User $user) => $user->isSuperadmin() || $user->hasPermission($permission)
        );

        return ($berhak->isNotEmpty() ? $berhak : $users)
            // Pelaku aksinya sudah tahu apa yang baru saja dia lakukan.
            ->reject(fn (User $user) => $aktor && (int) $user->id === (int) $aktor->id)
            ->values();
    }
}
