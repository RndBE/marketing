<?php

namespace App\Http\Controllers;

use App\Models\AlurPenawaran;
use App\Models\Approval;
use App\Models\ApprovalStep;
use App\Models\DocNumber;
use App\Models\Penawaran;
use App\Models\PenawaranCover;
use App\Models\PenawaranSignature;
use App\Models\PenawaranTerm;
use App\Models\PenawaranTermTemplate;
use App\Models\PenawaranValidity;
use App\Models\Pic;
use App\Models\Prospect;
use App\Models\ProspectUpdate;
use App\Models\UsulanPenawaran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProspectController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $prospects = $this->filteredProspectsQuery($request)
            ->withCount('updates')
            ->withCount('penawarans')
            ->withCount('usulans')
            ->orderByRaw('CASE WHEN next_follow_up_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('next_follow_up_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $statusOptions = Prospect::statusOptions();

        return view('prospects.index', compact('prospects', 'q', 'status', 'statusOptions'));
    }

    public function exportExcel(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $rows = $this->filteredProspectsQuery($request)
            ->withCount('updates')
            ->withCount('penawarans')
            ->withCount('usulans')
            ->orderByRaw('CASE WHEN next_follow_up_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('next_follow_up_at')
            ->orderByDesc('id')
            ->get();

        $headers = [
            'No',
            'Tanggal Lead',
            'Judul Prospek',
            'Instansi',
            'PIC',
            'Jabatan PIC',
            'HP / WA',
            'Email',
            'Lokasi',
            'Sumber Lead',
            'Produk',
            'Kebutuhan',
            'Potensi Nilai (Rp)',
            'Status',
            'Hasil Akhir',
            'Last Follow Up',
            'Next Follow Up',
            'PIC Kantor',
            'Jumlah Progress',
            'Jumlah Usulan',
            'Jumlah Penawaran',
            'Catatan',
            'Dibuat Oleh',
            'Terakhir Update',
        ];

        $exportRows = [];
        $no = 1;

        foreach ($rows as $prospect) {
            $exportRows[] = [
                $no++,
                $prospect->tanggal_lead?->format('d/m/Y') ?? '-',
                $prospect->display_title,
                $prospect->display_instansi,
                $prospect->display_pic_name,
                $prospect->jabatan_pic ?: ($prospect->pic?->jabatan ?? '-'),
                $prospect->display_phone,
                $prospect->display_email,
                $prospect->lokasi ?: '-',
                $prospect->sumber_lead ?: '-',
                $prospect->produk ?: '-',
                $prospect->kebutuhan ?: '-',
                (int) ($prospect->potensi_nilai ?? 0),
                $prospect->status_label,
                $prospect->outcome_label,
                $prospect->last_follow_up_at?->format('d/m/Y') ?? '-',
                $prospect->next_follow_up_at?->format('d/m/Y') ?? '-',
                $prospect->assignedTo?->name ?? '-',
                (int) ($prospect->updates_count ?? 0),
                (int) ($prospect->usulans_count ?? 0),
                (int) ($prospect->penawarans_count ?? 0),
                $prospect->catatan ?: '-',
                $prospect->creator?->name ?? '-',
                $prospect->updated_at?->format('d/m/Y H:i') ?? '-',
            ];
        }

        $filenameParts = ['data-prospek'];
        if ($status !== '') {
            $filenameParts[] = $status;
        }
        if ($q !== '') {
            $filenameParts[] = str($q)->slug('-');
        }

        $filename = implode('-', array_filter($filenameParts)) . '-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($headers, $exportRows) {
            $this->writeXlsx('Data Prospek', $headers, $exportRows, [
                'widths' => [6, 14, 28, 26, 24, 22, 16, 24, 18, 18, 22, 36, 18, 18, 18, 14, 14, 18, 14, 14, 16, 32, 18, 18],
                'wrap_columns' => [2, 3, 4, 5, 8, 10, 11, 21],
                'center_columns' => [0, 18, 19, 20],
                'currency_columns' => [12],
                'freeze_header' => true,
                'auto_filter' => true,
            ]);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function create()
    {
        return view('prospects.create', $this->formData());
    }

    public function store(Request $request)
    {
        $payload = $request->validate($this->rules());
        $payload = $this->normalizePayload($payload);
        $companyId = (int) $this->currentCompanyId($request->user());
        $payload['potensi_nilai'] = (int) ($payload['potensi_nilai'] ?? 0);
        $payload['created_by'] = $request->user()->id;
        $payload['updated_by'] = $request->user()->id;
        $payload['company_id'] = $companyId;

        if (!empty($payload['assigned_to'])) {
            $this->ensureUserBelongsToCompany((int) $payload['assigned_to'], $companyId);
        }

        $prospect = DB::transaction(function () use ($payload, $request) {
            $prospect = Prospect::create($payload);

            ProspectUpdate::create([
                'prospect_id' => $prospect->id,
                'tanggal' => $prospect->tanggal_lead ?? now()->toDateString(),
                'aktivitas' => 'Lead dibuat',
                'status' => $prospect->status,
                'next_follow_up_at' => $prospect->next_follow_up_at,
                'hasil_akhir' => $prospect->hasil_akhir,
                'catatan' => $prospect->catatan,
                'user_id' => $request->user()->id,
            ]);

            return $prospect;
        });

        return redirect()->route('prospects.show', $prospect)
            ->with('success', 'Prospek berhasil ditambahkan.');
    }

    public function show(Request $request, Prospect $prospect)
    {
        $this->ensureViewAccess($request->user(), $prospect);

        $user = $request->user();
        $canViewAllPenawaran = $user->hasPermission('view-all-penawaran');
        $canViewUsulan = $user->hasPermission('view-usulan');
        $companyId = $this->currentCompanyId($user);

        $prospect->load([
            'company',
            'pic',
            'assignedTo',
            'creator',
            'updater',
            'updates.user',
            'updates.attachments',
            'penawarans.docNumber',
            'penawarans.pic',
            'penawarans.approval',
            'penawarans.sharedCompanies',
            'usulans.pic',
            'usulans.creator',
            'usulans.sharedCompanies',
            'usulans.penawaran.docNumber',
        ]);

        $prospect->setRelation(
            'penawarans',
            $prospect->penawarans->filter(fn($penawaran) => $penawaran->isVisibleToCompany($companyId))->values()
        );

        $prospect->setRelation(
            'usulans',
            $prospect->usulans->filter(fn($usulan) => $usulan->isVisibleToCompany($companyId))->values()
        );

        $availablePenawarans = Penawaran::query()
            ->with(['docNumber', 'pic', 'prospect'])
            ->visibleToCompany($companyId)
            ->when(!$canViewAllPenawaran && !$this->isSuperadmin($user), function ($query) use ($user, $companyId) {
                $query->where(function ($nested) use ($user, $companyId) {
                    $nested->where('id_user', $user->id);

                    if ($companyId) {
                        $nested->orWhereHas('sharedCompanies', fn($sharedQuery) => $sharedQuery->where('companies.id', $companyId));
                    }
                });
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $availableUsulans = $canViewUsulan
            ? UsulanPenawaran::query()
                ->with(['pic', 'prospect', 'penawaran.docNumber'])
                ->when($companyId, fn($query) => $query->where('company_id', $companyId))
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get()
            : collect();
        $canCreatePenawaranFromProspect = $user->hasPermission('create-penawaran')
            && ($this->isSuperadmin($user) || (int) $prospect->company_id === (int) $companyId)
            && ($user->hasPermission('view-all-prospect')
                || (int) $prospect->created_by === (int) $user->id
                || (int) $prospect->assigned_to === (int) $user->id);

        return view('prospects.show', [
            'prospect' => $prospect,
            'statusOptions' => Prospect::statusOptions(),
            'outcomeOptions' => Prospect::outcomeOptions(),
            'attachPenawaranOptions' => $availablePenawarans->map(function ($penawaran) use ($prospect) {
                $number = $penawaran->docNumber?->doc_no ?? ('Draft #' . $penawaran->id);
                $label = $number . ' - ' . ($penawaran->judul ?: $penawaran->instansi_tujuan ?: 'Tanpa judul');

                if ($penawaran->prospect_id && (int) $penawaran->prospect_id !== (int) $prospect->id) {
                    $label .= ' [Saat ini di prospek: ' . ($penawaran->prospect?->display_title ?: ('#' . $penawaran->prospect_id)) . ']';
                }

                return [
                    'id' => (string) $penawaran->id,
                    'label' => $label,
                ];
            })->values()->all(),
            'attachUsulanOptions' => $availableUsulans->map(function ($usulan) use ($prospect) {
                $label = $usulan->judul;

                if ($usulan->pic?->instansi) {
                    $label .= ' - ' . $usulan->pic->instansi;
                }

                if ($usulan->prospect_id && (int) $usulan->prospect_id !== (int) $prospect->id) {
                    $label .= ' [Saat ini di prospek: ' . ($usulan->prospect?->display_title ?: ('#' . $usulan->prospect_id)) . ']';
                }

                return [
                    'id' => (string) $usulan->id,
                    'label' => $label,
                ];
            })->values()->all(),
            'canViewUsulan' => $canViewUsulan,
            'canCreatePenawaranFromProspect' => $canCreatePenawaranFromProspect,
        ]);
    }

    public function edit(Request $request, Prospect $prospect)
    {
        $this->ensureEditAccess($request->user(), $prospect);

        return view('prospects.edit', $this->formData($prospect));
    }

    public function update(Request $request, Prospect $prospect)
    {
        $this->ensureEditAccess($request->user(), $prospect);

        $payload = $request->validate($this->rules($prospect));
        $payload = $this->normalizePayload($payload);
        $payload['potensi_nilai'] = (int) ($payload['potensi_nilai'] ?? 0);
        $payload['updated_by'] = $request->user()->id;

        if (!empty($payload['assigned_to'])) {
            $this->ensureUserBelongsToCompany((int) $payload['assigned_to'], $prospect->company_id);
        }

        $prospect->update($payload);

        return redirect()->route('prospects.show', $prospect)
            ->with('success', 'Prospek berhasil diperbarui.');
    }

    public function destroy(Request $request, Prospect $prospect)
    {
        $this->ensureEditAccess($request->user(), $prospect);

        $this->deleteStoredUpdateAttachments($prospect);
        $prospect->delete();

        return redirect()->route('prospects.index')
            ->with('success', 'Prospek berhasil dihapus.');
    }

    public function storeUpdate(Request $request, Prospect $prospect)
    {
        $this->ensureEditAccess($request->user(), $prospect);

        $payload = $request->validate([
            'tanggal' => ['required', 'date'],
            'aktivitas' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(Prospect::statusOptions()))],
            'next_follow_up_at' => ['nullable', 'date'],
            'hasil_akhir' => ['required', Rule::in(array_keys(Prospect::outcomeOptions()))],
            'catatan' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        DB::transaction(function () use ($payload, $prospect, $request) {
            $update = ProspectUpdate::create([
                'prospect_id' => $prospect->id,
                'tanggal' => $payload['tanggal'],
                'aktivitas' => $payload['aktivitas'],
                'status' => $payload['status'],
                'next_follow_up_at' => $payload['next_follow_up_at'] ?? null,
                'hasil_akhir' => $payload['hasil_akhir'],
                'catatan' => $payload['catatan'] ?? null,
                'user_id' => $request->user()->id,
            ]);

            $this->storeUpdateAttachments($request, $update);

            $prospect->update([
                'status' => $payload['status'],
                'last_follow_up_at' => $payload['tanggal'],
                'next_follow_up_at' => $payload['next_follow_up_at'] ?? null,
                'hasil_akhir' => $payload['hasil_akhir'],
                'updated_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('prospects.show', $prospect)
            ->with('success', 'Progress prospek berhasil ditambahkan.');
    }

    public function buatPenawaran(Request $request, Prospect $prospect)
    {
        $this->ensureEditAccess($request->user(), $prospect);

        $penawaran = DB::transaction(function () use ($prospect, $request) {
            $penawaran = $this->createPenawaranFromProspect($prospect);

            ProspectUpdate::create([
                'prospect_id' => $prospect->id,
                'tanggal' => now()->toDateString(),
                'aktivitas' => 'Draft penawaran dibuat dari prospek',
                'status' => $prospect->status,
                'next_follow_up_at' => $prospect->next_follow_up_at,
                'hasil_akhir' => $prospect->hasil_akhir,
                'catatan' => 'Penawaran baru ditambahkan ke prospek ini.',
                'user_id' => $request->user()->id,
            ]);

            return $penawaran;
        });

        return redirect()->route('penawaran.show', $penawaran)
            ->with('success', 'Draft penawaran berhasil dibuat dari prospek.');
    }

    public function attachPenawaran(Request $request, Prospect $prospect)
    {
        $this->ensureEditAccess($request->user(), $prospect);

        $user = $request->user();
        $canViewAllPenawaran = $user->hasPermission('view-all-penawaran');

        $payload = $request->validate([
            'attach_penawaran_id' => ['required', 'exists:penawaran,id'],
        ]);

        $penawaran = Penawaran::query()
            ->visibleToCompany($this->currentCompanyId($user))
            ->when(!$canViewAllPenawaran && !$this->isSuperadmin($user), function ($query) use ($user) {
                $companyId = $this->currentCompanyId($user);

                $query->where(function ($nested) use ($user, $companyId) {
                    $nested->where('id_user', $user->id);

                    if ($companyId) {
                        $nested->orWhereHas('sharedCompanies', fn($sharedQuery) => $sharedQuery->where('companies.id', $companyId));
                    }
                });
            })
            ->findOrFail($payload['attach_penawaran_id']);

        DB::transaction(function () use ($prospect, $penawaran, $user) {
            $oldProspectId = $penawaran->prospect_id;

            if ((int) $oldProspectId === (int) $prospect->id) {
                return;
            }

            $penawaran->update([
                'prospect_id' => $prospect->id,
            ]);

            ProspectUpdate::create([
                'prospect_id' => $prospect->id,
                'tanggal' => now()->toDateString(),
                'aktivitas' => 'Penawaran existing dihubungkan ke prospek',
                'status' => $prospect->status,
                'next_follow_up_at' => $prospect->next_follow_up_at,
                'hasil_akhir' => $prospect->hasil_akhir,
                'catatan' => 'Penawaran ' . ($penawaran->docNumber?->doc_no ?? ('Draft #' . $penawaran->id)) . ' ditambahkan ke grup prospek.',
                'user_id' => $user->id,
            ]);

            if ($oldProspectId && (int) $oldProspectId !== (int) $prospect->id) {
                $oldProspect = Prospect::find($oldProspectId);

                if ($oldProspect) {
                    ProspectUpdate::create([
                        'prospect_id' => $oldProspect->id,
                        'tanggal' => now()->toDateString(),
                        'aktivitas' => 'Penawaran existing dipindahkan ke prospek lain',
                        'status' => $oldProspect->status,
                        'next_follow_up_at' => $oldProspect->next_follow_up_at,
                        'hasil_akhir' => $oldProspect->hasil_akhir,
                        'catatan' => 'Penawaran ' . ($penawaran->docNumber?->doc_no ?? ('Draft #' . $penawaran->id)) . ' dipindahkan ke prospek ' . $prospect->display_title . '.',
                        'user_id' => $user->id,
                    ]);
                }
            }
        });

        return redirect()->route('prospects.show', $prospect)
            ->with('success', 'Penawaran existing berhasil dihubungkan ke prospek.');
    }

    public function attachUsulan(Request $request, Prospect $prospect)
    {
        $this->ensureEditAccess($request->user(), $prospect);

        $user = $request->user();
        if (!$user->hasPermission('view-usulan')) {
            abort(403);
        }

        $payload = $request->validate([
            'attach_usulan_id' => ['required', 'exists:usulan_penawaran,id'],
        ]);

        $usulan = UsulanPenawaran::with(['penawaran.docNumber'])
            ->when($this->currentCompanyId($user), fn($query, $companyId) => $query->where('company_id', $companyId))
            ->findOrFail($payload['attach_usulan_id']);

        DB::transaction(function () use ($prospect, $usulan, $user) {
            $oldProspectId = $usulan->prospect_id;

            if ((int) $oldProspectId !== (int) $prospect->id) {
                $usulan->update([
                    'prospect_id' => $prospect->id,
                ]);

                ProspectUpdate::create([
                    'prospect_id' => $prospect->id,
                    'tanggal' => now()->toDateString(),
                    'aktivitas' => 'Usulan existing dihubungkan ke prospek',
                    'status' => $prospect->status,
                    'next_follow_up_at' => $prospect->next_follow_up_at,
                    'hasil_akhir' => $prospect->hasil_akhir,
                    'catatan' => 'Usulan "' . $usulan->judul . '" ditambahkan ke prospek ini.',
                    'user_id' => $user->id,
                ]);

                if ($oldProspectId) {
                    $oldProspect = Prospect::find($oldProspectId);

                    if ($oldProspect) {
                        ProspectUpdate::create([
                            'prospect_id' => $oldProspect->id,
                            'tanggal' => now()->toDateString(),
                            'aktivitas' => 'Usulan existing dipindahkan ke prospek lain',
                            'status' => $oldProspect->status,
                            'next_follow_up_at' => $oldProspect->next_follow_up_at,
                            'hasil_akhir' => $oldProspect->hasil_akhir,
                            'catatan' => 'Usulan "' . $usulan->judul . '" dipindahkan ke prospek ' . $prospect->display_title . '.',
                            'user_id' => $user->id,
                        ]);
                    }
                }
            }

            if ($usulan->penawaran && (int) $usulan->penawaran->prospect_id !== (int) $prospect->id) {
                $oldPenawaranProspectId = $usulan->penawaran->prospect_id;

                $usulan->penawaran->update([
                    'prospect_id' => $prospect->id,
                ]);

                ProspectUpdate::create([
                    'prospect_id' => $prospect->id,
                    'tanggal' => now()->toDateString(),
                    'aktivitas' => 'Penawaran dari usulan ikut terhubung ke prospek',
                    'status' => $prospect->status,
                    'next_follow_up_at' => $prospect->next_follow_up_at,
                    'hasil_akhir' => $prospect->hasil_akhir,
                    'catatan' => 'Penawaran ' . ($usulan->penawaran->docNumber?->doc_no ?? ('Draft #' . $usulan->penawaran->id)) . ' disinkronkan ke prospek ini karena terhubung dengan usulan "' . $usulan->judul . '".',
                    'user_id' => $user->id,
                ]);

                if ($oldPenawaranProspectId && (int) $oldPenawaranProspectId !== (int) $prospect->id) {
                    $oldPenawaranProspect = Prospect::find($oldPenawaranProspectId);

                    if ($oldPenawaranProspect) {
                        ProspectUpdate::create([
                            'prospect_id' => $oldPenawaranProspect->id,
                            'tanggal' => now()->toDateString(),
                            'aktivitas' => 'Penawaran dari usulan dipindahkan ke prospek lain',
                            'status' => $oldPenawaranProspect->status,
                            'next_follow_up_at' => $oldPenawaranProspect->next_follow_up_at,
                            'hasil_akhir' => $oldPenawaranProspect->hasil_akhir,
                            'catatan' => 'Penawaran ' . ($usulan->penawaran->docNumber?->doc_no ?? ('Draft #' . $usulan->penawaran->id)) . ' dipindahkan ke prospek ' . $prospect->display_title . ' mengikuti usulan "' . $usulan->judul . '".',
                            'user_id' => $user->id,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('prospects.show', $prospect)
            ->with('success', 'Usulan berhasil dihubungkan ke prospek.');
    }

    public function detachPenawaran(Request $request, Prospect $prospect, Penawaran $penawaran)
    {
        $this->ensureEditAccess($request->user(), $prospect);

        if ((int) $penawaran->prospect_id !== (int) $prospect->id) {
            abort(404);
        }

        DB::transaction(function () use ($prospect, $penawaran, $request) {
            $penawaran->update([
                'prospect_id' => null,
            ]);

            ProspectUpdate::create([
                'prospect_id' => $prospect->id,
                'tanggal' => now()->toDateString(),
                'aktivitas' => 'Penawaran dilepas dari prospek',
                'status' => $prospect->status,
                'next_follow_up_at' => $prospect->next_follow_up_at,
                'hasil_akhir' => $prospect->hasil_akhir,
                'catatan' => 'Penawaran ' . ($penawaran->docNumber?->doc_no ?? ('Draft #' . $penawaran->id)) . ' dilepas dari grup prospek ini.',
                'user_id' => $request->user()->id,
            ]);
        });

        return redirect()->route('prospects.show', $prospect)
            ->with('success', 'Penawaran berhasil dilepas dari prospek.');
    }

    public function detachUsulan(Request $request, Prospect $prospect, UsulanPenawaran $usulan)
    {
        $this->ensureEditAccess($request->user(), $prospect);

        if (!$this->isSuperadmin($request->user()) && (int) $usulan->company_id !== (int) $this->currentCompanyId($request->user())) {
            abort(403);
        }

        if ((int) $usulan->prospect_id !== (int) $prospect->id) {
            abort(404);
        }

        DB::transaction(function () use ($prospect, $usulan, $request) {
            $usulan->update([
                'prospect_id' => null,
            ]);

            ProspectUpdate::create([
                'prospect_id' => $prospect->id,
                'tanggal' => now()->toDateString(),
                'aktivitas' => 'Usulan dilepas dari prospek',
                'status' => $prospect->status,
                'next_follow_up_at' => $prospect->next_follow_up_at,
                'hasil_akhir' => $prospect->hasil_akhir,
                'catatan' => 'Usulan "' . $usulan->judul . '" dilepas dari prospek ini.',
                'user_id' => $request->user()->id,
            ]);
        });

        return redirect()->route('prospects.show', $prospect)
            ->with('success', 'Usulan berhasil dilepas dari prospek.');
    }

    private function formData(?Prospect $prospect = null): array
    {
        $pics = Pic::query()
            ->orderBy('instansi')
            ->orderBy('nama')
            ->get([
            'id',
            'honorific',
            'nama',
            'jabatan',
            'instansi',
            'email',
            'no_hp',
        ]);
        $users = $this->companyUsersQuery($prospect?->company_id ?? $this->currentCompanyId())
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'prospect' => $prospect,
            'pics' => $pics,
            'picOptions' => $pics->mapWithKeys(function ($pic) {
                return [
                    $pic->id => [
                        'id' => $pic->id,
                        'nama' => trim(($pic->honorific ? $pic->honorific . ' ' : '') . $pic->nama),
                        'jabatan' => $pic->jabatan,
                        'instansi' => $pic->instansi,
                        'email' => $pic->email,
                        'no_hp' => $pic->no_hp,
                    ],
                ];
            })->all(),
            'picSearchOptions' => $pics->map(function ($pic) {
                $label = trim(($pic->honorific ? $pic->honorific . ' ' : '') . $pic->nama)
                    . ($pic->instansi ? ' - ' . $pic->instansi : '');

                return [
                    'id' => (string) $pic->id,
                    'label' => $label,
                ];
            })->values()->all(),
            'users' => $users,
            'assignedUserOptions' => $users->map(function ($user) {
                return [
                    'id' => (string) $user->id,
                    'label' => $user->name,
                ];
            })->values()->all(),
            'statusOptions' => Prospect::statusOptions(),
            'sourceOptions' => Prospect::sourceOptions(),
            'outcomeOptions' => Prospect::outcomeOptions(),
        ];
    }

    private function filteredProspectsQuery(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        return Prospect::query()
            ->with(['pic', 'assignedTo', 'creator'])
            ->when($status !== '', fn($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('judul', 'like', '%' . $q . '%')
                        ->orWhere('instansi', 'like', '%' . $q . '%')
                        ->orWhere('nama_pic', 'like', '%' . $q . '%')
                        ->orWhere('jabatan_pic', 'like', '%' . $q . '%')
                        ->orWhere('produk', 'like', '%' . $q . '%')
                        ->orWhere('kebutuhan', 'like', '%' . $q . '%')
                        ->orWhere('sumber_lead', 'like', '%' . $q . '%')
                        ->orWhere('catatan', 'like', '%' . $q . '%');
                });
            });
    }

    private function rules(?Prospect $prospect = null): array
    {
        return [
            'tanggal_lead' => ['nullable', 'date'],
            'judul' => ['required', 'string', 'max:255'],
            'instansi' => ['required', 'string', 'max:255'],
            'pic_id' => ['nullable', 'exists:pics,id'],
            'nama_pic' => ['nullable', 'string', 'max:255'],
            'jabatan_pic' => ['nullable', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'sumber_lead' => ['nullable', 'string', 'max:100'],
            'produk' => ['nullable', 'string', 'max:255'],
            'kebutuhan' => ['nullable', 'string'],
            'potensi_nilai' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(array_keys(Prospect::statusOptions()))],
            'last_follow_up_at' => ['nullable', 'date'],
            'next_follow_up_at' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'catatan' => ['nullable', 'string'],
            'hasil_akhir' => ['required', Rule::in(array_keys(Prospect::outcomeOptions()))],
        ];
    }

    private function normalizePayload(array $payload): array
    {
        $picId = $payload['pic_id'] ?? null;
        if (!$picId) {
            return $payload;
        }

        $pic = Pic::query()->find($picId);
        if (!$pic) {
            return $payload;
        }

        $payload['instansi'] = $pic->instansi ?: ($payload['instansi'] ?? null);
        $payload['nama_pic'] = trim(($pic->honorific ? $pic->honorific . ' ' : '') . $pic->nama);
        $payload['jabatan_pic'] = $pic->jabatan;
        $payload['no_hp'] = $pic->no_hp;
        $payload['email'] = $pic->email;

        return $payload;
    }

    private function storeUpdateAttachments(Request $request, ProspectUpdate $update): void
    {
        if (!$request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            if (!$file) {
                continue;
            }

            $path = $file->store('prospects/updates/' . $update->id, 'public');

            $update->attachments()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime' => $file->getMimeType(),
                'size' => (int) $file->getSize(),
            ]);
        }
    }

    private function deleteStoredUpdateAttachments(Prospect $prospect): void
    {
        $prospect->loadMissing('updates.attachments');

        foreach ($prospect->updates as $update) {
            foreach ($update->attachments as $attachment) {
                if (!empty($attachment->file_path)) {
                    Storage::disk('public')->delete($attachment->file_path);
                }
            }
        }
    }

    private function ensureViewAccess($user, Prospect $prospect): void
    {
        $companyId = $this->currentCompanyId($user);

        if (!$this->isSuperadmin($user) && !$prospect->isVisibleToCompany($companyId)) {
            abort(403);
        }
    }

    public function updateVisibility(Request $request, Prospect $prospect)
    {
        abort_unless($this->isSuperadmin($request->user()), 403);

        $prospect->sharedCompanies()->sync([]);

        return back()->with('success', 'Prospek otomatis visible ke semua perusahaan.');
    }

    private function ensureEditAccess($user, Prospect $prospect): void
    {
        $this->ensureCompanyAccess($prospect, 'company_id', $user);

        if ($this->isSuperadmin($user)) {
            return;
        }

        if ($user->hasPermission('view-all-prospect')) {
            return;
        }

        $canAccess = (int) $prospect->created_by === (int) $user->id
            || (int) $prospect->assigned_to === (int) $user->id;

        if (!$canAccess) {
            abort(403);
        }
    }

    private function createPenawaranFromProspect(Prospect $prospect): Penawaran
    {
        $owner = $this->resolveCompanyUser((int) $prospect->company_id, (int) $prospect->created_by);
        $company = $owner->company;
        $docNumber = $this->createDocNumber((int) $prospect->company_id, (int) $owner->id);

        $judul = trim(implode(' - ', array_filter([
            $prospect->judul,
            $prospect->instansi,
            $prospect->produk,
        ])));

        $penawaran = Penawaran::create([
            'company_id' => $prospect->company_id,
            'id_pic' => $prospect->pic_id,
            'id_user' => $owner->id,
            'prospect_id' => $prospect->id,
            'doc_number_id' => $docNumber->id,
            'approval_id' => null,
            'judul' => $judul !== '' ? $judul : ($prospect->judul ?: 'Prospek Baru'),
            'catatan' => $prospect->kebutuhan,
            'instansi_tujuan' => $prospect->instansi,
            'date_created' => now()->timestamp,
            'date_updated' => now()->timestamp,
        ]);

        PenawaranCover::create([
            'penawaran_id' => $penawaran->id,
            'judul_cover' => 'Dokumen Penawaran',
            'subjudul' => $penawaran->judul,
            'perusahaan_nama' => $company?->name ?? 'CV. ARTA SOLUSINDO',
            'perusahaan_alamat' => $company?->address,
            'perusahaan_email' => $company?->email,
            'perusahaan_telp' => $company?->phone,
            'logo_path' => $company?->logo_path,
        ]);

        PenawaranValidity::create([
            'penawaran_id' => $penawaran->id,
            'mulai' => now()->toDateString(),
            'sampai' => now()->addDays(30)->toDateString(),
            'berlaku_hari' => 30,
            'keterangan' => 'Penawaran berlaku 30 hari.',
        ]);

        $templates = PenawaranTermTemplate::query()
            ->whereNull('parent_id')
            ->orderBy('urutan')
            ->orderBy('id')
            ->with(['children'])
            ->get();

        foreach ($templates as $template) {
            $this->cloneTemplateTerm($penawaran->id, $template, null);
        }

        $alur = AlurPenawaran::where('berlaku_untuk', 'penawaran')
            ->where('company_id', $prospect->company_id)
            ->where('status', 'aktif')
            ->with(['langkah' => fn($query) => $query->orderBy('no_langkah')])
            ->first();

        if ($alur && $alur->langkah->isNotEmpty()) {
            $firstStep = $alur->langkah->first()->no_langkah;

            $approval = Approval::create([
                'status' => 'menunggu',
                'current_step' => $firstStep,
                'module' => 'penawaran',
                'ref_id' => $penawaran->id,
            ]);

            foreach ($alur->langkah as $step) {
                $approverId = $step->user_id ?? $penawaran->id_user;

                ApprovalStep::create([
                    'approval_id' => $approval->id,
                    'step_order' => $step->no_langkah,
                    'step_name' => $step->nama_langkah,
                    'user_id' => $step->user_id,
                    'harus_semua' => $step->harus_semua,
                    'status' => 'menunggu',
                    'akses_approve' => [
                        'user_id' => (int) $approverId,
                        'ref_penawaran' => (int) $penawaran->id,
                    ],
                ]);
            }

            $penawaran->update([
                'approval_id' => $approval->id,
            ]);
        }

        $roleNames = $owner->roles->pluck('name')->implode(', ');

        PenawaranSignature::create([
            'penawaran_id' => $penawaran->id,
            'urutan' => 1,
            'nama' => $owner->name,
            'jabatan' => $roleNames ?: 'Staff',
            'kota' => 'Sleman',
            'tanggal' => now()->toDateString(),
            'ttd_path' => $owner->ttd,
        ]);

        return $penawaran;
    }

    private function cloneTemplateTerm(int $penawaranId, $template, ?int $parentId): void
    {
        $newTerm = PenawaranTerm::create([
            'penawaran_id' => $penawaranId,
            'parent_id' => $parentId,
            'urutan' => (int) ($template->urutan ?? 1),
            'judul' => $template->judul,
            'isi' => $template->isi,
        ]);

        foreach ($template->children ?? collect() as $child) {
            $this->cloneTemplateTerm($penawaranId, $child, $newTerm->id);
        }
    }

    private function createDocNumber(?int $companyId = null, ?int $userId = null): DocNumber
    {
        return DB::transaction(function () use ($companyId, $userId) {
            $now = now();
            $month = $now->month;
            $year = $now->year;
            $companyId = $companyId ?: $this->currentCompanyId();
            $company = \App\Models\Company::find($companyId);
            $companyCode = strtoupper((string) ($company?->code ?: 'COMP'));
            $userId = $userId ?: auth()->id();

            $romawi = [
                1 => 'I',
                2 => 'II',
                3 => 'III',
                4 => 'IV',
                5 => 'V',
                6 => 'VI',
                7 => 'VII',
                8 => 'VIII',
                9 => 'IX',
                10 => 'X',
                11 => 'XI',
                12 => 'XII',
            ];

            $last = DocNumber::where('company_id', $companyId)->orderByDesc('seq')->first();
            $seq = $last ? $last->seq + 1 : 1;

            $userCode = 'SPH' . str_pad((string) $userId, 2, '0', STR_PAD_LEFT);
            $docNo = str_pad((string) $seq, 3, '0', STR_PAD_LEFT)
                . "/{$userCode}/{$companyCode}/{$romawi[$month]}/{$year}";

            return DocNumber::create([
                'company_id' => $companyId,
                'prefix' => $userCode,
                'seq' => $seq,
                'month' => $month,
                'year' => $year,
                'doc_no' => $docNo,
            ]);
        });
    }

    private function writeXlsx(string $sheetName, array $headers, array $rows, array $options = []): void
    {
        $strings = [];
        $strIndex = [];
        $widths = $options['widths'] ?? [];
        $wrapColumns = $options['wrap_columns'] ?? [];
        $centerColumns = $options['center_columns'] ?? [];
        $currencyColumns = $options['currency_columns'] ?? [];
        $freezeHeader = (bool) ($options['freeze_header'] ?? false);
        $autoFilter = (bool) ($options['auto_filter'] ?? false);

        $addStr = function (string $val) use (&$strings, &$strIndex): int {
            if (!isset($strIndex[$val])) {
                $strIndex[$val] = count($strings);
                $strings[] = $val;
            }

            return $strIndex[$val];
        };

        $sheetXmlRows = '';

        $sheetXmlRows .= '<row r="1">';
        foreach ($headers as $ci => $header) {
            $col = $this->xlsxColName($ci);
            $si = $addStr((string) $header);
            $sheetXmlRows .= "<c r=\"{$col}1\" s=\"1\" t=\"s\"><v>{$si}</v></c>";
        }
        $sheetXmlRows .= '</row>';

        foreach ($rows as $ri => $row) {
            $rowNum = $ri + 2;
            $sheetXmlRows .= "<row r=\"{$rowNum}\">";
            foreach ($row as $ci => $val) {
                $col = $this->xlsxColName($ci);
                $styleIndex = $this->xlsxCellStyleIndex($ci, $wrapColumns, $centerColumns, $currencyColumns);
                if (is_int($val) || is_float($val)) {
                    $sheetXmlRows .= "<c r=\"{$col}{$rowNum}\" s=\"{$styleIndex}\"><v>{$val}</v></c>";
                    continue;
                }

                $si = $addStr((string) ($val ?? ''));
                $sheetXmlRows .= "<c r=\"{$col}{$rowNum}\" s=\"{$styleIndex}\" t=\"s\"><v>{$si}</v></c>";
            }
            $sheetXmlRows .= '</row>';
        }

        $colsXml = '';
        foreach ($headers as $ci => $header) {
            $width = $widths[$ci] ?? 18;
            $colNum = $ci + 1;
            $colsXml .= '<col min="' . $colNum . '" max="' . $colNum . '" width="' . $width . '" customWidth="1"/>';
        }

        $lastCol = $this->xlsxColName(max(count($headers) - 1, 0));
        $lastRow = max(count($rows) + 1, 1);
        $dimensionRef = 'A1:' . $lastCol . $lastRow;
        $sheetViewsXml = '';
        if ($freezeHeader) {
            $sheetViewsXml = '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A2" sqref="A2"/></sheetView></sheetViews>';
        }
        $autoFilterXml = $autoFilter ? '<autoFilter ref="' . $dimensionRef . '"/>' : '';

        $sharedStringsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($strings) . '" uniqueCount="' . count($strings) . '">';
        foreach ($strings as $string) {
            $sharedStringsXml .= '<si><t xml:space="preserve">' . htmlspecialchars($string, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></si>';
        }
        $sharedStringsXml .= '</sst>';

        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0"/></numFmts>'
            . '<fonts count="2">'
            . '<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1E293B"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FFD1D5DB"/></left>'
            . '<right style="thin"><color rgb="FFD1D5DB"/></right>'
            . '<top style="thin"><color rgb="FFD1D5DB"/></top>'
            . '<bottom style="thin"><color rgb="FFD1D5DB"/></bottom>'
            . '<diagonal/>'
            . '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="6">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="top"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="top"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<dimension ref="' . $dimensionRef . '"/>'
            . $sheetViewsXml
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . '<cols>' . $colsXml . '</cols>'
            . '<sheetData>' . $sheetXmlRows . '</sheetData>'
            . $autoFilterXml
            . '</worksheet>';

        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . htmlspecialchars($sheetName, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsxexp_');
        $zip = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rootRels);
        $zip->addFromString('xl/workbook.xml', $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->addFromString('xl/sharedStrings.xml', $sharedStringsXml);
        $zip->addFromString('xl/styles.xml', $stylesXml);
        $zip->close();

        readfile($tmpFile);
        @unlink($tmpFile);
    }

    private function xlsxCellStyleIndex(int $columnIndex, array $wrapColumns, array $centerColumns, array $currencyColumns): int
    {
        if (in_array($columnIndex, $currencyColumns, true)) {
            return 5;
        }

        if (in_array($columnIndex, $centerColumns, true)) {
            return 4;
        }

        if (in_array($columnIndex, $wrapColumns, true)) {
            return 3;
        }

        return 2;
    }

    private function xlsxColName(int $index): string
    {
        $name = '';
        $index++;

        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $name = chr(65 + $mod) . $name;
            $index = (int) (($index - $mod) / 26);
        }

        return $name;
    }
}
