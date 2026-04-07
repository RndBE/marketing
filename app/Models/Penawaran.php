<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penawaran extends Model
{
    protected $table = 'penawaran';

    protected $fillable = [
        'id_pic',
        'id_user',
        'prospect_id',
        'doc_number_id',
        'approval_id',
        'judul',
        'catatan',
        'instansi_tujuan',
        'nama_pekerjaan',
        'lokasi_pekerjaan',
        'tanggal_penawaran',
        'date_created',
        'date_updated',
        'discount_enabled',
        'discount_type',
        'discount_value',
        'tax_enabled',
        'tax_rate',
        'is_goal',
        'goal_at',
    ];
    protected $casts = [
        'discount_enabled' => 'boolean',
        'tax_enabled' => 'boolean',
        'discount_value' => 'decimal:2',
        'tanggal_penawaran' => 'date',
        'tax_rate' => 'decimal:2',
        'is_goal' => 'boolean',
        'goal_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
    public function pic()
    {
        return $this->belongsTo(Pic::class, 'id_pic');
    }

    public function docNumber()
    {
        return $this->belongsTo(DocNumber::class, 'doc_number_id');
    }

    public function approval()
    {
        return $this->belongsTo(Approval::class, 'approval_id');
    }

    public function cover()
    {
        return $this->hasOne(PenawaranCover::class, 'penawaran_id');
    }

    public function validity()
    {
        return $this->hasOne(PenawaranValidity::class, 'penawaran_id');
    }

    public function items()
    {
        return $this->hasMany(PenawaranItem::class, 'penawaran_id')->orderBy('urutan');
    }

    public function terms()
    {
        return $this->hasMany(PenawaranTerm::class, 'penawaran_id')->orderBy('urutan')->orderBy('id');
    }

    public function termRoots()
    {
        return $this->hasMany(PenawaranTerm::class, 'penawaran_id')->whereNull('parent_id')->orderBy('urutan')->orderBy('id');
    }


    public function signatures()
    {
        return $this->hasMany(PenawaranSignature::class, 'penawaran_id')->orderBy('urutan');
    }

    public function attachments()
    {
        return $this->hasMany(PenawaranAttachment::class, 'penawaran_id')->orderBy('urutan');
    }

    public function penghapusan()
    {
        return $this->hasMany(PenghapusanPenawaran::class);
    }

    public function usulan()
    {
        return $this->hasOne(UsulanPenawaran::class, 'penawaran_id');
    }

    public function prospect()
    {
        return $this->belongsTo(Prospect::class, 'prospect_id');
    }

    public function calcItemsSubtotal(): int
    {
        return (int) $this->items->sum(fn($item) => $item->calcSubtotal());
    }

    public function calcDiscountAmount(): int
    {
        if (!$this->discount_enabled) {
            return 0;
        }

        $subtotal = $this->calcItemsSubtotal();
        $value = (float) ($this->discount_value ?? 0);
        $discount = ($this->discount_type ?? 'percent') === 'percent'
            ? (int) round($subtotal * ($value / 100))
            : (int) round($value);

        return min($discount, $subtotal);
    }

    public function calcDppTotal(): int
    {
        return $this->calcItemsSubtotal() - $this->calcDiscountAmount();
    }

    public function calcTaxAmount(): int
    {
        if (!$this->tax_enabled) {
            return 0;
        }

        return (int) round($this->calcDppTotal() * ((float) ($this->tax_rate ?? 11) / 100));
    }

    public function calcGrandTotal(): int
    {
        return $this->calcDppTotal() + $this->calcTaxAmount();
    }
}
