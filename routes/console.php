<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mcp:token {email} {--cabut : Cabut semua token MCP milik pengguna ini}', function (string $email) {
    $user = User::where('email', $email)->first();

    if (! $user) {
        $this->error("Pengguna {$email} tidak ditemukan.");

        return 1;
    }

    if ($this->option('cabut')) {
        $this->info($user->tokens()->where('name', 'claude-mcp')->delete().' token dicabut.');

        return 0;
    }

    if (! $user->hasPermission('create-penawaran')) {
        $this->warn("{$user->name} belum punya izin create-penawaran; token tetap dibuat tapi akan ditolak.");
    }

    $token = $user->createToken('claude-mcp')->plainTextToken;

    $this->line('Kirim perintah ini ke '.$user->name.' (token hanya tampil sekali):');
    $this->line('claude mcp add --scope user --transport http crm-penawaran '.url('/mcp/penawaran').' --header "Authorization: Bearer '.$token.'"');
})->purpose('Buat token Claude Code untuk MCP penawaran');
