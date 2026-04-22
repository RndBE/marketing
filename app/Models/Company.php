<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'code',
        'name',
        'address',
        'email',
        'phone',
        'logo_path',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function logoFullPath(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        $publicPath = public_path('storage/' . ltrim($this->logo_path, '/'));
        if (is_file($publicPath)) {
            return $publicPath;
        }

        $storagePath = storage_path('app/public/' . ltrim($this->logo_path, '/'));
        if (is_file($storagePath)) {
            return $storagePath;
        }

        return null;
    }
}
