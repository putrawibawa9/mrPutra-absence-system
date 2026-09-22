<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCommitment;
use App\Models\ExpenseItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExpenseCommitmentService
{
    /**
     * Buat komitmen baru + generate seluruh baris cicilan di tabel expenses.
     * Selisih pembulatan ditaruh di cicilan TERAKHIR. Item & kategori sama untuk
     * semua baris (kategori diturunkan dari item). Semua dalam satu transaksi.
     */
    public function create(
        ExpenseItem $item,
        string $name,
        int $totalAmount,
        int $months,
        Carbon $startDate,
        ?string $notes = null,
        ?int $actorId = null,
        ?string $group = null,
    ): ExpenseCommitment {
        $months = max(2, $months);
        $group ??= (string) Str::uuid();
        $per = intdiv($totalAmount, $months);
        $remainder = $totalAmount - ($per * $months); // ditaruh di cicilan terakhir

        return DB::transaction(function () use ($item, $name, $totalAmount, $months, $startDate, $notes, $actorId, $group, $per, $remainder): ExpenseCommitment {
            $commitment = ExpenseCommitment::create([
                'expense_item_id' => $item->id,
                'name' => $name,
                'total_amount' => $totalAmount,
                'months' => $months,
                'start_date' => $startDate->toDateString(),
                'amortization_group' => $group,
                'notes' => $notes,
            ]);

            for ($i = 0; $i < $months; $i++) {
                Expense::create([
                    'expense_category_id' => $item->expense_category_id,
                    'expense_item_id' => $item->id,
                    'created_by_user_id' => $actorId,
                    'title' => $name.' (Cicilan '.($i + 1).'/'.$months.')',
                    'amount' => $per + ($i === $months - 1 ? $remainder : 0),
                    'expense_date' => $startDate->copy()->addMonthsNoOverflow($i)->toDateString(),
                    'amortization_group' => $group,
                    'amortization_index' => $i + 1,
                    'amortization_total' => $months,
                ]);
            }

            return $commitment;
        });
    }
}
