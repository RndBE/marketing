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
        'stamp_path',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function logoFullPath(): ?string
    {
        return $this->publicDiskFullPath($this->logo_path);
    }

    public function stampFullPath(): ?string
    {
        return $this->publicDiskFullPath($this->stamp_path);
    }

    private function publicDiskFullPath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $publicPath = public_path('storage/' . ltrim($path, '/'));
        if (is_file($publicPath)) {
            return $publicPath;
        }

        $storagePath = storage_path('app/public/' . ltrim($path, '/'));
        if (is_file($storagePath)) {
            return $storagePath;
        }

        return null;
    }
}
