<?php

namespace App\Console\Commands;

use App\Services\Inventory\BentukHargaModal;
use App\Services\Inventory\HargaModalClient;
use App\Services\Inventory\HargaModalPayload;
use App\Services\Inventory\TabHargaModal;
use Illuminate\Console\Command;

/**
 * Membandingkan bentuk jawaban inventory dengan peta bidang milik CRM.
 *
 * Dipakai ketika sebuah kolom tampil "-" terus-menerus, untuk memisahkan dua
 * kemungkinan yang terlihat sama di layar: nama bidangnya yang belum tercocokkan
 * di CRM, atau inventory memang tidak mengirim bidang itu.
 *
 * Yang dicetak hanya nama bidang dan tipenya. Nilainya tidak pernah ikut, supaya
 * keluaran perintah ini aman disalin ke tiket atau percakapan.
 */
class PeriksaHargaModal extends Command
{
    protected $signature = 'harga-modal:periksa
        {email : Email yang terdaftar di inventory}
        {--tab=produk-jadi : produk-jadi, setengah-jadi, atau bahan}
        {--hanya-tersedia : Ikut mengirim hanya_tersedia=1}
        {--produksi-id= : Periksa endpoint rincian untuk satu batch produksi}';

    protected $description = 'Memeriksa bentuk jawaban inventory dan pemetaan bidangnya di CRM';

    public function handle(HargaModalClient $client): int
    {
        $email = (string) $this->argument('email');
        $diminta = (string) $this->option('tab');
        $tab = TabHargaModal::tryFrom($diminta);

        if ($tab === null) {
            $this->error('Tab "'.$diminta.'" tidak dikenal. Pilihan: produk-jadi, setengah-jadi, bahan.');

            return self::FAILURE;
        }

        $hanyaTersedia = (bool) $this->option('hanya-tersedia');
        $produksiId = trim((string) $this->option('produksi-id'));

        // Dengan --produksi-id, yang diperiksa endpoint rincian. Jawabannya berupa
        // daftar bahan, jadi peta bidang yang dibandingkan adalah peta Bahan.
        $rincian = $produksiId !== '';

        $bentuk = match (true) {
            $rincian => BentukHargaModal::Rincian,
            $tab->berbasisUnit() => BentukHargaModal::Unit,
            default => BentukHargaModal::Bahan,
        };

        $kunciBaris = $rincian ? 'rincian' : $tab->kunciBaris();
        $judul = $rincian ? 'Rincian bahan batch produksi' : $tab->label();

        $this->line('');

        if ($rincian) {
            if (! $tab->berbasisUnit()) {
                $this->error('Rincian hanya ada untuk --tab=produk-jadi atau setengah-jadi.');

                return self::FAILURE;
            }

            $this->info('Memanggil inventory (rincian): '.$email.' | tipe='.$tab->value.' | produksi_id='.$produksiId);
            [$status, $badan, $galat] = $client->mentahRincian($email, $tab, $produksiId);
        } else {
            $this->info('Memanggil inventory: '.$email.' | tab='.$tab->value.($hanyaTersedia ? ' | hanya_tersedia=1' : ''));
            [$status, $badan, $galat] = $client->mentah($email, $tab, $hanyaTersedia);
        }

        $this->line('');
        $this->line('Status HTTP  : '.($status ?? 'tidak ada jawaban'));

        if ($galat !== null) {
            $this->line('Catatan      : '.$galat);
        }

        if (! is_array($badan)) {
            $this->line('');
            $this->error('Tidak ada badan JSON yang bisa diperiksa.');

            return self::FAILURE;
        }

        // Muatan boleh dibungkus `data`; ikuti pembungkusnya seperti yang dilakukan CRM.
        $akar = $badan;

        if (! array_key_exists($kunciBaris, $akar) && isset($akar['data']) && is_array($akar['data'])) {
            $this->line('Pembungkus   : muatan ditemukan di dalam kunci `data`');
            $akar = $akar['data'];
        }

        $this->line('Kunci teratas: '.implode(', ', array_keys($akar)));

        $this->periksaTab($bentuk, $judul, $kunciBaris, $akar);

        $this->line('');
        $this->line('Kolom yang berstatus TIDAK KETEMU perlu satu dari dua tindakan:');
        $this->line('  1. nama bidangnya ditambahkan ke peta di HargaModalPayload, atau');
        $this->line('  2. ditanyakan ke tim inventory kalau bidangnya memang tidak dikirim.');
        $this->line('');

        return self::SUCCESS;
    }

    /** @param  array<string, mixed>  $akar */
    private function periksaTab(BentukHargaModal $bentuk, string $judul, string $kunciBaris, array $akar): void
    {
        $this->line('');
        $this->line(str_repeat('-', 64));
        $this->line($judul.'  ('.$kunciBaris.')');
        $this->line(str_repeat('-', 64));

        $isi = $akar[$kunciBaris] ?? $akar;

        if (! is_array($isi)) {
            $this->warn('  Tidak ada muatan yang bisa dibaca untuk tab ini.');

            return;
        }

        $baris = $isi;

        // Daftar pembungkus disamakan dengan yang dipakai HargaModalPayload.
        foreach (['baris', 'rincian', 'items', 'data'] as $pembungkus) {
            if (isset($isi[$pembungkus]) && is_array($isi[$pembungkus])) {
                $baris = $isi[$pembungkus];
                $this->line('  Baris dibungkus kunci `'.$pembungkus.'`');
                break;
            }
        }

        $this->line('  Jumlah baris: '.count($baris));

        $pertama = null;

        foreach ($baris as $calon) {
            if (is_array($calon)) {
                $pertama = $calon;
                break;
            }
        }

        if ($pertama === null) {
            $this->warn('  Tidak ada baris yang bisa dijadikan contoh.');
        } else {
            $this->line('');
            $this->line('  Bidang pada baris pertama:');

            foreach ($pertama as $kunci => $nilai) {
                $this->line(sprintf('    %-28s %s', $kunci, $this->tipe($nilai)));
            }

            $this->line('');
            $this->line('  Pemetaan kolom CRM:');
            $this->laporkan(HargaModalPayload::diagnosaBaris($pertama, $bentuk));
        }
    }

    /** @param  array<string, ?string>  $peta */
    private function laporkan(array $peta): void
    {
        foreach ($peta as $internal => $cocok) {
            if ($cocok === null) {
                $this->line(sprintf('    <fg=red>%-28s TIDAK KETEMU</>', $internal));

                continue;
            }

            $this->line(sprintf('    <fg=green>%-28s <- %s</>', $internal, $cocok));
        }
    }

    /** Tipe saja, tanpa nilai: isi bidang harga modal tidak boleh bocor ke terminal atau tiket. */
    private function tipe(mixed $nilai): string
    {
        return match (true) {
            is_null($nilai) => 'null',
            is_bool($nilai) => 'bool',
            is_int($nilai) => 'int',
            is_float($nilai) => 'float',
            is_string($nilai) => 'string('.mb_strlen($nilai).' karakter)',
            // Kunci dalamnya ikut disebut -- tanpa nilai -- karena bidang bersarang
            // seperti {id, nama} adalah penyebab paling sering kolom tampil kosong.
            is_array($nilai) => 'array('.count($nilai).' isi'.$this->kunciDalam($nilai).')',
            default => get_debug_type($nilai),
        };
    }

    /** @param  array<mixed>  $nilai */
    private function kunciDalam(array $nilai): string
    {
        if ($nilai === [] || array_is_list($nilai)) {
            return '';
        }

        return ': '.implode(', ', array_slice(array_keys($nilai), 0, 8));
    }
}
