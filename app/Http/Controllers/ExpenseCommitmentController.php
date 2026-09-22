<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseCommitmentRequest;
use App\Models\Expense;
use App\Models\ExpenseCommitment;
use App\Models\ExpenseItem;
use App\Services\ExpenseCommitmentService;
use Illuminate\Support\Facades\DB;

class ExpenseCommitmentController extends Controller
{
    public function __construct(protected ExpenseCommitmentService $commitments)
    {
    }

    public function index()
    {
        $commitments = ExpenseCommitment::query()
            ->with('item.category')
            ->latest('start_date')
            ->latest('id')
            ->paginate(20);

        return view('expense-commitments.index', compact('commitments'));
    }

    public function create()
    {
        return view('expense-commitments.create', [
            'expenseItems' => ExpenseItem::query()->selectable()->with('category:id,name')->orderBy('name')->get(),
        ]);
    }

    public function store(ExpenseCommitmentRequest $request)
    {
        $item = ExpenseItem::query()->with('category')->findOrFail($request->integer('expense_item_id'));

        $this->commitments->create(
            item: $item,
            name: $request->string('name')->toString(),
            totalAmount: $request->integer('total_amount'),
            months: $request->integer('months'),
            startDate: $request->date('start_date'),
            notes: $request->string('notes')->toString() ?: null,
            actorId: $request->user()->id,
        );

        return redirect()->route('expense-commitments.index')
            ->with('status', 'Komitmen dibuat & '.$request->integer('months').' cicilan bulanan digenerate.');
    }

    public function destroy(ExpenseCommitment $expense_commitment)
    {
        DB::transaction(function () use ($expense_commitment): void {
            Expense::query()->where('amortization_group', $expense_commitment->amortization_group)->delete();
            $expense_commitment->delete();
        });

        return redirect()->route('expense-commitments.index')
            ->with('status', 'Komitmen & seluruh baris cicilannya dihapus.');
    }
}
