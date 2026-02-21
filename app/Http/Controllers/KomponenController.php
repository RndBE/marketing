<?php

namespace App\Http\Controllers;

use App\Models\Komponen;
use Illuminate\Http\Request;

class KomponenController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $komponen = Komponen::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('nama', 'like', "%{$q}%")
                    ->orWhere('kode', 'like', "%{$q}%");
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('komponen.index', compact('komponen', 'q'));
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'kode' => 'nullable|string|max:50|unique:komponen,kode',
            'nama' => 'required|string|max:255',
            'spesifikasi' => 'nullable|string',
            'satuan' => 'nullable|string|max:50',
            'harga' => 'required|integer|min:0',
        ]);

        Komponen::create($payload);

        return redirect()->route('komponen.index')->with('success', 'Komponen berhasil ditambahkan');
    }

    public function update(Request $request, Komponen $komponen)
    {
        $payload = $request->validate([
            'kode' => 'nullable|string|max:50|unique:komponen,kode,' . $komponen->id,
            'nama' => 'required|string|max:255',
            'spesifikasi' => 'nullable|string',
            'satuan' => 'nullable|string|max:50',
            'harga' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $payload['is_active'] = (bool) ($payload['is_active'] ?? true);

        $komponen->update($payload);

        return redirect()->route('komponen.index')->with('success', 'Komponen berhasil diupdate');
    }

    public function destroy(Komponen $komponen)
    {
        $komponen->delete();
        return redirect()->route('komponen.index')->with('success', 'Komponen berhasil dihapus');
    }

    // API endpoint untuk get komponen data
    public function show(Komponen $komponen)
    {
        return response()->json([
            'id' => $komponen->id,
            'kode' => $komponen->kode,
            'nama' => $komponen->nama,
            'spesifikasi' => $komponen->spesifikasi,
            'satuan' => $komponen->satuan,
            'harga' => $komponen->harga,
        ]);
    }

    // API endpoint untuk list komponen aktif
    public function list()
    {
        $komponen = Komponen::where('is_active', true)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'kode', 'nama', 'spesifikasi', 'satuan', 'harga']);

        return response()->json($komponen);
    }

    public function bulkImport(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        // Open and read CSV file
        if (($handle = fopen($path, 'r')) !== false) {
            // Skip header row
            $header = fgetcsv($handle);

            $imported = 0;
            $updated = 0;
            $errors = [];

            while (($row = fgetcsv($handle)) !== false) {
                // Skip if all columns are empty
                if (empty(array_filter($row))) {
                    continue;
                }

                // Extract columns based on CSV structure: nama, kode, satuan, harga
                $nama = trim($row[0] ?? '');
                $kode = trim($row[1] ?? '');
                $satuan = trim($row[2] ?? '');
                $hargaStr = trim($row[3] ?? '');

                // Skip if nama is empty
                if (empty($nama)) {
                    continue;
                }

                // Parse harga - remove quotes and thousand separators (commas)
                $hargaStr = str_replace(['"', ','], '', $hargaStr);
                $harga = (int) $hargaStr;

                try {
                    // Check if komponen with this kode already exists
                    if (!empty($kode)) {
                        $existing = Komponen::where('kode', $kode)->first();

                        if ($existing) {
                            // Update existing
                            $existing->update([
                                'nama' => $nama,
                                'satuan' => $satuan ?: null,
                                'harga' => $harga,
                                'is_active' => true,
                            ]);
                            $updated++;
                        } else {
                            // Create new
                            Komponen::create([
                                'kode' => $kode ?: null,
                                'nama' => $nama,
                                'satuan' => $satuan ?: null,
                                'harga' => $harga,
                                'is_active' => true,
                            ]);
                            $imported++;
                        }
                    } else {
                        // No kode, just create
                        Komponen::create([
                            'kode' => null,
                            'nama' => $nama,
                            'satuan' => $satuan ?: null,
                            'harga' => $harga,
                            'is_active' => true,
                        ]);
                        $imported++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Baris '{$nama}': " . $e->getMessage();
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

            return redirect()->route('komponen.index')->with('success', $message);
        }

        return redirect()->route('komponen.index')->with('error', 'Gagal membaca file CSV');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:komponen,id',
        ]);

        $count = Komponen::whereIn('id', $request->ids)->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => "{$count} komponen berhasil dihapus",
                'count' => $count,
            ]);
        }

        return redirect()->route('komponen.index')->with('success', "{$count} komponen berhasil dihapus");
    }
}
