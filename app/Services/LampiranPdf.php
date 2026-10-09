<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Menempelkan berkas unggahan (PDF atau gambar) di belakang PDF yang dibuat sistem.
 *
 * Lampiran yang gagal dibaca dilewati saja: dokumen utama tetap harus bisa diunduh.
 */
class LampiranPdf
{
    private const GAMBAR = ['jpg', 'jpeg', 'png'];

    /**
     * @param  array<int, string>  $berkas  path lengkap berkas lampiran, berurutan
     */
    public function gabung(string $pdfUtama, array $berkas): string
    {
        $berkas = array_values(array_filter($berkas, 'is_file'));
        if (! $berkas) {
            return $pdfUtama;
        }

        $sementara = [];

        try {
            $utama = $this->tulisSementara($pdfUtama, $sementara);
            $gabungan = new Fpdi;
            $this->salinHalaman($gabungan, $utama);

            foreach ($berkas as $path) {
                try {
                    $this->salinHalaman($gabungan, $this->jadikanPdf($path, $sementara));
                } catch (\Throwable $e) {
                    Log::warning('Lampiran gagal digabung ke PDF', ['file_path' => $path, 'error' => $e->getMessage()]);
                }
            }

            return $gabungan->Output('S');
        } catch (\Throwable $e) {
            report($e);

            return $pdfUtama;
        } finally {
            foreach ($sementara as $path) {
                @unlink($path);
            }
        }
    }

    private function salinHalaman(Fpdi $tujuan, string $pdf): void
    {
        $jumlah = $tujuan->setSourceFile($pdf);

        // Impor dulu semua halaman, baru ditambahkan: lampiran yang rusak di tengah
        // jalan tidak meninggalkan halaman setengah jadi di dokumen utama.
        $halaman = [];
        for ($nomor = 1; $nomor <= $jumlah; $nomor++) {
            $templat = $tujuan->importPage($nomor);
            $halaman[] = [$templat, $tujuan->getTemplateSize($templat)];
        }

        foreach ($halaman as [$templat, $ukuran]) {
            $tujuan->AddPage($ukuran['orientation'], [$ukuran['width'], $ukuran['height']]);
            $tujuan->useTemplate($templat);
        }
    }

    /**
     * PDF dipakai apa adanya; gambar dicetak dulu ke satu halaman A4.
     *
     * @param  array<int, string>  $sementara
     */
    private function jadikanPdf(string $path, array &$sementara): string
    {
        $ekstensi = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ekstensi, self::GAMBAR, true)) {
            $mime = $ekstensi === 'png' ? 'image/png' : 'image/jpeg';
            $uri = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
            $html = '<html><head><style>@page{margin:12mm}body{margin:0;text-align:center}'
                .'img{max-width:100%;max-height:265mm}</style></head><body><img src="'.$uri.'"></body></html>';

            return $this->tulisSementara(Pdf::loadHTML($html)->setPaper('a4', 'portrait')->output(), $sementara);
        }

        try {
            (new Fpdi)->setSourceFile($path);

            return $path;
        } catch (\Throwable $e) {
            // Parser FPDI gratis tidak bisa membaca PDF 1.5+ yang terkompresi; ratakan
            // dulu ke PDF 1.4 bila qpdf atau Ghostscript tersedia.
            $rata = $this->ratakan($path, $sementara);
            if ($rata === null) {
                throw $e;
            }

            return $rata;
        }
    }

    /**
     * @param  array<int, string>  $sementara
     */
    private function ratakan(string $sumber, array &$sementara): ?string
    {
        $tujuan = $this->tulisSementara('', $sementara);
        $finder = new ExecutableFinder;
        $perintah = [];

        if ($qpdf = $finder->find('qpdf')) {
            $perintah[] = [$qpdf, '--object-streams=disable', $sumber, $tujuan];
        }
        foreach (['gs', 'gswin64c', 'gswin32c'] as $nama) {
            if ($gs = $finder->find($nama)) {
                $perintah[] = [$gs, '-q', '-dNOPAUSE', '-dBATCH', '-dSAFER', '-sDEVICE=pdfwrite',
                    '-dCompatibilityLevel=1.4', '-sOutputFile='.$tujuan, $sumber];
            }
        }

        foreach ($perintah as $argumen) {
            try {
                $proses = new Process($argumen);
                $proses->setTimeout(30);
                $proses->run();
            } catch (\Throwable) {
                continue;
            }

            if ($proses->isSuccessful() && filesize($tujuan) > 0) {
                return $tujuan;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $sementara
     */
    private function tulisSementara(string $isi, array &$sementara): string
    {
        $path = tempnam(sys_get_temp_dir(), 'lampiran_');
        $sementara[] = $path;
        file_put_contents($path, $isi);

        return $path;
    }
}
