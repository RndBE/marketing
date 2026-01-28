<?php

namespace App\Http\Controllers;

use App\Models\PenawaranTermTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PenawaranTermTemplateController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $data = PenawaranTermTemplate::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('isi', 'like', "%{$q}%")
                    ->orWhere('judul', 'like', "%{$q}%");
            })
            ->orderByRaw('COALESCE(parent_id, 0) asc')
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        $termsByParent = $data->groupBy('parent_id');
        $roots = $termsByParent[null] ?? collect();

        $options = [];
        $walk = function ($parentId, $prefix) use (&$walk, &$options, $termsByParent) {
            $children = $termsByParent[$parentId] ?? collect();
            foreach ($children->sortBy(fn($x) => $x->urutan . '-' . $x->id) as $t) {
                $options[] = [
                    'id' => $t->id,
                    'label' => $prefix . Str::limit(trim((string) $t->isi), 60),
                ];
                $walk($t->id, $prefix . '— ');
            }
        };
        $walk(null, '');

        return view('term_templates.index', compact('data', 'termsByParent', 'roots', 'options', 'q'));
    }

    public function create()
    {
        return redirect()->route('term_templates.index');
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'parent_id' => ['nullable', 'integer'],
            'judul' => ['nullable', 'string', 'max:255'],
            'isi' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'group_name' => ['nullable', 'string', 'max:80'],
        ]);

        return DB::transaction(function () use ($payload) {
            $parentId = $payload['parent_id'] ?? null;

            if ($parentId !== null) {
                $ok = PenawaranTermTemplate::where('id', $parentId)->exists();
                if (!$ok) $parentId = null;
            }

            $urutan = (int) PenawaranTermTemplate::where('parent_id', $parentId)->max('urutan');
            $urutan = $urutan > 0 ? $urutan + 1 : 1;

            PenawaranTermTemplate::create([
                'parent_id' => $parentId,
                'urutan' => $urutan,
                'judul' => $payload['judul'] ?? null,
                'isi' => $payload['isi'],
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'group_name' => $payload['group_name'] ?? null,
            ]);

            return redirect()->route('term_templates.index');
        });
    }

    public function edit(PenawaranTermTemplate $template)
    {
        $all = PenawaranTermTemplate::orderByRaw('COALESCE(parent_id, 0) asc')
            ->orderBy('urutan')->orderBy('id')->get();

        $termsByParent = $all->groupBy('parent_id');

        $options = [];
        $walk = function ($parentId, $prefix) use (&$walk, &$options, $termsByParent, $template) {
            $children = $termsByParent[$parentId] ?? collect();
            foreach ($children->sortBy(fn($x) => $x->urutan . '-' . $x->id) as $t) {
                if ((int) $t->id === (int) $template->id) continue;
                $options[] = [
                    'id' => $t->id,
                    'label' => $prefix . Str::limit(trim((string) $t->isi), 60),
                ];
                $walk($t->id, $prefix . '— ');
            }
        };
        $walk(null, '');

        return view('term_templates.edit', compact('template', 'options'));
    }

    public function update(Request $request, PenawaranTermTemplate $template)
    {
        $payload = $request->validate([
            'parent_id' => ['nullable', 'integer'],
            'urutan' => ['required', 'integer', 'min:1'],
            'judul' => ['nullable', 'string', 'max:255'],
            'isi' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'group_name' => ['nullable', 'string', 'max:80'],
        ]);

        $parentId = $payload['parent_id'] ?? null;

        if ($parentId !== null) {
            if ((int) $parentId === (int) $template->id) $parentId = null;
            $ok = PenawaranTermTemplate::where('id', $parentId)->exists();
            if (!$ok) $parentId = null;
        }

        $template->update([
            'parent_id' => $parentId,
            'urutan' => (int) $payload['urutan'],
            'judul' => $payload['judul'] ?? null,
            'isi' => $payload['isi'],
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'group_name' => $payload['group_name'] ?? null,
        ]);

        return redirect()->route('term_templates.index');
    }

    public function destroy(PenawaranTermTemplate $template)
    {
        $template->delete();
        return redirect()->route('term_templates.index');
    }
}
