<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'notes', 'is_active', 'cost_behavior', 'is_system'])]
class ExpenseCategory extends Model
{
    use HasFactory;

    public const COST_FIXED = 'fixed';
    public const COST_VARIABLE = 'variable';

    /** Nama kategori sistem Fee Guru — dikenali lewat NAMA (bukan id yang hardcoded). */
    public const FEE_GURU_NAME = 'Fee Guru';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExpenseItem::class);
    }

    public function isSystem(): bool
    {
        return (bool) $this->is_system;
    }

    /**
     * Id kategori Fee Guru (dicari via nama). Dipakai guard model & laporan.
     */
    public static function feeGuruId(): ?int
    {
        $id = static::query()->where('name', self::FEE_GURU_NAME)->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * Kategori yang boleh dipilih untuk input manual (aktif & bukan sistem).
     */
    public function scopeSelectable($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('is_system')->orWhere('is_system', false));
    }

    /**
     * Pilihan perilaku biaya untuk dropdown form.
     */
    public static function costBehaviorOptions(): array
    {
        return [
            self::COST_FIXED => 'Tetap (fixed) — mis. sewa, Claude, partner',
            self::COST_VARIABLE => 'Variabel (variable) — mis. fee guru, fotokopi buku',
        ];
    }

    public function costBehaviorLabel(): string
    {
        return match ($this->cost_behavior) {
            self::COST_FIXED => 'Tetap',
            self::COST_VARIABLE => 'Variabel',
            default => 'Belum ditandai',
        };
    }

    public function isFixedCost(): bool
    {
        return $this->cost_behavior === self::COST_FIXED;
    }

    public function isVariableCost(): bool
    {
        return $this->cost_behavior === self::COST_VARIABLE;
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
