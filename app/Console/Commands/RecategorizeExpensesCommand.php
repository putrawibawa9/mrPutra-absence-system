<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseCommitment;
use App\Models\ExpenseItem;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecategorizeExpensesCommand extends Command
{
    protected $signature = 'expenses:recategorize {--apply : Terapkan perubahan (default hanya dry-run + CSV)}';

    protected $description = 'Cocokkan expense lama ke item & kategori baru. Default dry-run (CSV di storage/app), --apply untuk menerapkan.';

    /** Override manual per expense id: id => item name. */
    private const ID_TO_ITEM = [
        148 => 'Desain & Cetak Promosi', // Xbanner yang salah masuk Fee Guru
    ];

    /** id yang dipaksa PERLU_REVIEW (jangan ditebak). */
    private const FORCE_REVIEW = [515]; // "ratih trainee" Fee Guru tanpa attendance

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $items = ExpenseItem::query()->with('category')->get();
        if ($items->isEmpty()) {
            $this->error('Belum ada expense_items. Jalankan dulu: php artisan db:seed --class=ExpenseItemSeeder');

            return self::FAILURE;
        }

        $feeGuruId = ExpenseCategory::feeGuruId();
        $feeItem = $items->firstWhere('name', ExpenseCategory::FEE_GURU_NAME);
        $categoriesById = ExpenseCategory::query()->get()->keyBy('id');
        $itemsByName = $items->keyBy('name');

        // Item non-sistem untuk pencocokan keyword.
        $matchable = $items->filter(fn (ExpenseItem $i) => ! $i->category?->isSystem());

        $expenses = Expense::query()->orderBy('id')->get();

        // Pra-hitung: item per amortization_group (harus seragam se-grup).
        $groupItem = [];
        foreach ($expenses->whereNotNull('amortization_group')->groupBy('amortization_group') as $group => $rows) {
            $repr = $this->stripInstallmentSuffix((string) $rows->first()->title);
            $match = $this->matchItem($repr, $matchable);
            $groupItem[$group] = $match; // bisa null
        }

        // Untuk deteksi dobel: tanggal baris bergrup per item.
        $groupDatesByItem = [];
        foreach ($expenses->whereNotNull('amortization_group') as $row) {
            $gi = $groupItem[$row->amortization_group] ?? null;
            if ($gi) {
                $groupDatesByItem[$gi->id][] = Carbon::parse($row->expense_date);
            }
        }

        $rows = [];
        $beforeByCat = [];
        $afterByCat = [];

        foreach ($expenses as $expense) {
            $amount = (int) $expense->amount;
            $oldCatId = (int) $expense->expense_category_id;
            $oldCatName = $categoriesById[$oldCatId]->name ?? ('#'.$oldCatId);

            $result = $this->resolve($expense, $feeGuruId, $feeItem, $matchable, $groupItem, $itemsByName);
            $item = $result['item'];
            $status = $result['status'];
            $note = $result['note'];

            // Deteksi kemungkinan dobel (item fixed, ada baris cicilan item sama dalam ±40 hari).
            if ($item && $item->category?->cost_behavior === ExpenseCategory::COST_FIXED && ! $expense->amortization_group) {
                foreach ($groupDatesByItem[$item->id] ?? [] as $d) {
                    if (abs($d->diffInDays(Carbon::parse($expense->expense_date))) <= 40) {
                        $note = trim($note.' KEMUNGKINAN_DOBEL(vs cicilan)');
                        break;
                    }
                }
            }

            $newCatId = $item ? (int) $item->expense_category_id : $oldCatId;
            $newCatName = $item ? ($item->category?->name ?? '#'.$newCatId) : $oldCatName;

            $beforeByCat[$oldCatName] = ($beforeByCat[$oldCatName] ?? 0) + $amount;
            $afterByCat[$newCatName] = ($afterByCat[$newCatName] ?? 0) + $amount;

            $rows[] = [
                $expense->id,
                $expense->title,
                $amount,
                Carbon::parse($expense->expense_date)->toDateString(),
                $oldCatName,
                $item?->name ?? '',
                $newCatName,
                $status,
                $note,
            ];
        }

        $file = 'recategorize_'.now()->format('Ymd_His').'.csv';
        $this->writeCsv($file, $rows);

        $this->info(($apply ? '[APPLY] ' : '[DRY-RUN] ').'CSV: storage/app/'.$file);
        $this->summary($beforeByCat, $afterByCat);

        if (! $apply) {
            $this->warn('Ini dry-run. Tidak ada data yang diubah. Jalankan ulang dengan --apply untuk menerapkan.');

            return self::SUCCESS;
        }

        $this->applyChanges($expenses, $feeGuruId, $feeItem, $matchable, $groupItem, $itemsByName);
        $this->info('Perubahan diterapkan.');

        return self::SUCCESS;
    }

    /**
     * Tentukan item/kategori & status untuk satu expense (tanpa menulis DB).
     *
     * @return array{item: ?ExpenseItem, status: string, note: string}
     */
    private function resolve(Expense $expense, ?int $feeGuruId, ?ExpenseItem $feeItem, $matchable, array $groupItem, $itemsByName): array
    {
        // Override eksplisit per id.
        if (isset(self::ID_TO_ITEM[$expense->id])) {
            $item = $itemsByName[self::ID_TO_ITEM[$expense->id]] ?? null;

            return ['item' => $item, 'status' => $item ? 'OK_OVERRIDE' : 'PERLU_REVIEW', 'note' => 'override id'];
        }

        if (in_array($expense->id, self::FORCE_REVIEW, true)) {
            return ['item' => null, 'status' => 'PERLU_REVIEW', 'note' => 'ditandai manual'];
        }

        // Kategori Fee Guru.
        if ($feeGuruId !== null && (int) $expense->expense_category_id === $feeGuruId) {
            if ($expense->attendance_id || $expense->attendance_batch_id) {
                return ['item' => $feeItem, 'status' => 'OK_FEE', 'note' => 'fee otomatis (kategori tetap)'];
            }

            return ['item' => null, 'status' => 'PERLU_REVIEW', 'note' => 'Fee Guru tanpa attendance'];
        }

        // Baris cicilan: ikut item grup (seragam).
        if ($expense->amortization_group) {
            $item = $groupItem[$expense->amortization_group] ?? null;

            return ['item' => $item, 'status' => $item ? 'OK_GROUP' : 'PERLU_REVIEW', 'note' => 'cicilan'];
        }

        // Non-fee biasa: cocokkan keyword.
        $item = $this->matchItem((string) $expense->title, $matchable);

        return ['item' => $item, 'status' => $item ? 'OK' : 'PERLU_REVIEW', 'note' => ''];
    }

    private function applyChanges($expenses, ?int $feeGuruId, ?ExpenseItem $feeItem, $matchable, array $groupItem, $itemsByName): void
    {
        DB::transaction(function () use ($expenses, $feeGuruId, $feeItem, $matchable, $groupItem, $itemsByName): void {
            foreach ($expenses as $expense) {
                $result = $this->resolve($expense, $feeGuruId, $feeItem, $matchable, $groupItem, $itemsByName);
                $item = $result['item'];

                if (! $item) {
                    continue; // PERLU_REVIEW: biarkan apa adanya.
                }

                $expense->expense_item_id = $item->id;
                $expense->expense_category_id = $item->expense_category_id;
                $expense->save();
            }

            // Buat komitmen untuk tiap amortization_group yang punya item.
            foreach ($groupItem as $group => $item) {
                if (! $item) {
                    continue;
                }
                if (ExpenseCommitment::query()->where('amortization_group', $group)->exists()) {
                    continue;
                }

                $groupRows = $expenses->where('amortization_group', $group);
                if ($groupRows->isEmpty()) {
                    continue;
                }

                ExpenseCommitment::create([
                    'expense_item_id' => $item->id,
                    'name' => $this->stripInstallmentSuffix((string) $groupRows->first()->title),
                    'total_amount' => (int) $groupRows->sum('amount'),
                    'months' => (int) ($groupRows->max('amortization_total') ?: $groupRows->count()),
                    'start_date' => Carbon::parse($groupRows->min('expense_date'))->toDateString(),
                    'amortization_group' => $group,
                    'notes' => 'Dibuat otomatis dari data cicilan lama.',
                ]);
            }
        });
    }

    private function matchItem(string $title, $matchable): ?ExpenseItem
    {
        $t = mb_strtolower(trim($title));
        if ($t === '') {
            return null;
        }

        $best = null;
        $bestLen = 0;
        foreach ($matchable as $item) {
            foreach ($item->keywordList() as $kw) {
                if ($kw !== '' && str_contains($t, $kw) && strlen($kw) > $bestLen) {
                    $best = $item;
                    $bestLen = strlen($kw);
                }
            }
        }

        return $best;
    }

    private function stripInstallmentSuffix(string $title): string
    {
        return trim(preg_replace('/\s*\(Cicilan\s*\d+\/\d+\)\s*$/i', '', $title)) ?: $title;
    }

    private function writeCsv(string $file, array $rows): void
    {
        $path = storage_path('app/'.$file);
        @mkdir(dirname($path), 0775, true);
        $fh = fopen($path, 'w');
        fputcsv($fh, ['expense_id', 'title', 'amount', 'tanggal', 'kategori_lama', 'item_baru', 'kategori_baru', 'status', 'catatan']);
        foreach ($rows as $row) {
            fputcsv($fh, $row);
        }
        fclose($fh);
    }

    private function summary(array $before, array $after): void
    {
        $names = collect(array_keys($before))->merge(array_keys($after))->unique()->sort()->values();
        $table = $names->map(fn ($name) => [
            $name,
            'Rp '.number_format($before[$name] ?? 0, 0, ',', '.'),
            'Rp '.number_format($after[$name] ?? 0, 0, ',', '.'),
        ])->all();

        $this->line('');
        $this->line('Total rupiah per kategori (sebelum vs sesudah):');
        $this->table(['Kategori', 'Sebelum', 'Sesudah'], $table);
    }
}
