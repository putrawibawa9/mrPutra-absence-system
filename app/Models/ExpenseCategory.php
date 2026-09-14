<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'notes', 'is_active', 'cost_behavior'])]
class ExpenseCategory extends Model
{
    use HasFactory;

    public const COST_FIXED = 'fixed';
    public const COST_VARIABLE = 'variable';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
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
