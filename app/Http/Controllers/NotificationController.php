<?php

namespace App\Http\Controllers;

use App\Services\DaftarNotifikasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Dipanggil berkala oleh lonceng di header. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => DaftarNotifikasi::belumDibaca($user),
            'items' => DaftarNotifikasi::untuk($user),
        ]);
    }

    /** Buka notifikasi: tandai terbaca lalu lempar ke dokumennya. */
    public function open(Request $request, string $notification)
    {
        $item = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $item->markAsRead();

        $url = data_get($item->data, 'url');

        return redirect($url ?: route('penawaran-harga.index'));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->expectsJson()) {
            return response()->json([
                'unread_count' => 0,
                'items' => DaftarNotifikasi::untuk($request->user()),
            ]);
        }

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
