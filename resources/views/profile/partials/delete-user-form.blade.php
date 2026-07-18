<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete Account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" maxWidth="lg" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" data-delete-theme="permanent-password">
            @csrf
            @method('delete')

            <div class="h-1 bg-rose-500"></div>

            <div class="px-5 pb-5 pt-6 sm:px-6 sm:pb-6">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 ring-1 ring-inset ring-rose-100">
                        <svg aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 6V4.75h6V6m-9 0h12m-10.5 0 .72 12.25h7.56L16.5 6M10 10v4.5m4-4.5v4.5" />
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-rose-600">Tindakan permanen</p>
                        <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">
                            Hapus Akun?
                        </h2>
                    </div>
                </div>

                <div class="mt-5 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3.5">
                    <p class="text-sm leading-6 text-slate-700">
                        Akun beserta seluruh data yang terkait akan dihapus secara permanen. Masukkan kata sandi untuk
                        melanjutkan.
                    </p>
                    <p class="mt-2 text-xs font-medium text-rose-700">
                        Data yang sudah dihapus tidak dapat dipulihkan kembali.
                    </p>
                </div>

                <div class="mt-5">
                    <x-input-label for="password" value="Kata Sandi" class="text-slate-700" />
                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        class="mt-2 block w-full"
                        placeholder="Masukkan kata sandi"
                    />

                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                </div>
            </div>

            <div
                class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <x-secondary-button class="w-full justify-center sm:w-auto" x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>

                <x-danger-button class="w-full justify-center sm:ms-1 sm:w-auto">
                    Hapus Permanen
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
