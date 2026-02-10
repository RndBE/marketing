<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');

        $data = PurchaseOrder::query()
            ->when($q, function ($query) use ($q) {
                $query->where('nomor_po', 'like', "%{$q}%")
                    ->orWhere('judul', 'like', "%{$q}%")
                    ->orWhere('supplier_nama', 'like', "%{$q}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('purchase_orders.index', compact('data', 'q'));
    }

    public function create()
    {
        return view('purchase_orders.create');
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'nomor_po' => ['nullable', 'string', 'max:50', 'unique:purchase_orders,nomor_po'],
            'judul' => ['required', 'string', 'max:255'],
            'supplier_nama' => ['required', 'string', 'max:255'],
            'supplier_alamat' => ['nullable', 'string'],
            'tgl_po' => ['required', 'date'],
            'status' => ['required', 'in:draft,submitted,approved,cancelled'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string'],
        ]);

        $payload['user_id'] = $request->user()->id;
        $payload['total'] = $payload['total'] ?? 0;

        $po = PurchaseOrder::create($payload);

        if (empty($po->nomor_po)) {
            $po->nomor_po = $this->generateNumber($po);
            $po->save();
        }

        return redirect()->route('purchase-orders.show', $po->id)
            ->with('success', 'Purchase Order berhasil dibuat.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return view('purchase_orders.show', ['po' => $purchaseOrder]);
    }

    private function generateNumber(PurchaseOrder $po): string
    {
        $date = $po->tgl_po?->format('Ymd') ?? now()->format('Ymd');
        return 'PO-' . $date . '-' . str_pad((string) $po->id, 4, '0', STR_PAD_LEFT);
    }
}
