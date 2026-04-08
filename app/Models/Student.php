<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'phone', 'email', 'is_active'])]
class Student extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('payment_date');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class)->latest('date');
    }

    public function latestActivePayment(): HasOne
    {
        return $this->hasOne(Payment::class)
            ->ofMany(['payment_date' => 'max', 'id' => 'max'], function ($query) {
                $query->where('remaining_sessions', '>', 0);
            });
    }

    public function getRemainingSessions(): int
    {
        return (int) $this->payments()->sum('remaining_sessions');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function statusLabel(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }
}
