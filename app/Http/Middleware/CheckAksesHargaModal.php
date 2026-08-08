<?php

namespace App\Http\Middleware;

use App\Services\Inventory\AksesHargaModal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penjaga rute Harga Modal: izin per role sekaligus perusahaan yang diizinkan.
 *
 * Dibuat sendiri alih-alih memakai `permission:view-harga-modal`, supaya syarat
 * perusahaannya tidak hanya berlaku di menu. Menyembunyikan menu saja tidak
 * mengamankan apa pun -- alamatnya tetap bisa diketik langsung.
 */
class CheckAksesHargaModal
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (! AksesHargaModal::boleh($request->user())) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
