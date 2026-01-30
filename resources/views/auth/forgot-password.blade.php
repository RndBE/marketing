<x-guest-layout>
    <h2 class="text-xl font-semibold text-center mb-4">Lupa Password</h2>

    <div class="mb-4 text-sm text-slate-600">
        Lupa password? Tidak masalah. Masukkan alamat email Anda dan kami akan mengirimkan link untuk reset password.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-semibold text-slate-700 mb-1">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6">
            <button type="submit"
                class="w-full rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                Kirim Link Reset Password
            </button>
        </div>

        <div class="mt-6 text-center text-sm text-slate-600">
            Ingat password?
            <a href="{{ route('login') }}" class="font-semibold text-slate-900 hover:underline">Login</a>
        </div>
    </form>
</x-guest-layout>