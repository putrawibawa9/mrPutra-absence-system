<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tema', 'link', 'is_active'])]
class Game extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceBatches(): HasMany
    {
        return $this->hasMany(AttendanceBatch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Apakah game ini sudah pernah dipakai di sesi mana pun. */
    public function isInUse(): bool
    {
        return $this->attendances()->exists() || $this->attendanceBatches()->exists();
    }
}
