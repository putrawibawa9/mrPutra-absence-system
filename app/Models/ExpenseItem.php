<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['expense_category_id', 'name', 'keywords', 'default_amount', 'is_active'])]
class ExpenseItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'default_amount' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function commitments(): HasMany
    {
        return $this->hasMany(ExpenseCommitment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Item yang boleh dipilih untuk input manual: aktif & kategorinya bukan sistem.
     */
    public function scopeSelectable($query)
    {
        return $query->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('is_system')->orWhere('is_system', false)));
    }

    /**
     * @return array<int, string> daftar keyword (lowercase, trim, tanpa kosong).
     */
    public function keywordList(): array
    {
        return collect(explode(',', (string) $this->keywords))
            ->map(fn ($keyword) => Str::lower(trim($keyword)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
