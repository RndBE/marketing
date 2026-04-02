<div class="overflow-x-auto">
    <div class="rounded-xl overflow-hidden border border-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-3 py-2 text-left">Nama</th>
                    <th class="px-3 py-2 text-left">Jabatan</th>
                    <th class="px-3 py-2 text-left">Instansi</th>
                    <th class="px-3 py-2 text-left">Email</th>
                    <th class="px-3 py-2 text-left">HP</th>
                    <th class="px-3 py-2 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($data as $pic)
                    <tr>
                        <td class="px-3 py-2 font-medium">
                            {{ trim(($pic->honorific ? $pic->honorific . ' ' : '') . $pic->nama) }}</td>
                        <td class="px-3 py-2">{{ $pic->jabatan ?: '-' }}</td>
                        <td class="px-3 py-2">{{ $pic->instansi ?: '-' }}</td>
                        <td class="px-3 py-2">{{ $pic->email ?: '-' }}</td>
                        <td class="px-3 py-2">{{ $pic->no_hp ?: '-' }}</td>
                        <td class="px-3 py-2 text-right space-x-2">
                            <a href="{{ route('pics.edit', $pic->id) }}"
                                class="border border-green-600 px-3 py-1 text-green-600 rounded-xl text-sm">Edit</a>

                            <form action="{{ route('pics.destroy', $pic->id) }}" method="POST" class="inline"
                                onsubmit="return confirm('Hapus PIC ini?')">
                                @csrf
                                @method('DELETE')
                                <button
                                    class="border border-red-600 px-3 py-1 text-red-600 rounded-xl text-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-8 text-center text-slate-500">
                            Belum ada data PIC.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($data->hasPages())
    <div class="mt-4">
        {{ $data->links() }}
    </div>
@endif
