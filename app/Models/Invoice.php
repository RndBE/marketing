<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'doc_number_id',
        'user_id',
        'pic_id',
        'parent_id',
        'penawaran_id',
        'judul',
        'catatan',
        'payment_info',
        'tgl_invoice',
        'jatuh_tempo',
        'status',
        'subtotal',
        'tax_percent',
        'tax_amount',
        'discount_amount',
        'grand_total'
    ];

    protected $casts = [
        'tgl_invoice' => 'date',
        'jatuh_tempo' => 'date',
        'tax_percent' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function docNumber(): BelongsTo
    {
        return $this->belongsTo(DocNumber::class);
    }

    public function penawaran(): BelongsTo
    {
        return $this->belongsTo(Penawaran::class);
    }

    public function pic()
    {
        return $this->belongsTo(Pic::class, 'pic_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('urutan');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Invoice::class, 'parent_id');
    }

    public function signature()
    {
        return $this->hasOne(InvoiceSignature::class);
    }

    public function terms()
    {
        return $this->hasMany(InvoiceTerm::class)->orderBy('urutan');
    }

    public function getTerbilangAttribute()
    {
        return $this->terbilang($this->grand_total) . ' Rupiah';
    }

    public static function terbilang($nilai)
    {
        if ($nilai < 0) {
            return "minus " . self::terbilang(abs($nilai));
        }

        $angka = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
        $temp = "";

        if ($nilai < 12) {
            $temp = " " . $angka[$nilai];
        } else if ($nilai < 20) {
            $temp = self::terbilang($nilai - 10) . " Belas";
        } else if ($nilai < 100) {
            $temp = self::terbilang($nilai / 10) . " Puluh" . self::terbilang($nilai % 10);
        } else if ($nilai < 200) {
            $temp = " Seratus" . self::terbilang($nilai - 100);
        } else if ($nilai < 1000) {
            $temp = self::terbilang($nilai / 100) . " Ratus" . self::terbilang($nilai % 100);
        } else if ($nilai < 2000) {
            $temp = " Seribu" . self::terbilang($nilai - 1000);
        } else if ($nilai < 1000000) {
            $temp = self::terbilang($nilai / 1000) . " Ribu" . self::terbilang($nilai % 1000);
        } else if ($nilai < 1000000000) {
            $temp = self::terbilang($nilai / 1000000) . " Juta" . self::terbilang($nilai % 1000000);
        } else if ($nilai < 1000000000000) {
            $temp = self::terbilang($nilai / 1000000000) . " Milyar" . self::terbilang(fmod($nilai, 1000000000));
        } else if ($nilai < 1000000000000000) {
            $temp = self::terbilang($nilai / 1000000000000) . " Trilyun" . self::terbilang(fmod($nilai, 1000000000000));
        }

        return $temp;
    }
}
