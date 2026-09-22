<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['expense_category_id', 'expense_item_id', 'created_by_user_id', 'teacher_user_id', 'attendance_id', 'attendance_batch_id', 'title', 'amount', 'expense_date', 'notes', 'amortization_group', 'amortization_index', 'amortization_total'])]
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

    protected static function booted(): void
    {
        // Backstop model: expense berkategori Fee Guru HANYA boleh ada bila terkait
        // attendance/batch (dibuat otomatis dari absensi). Mencegah input manual
        // ke Fee Guru dari mana pun (form, command, tinker), bukan hanya form.
        static::saving(function (Expense $expense): void {
            if ($expense->isFeeGuruCategory()
                && ! $expense->attendance_id
                && ! $expense->attendance_batch_id) {
                throw new \RuntimeException(
                    'Expense kategori Fee Guru hanya boleh dibuat otomatis dari absensi (harus terkait attendance).'
                );
            }
        });
    }

    public function isFeeGuruCategory(): bool
    {
        $feeGuruId = ExpenseCategory::feeGuruId();

        return $feeGuruId !== null && (int) $this->expense_category_id === $feeGuruId;
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ExpenseItem::class, 'expense_item_id');
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
