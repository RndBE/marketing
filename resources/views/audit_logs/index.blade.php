@extends('layouts.app', ['title' => 'Audit Log'])

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold">Audit Log</h1>
            <div class="text-sm text-slate-500">Menampilkan aktivitas mutasi data di aplikasi</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <form method="GET" action="{{ route('audit-logs.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-6">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Cari</label>
                    <input type="text" name="q" value="{{ $q }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                        placeholder="Aksi, route, URL, user, IP">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Action</label>
                    <input type="text" name="action" value="{{ $action }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                        placeholder="contoh: auth.login">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Method</label>
                    <select name="method" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Semua</option>
                        @foreach ($methods as $m)
                            <option value="{{ $m }}" @selected($method === $m)>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">User</label>
                    <select name="user_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Semua</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) $userId === (string) $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Status Code</label>
                    <input type="number" name="status_code" value="{{ $statusCode }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" min="100" max="599"
                        placeholder="200">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Tanggal Dari</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Tanggal Sampai</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>

                <div class="md:col-span-6 flex items-center justify-end gap-2">
                    <a href="{{ route('audit-logs.index') }}"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Reset
                    </a>
                    <button type="submit"
                        class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Waktu</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">User</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Action</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Route</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Method</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">IP</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">URL</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Payload</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($logs as $log)
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-700">
                                    {{ optional($log->created_at)->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($log->user)
                                        <div class="font-medium text-slate-800">{{ $log->user->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $log->user->email }}</div>
                                    @else
                                        <span class="text-slate-500">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $log->route_name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-md px-2 py-1 text-xs font-semibold
                                        {{ $log->method === 'DELETE'
                                            ? 'bg-rose-100 text-rose-700'
                                            : ($log->method === 'PATCH'
                                                ? 'bg-amber-100 text-amber-700'
                                                : ($log->method === 'PUT'
                                                    ? 'bg-indigo-100 text-indigo-700'
                                                    : 'bg-emerald-100 text-emerald-700')) }}">
                                        {{ $log->method }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $log->status_code ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $log->ip_address ?? '-' }}</td>
                                <td class="max-w-xs px-4 py-3 text-slate-600">
                                    <div class="truncate" title="{{ $log->url }}">{{ $log->url }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @if (!empty($log->payload))
                                        <details>
                                            <summary class="cursor-pointer text-xs font-semibold text-slate-700">Lihat</summary>
                                            <pre
                                                class="mt-2 max-h-48 overflow-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                    @else
                                        <span class="text-slate-500">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-slate-500">Belum ada data audit log.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $logs->links() }}</div>
    </div>
@endsection
