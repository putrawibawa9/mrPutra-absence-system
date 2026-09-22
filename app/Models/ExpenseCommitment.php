<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['expense_item_id', 'name', 'total_amount', 'months', 'start_date', 'amortization_group', 'notes'])]
class ExpenseCommitment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'months' => 'integer',
            'start_date' => 'date',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ExpenseItem::class, 'expense_item_id');
    }

    /**
     * Baris-baris cicilan di tabel expenses yang tergabung dalam komitmen ini.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'amortization_group', 'amortization_group');
    }
}
