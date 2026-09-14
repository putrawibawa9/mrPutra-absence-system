<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['expense_category_id', 'created_by_user_id', 'teacher_user_id', 'attendance_id', 'attendance_batch_id', 'title', 'amount', 'expense_date', 'notes', 'amortization_group', 'amortization_index', 'amortization_total'])]
class Expense extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'expense_date' => 'date',
            'amortization_index' => 'integer',
            'amortization_total' => 'integer',
        ];
    }

    public function isInstallment(): bool
    {
        return (int) $this->amortization_total > 1;
    }

    public function installmentLabel(): ?string
    {
        return $this->isInstallment()
            ? 'Cicilan '.$this->amortization_index.'/'.$this->amortization_total
            : null;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function attendanceBatch(): BelongsTo
    {
        return $this->belongsTo(AttendanceBatch::class, 'attendance_batch_id');
    }
}
