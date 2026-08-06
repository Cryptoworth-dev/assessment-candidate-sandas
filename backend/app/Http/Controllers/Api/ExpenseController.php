<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use App\Enums\ExpenseCategory;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Requests\ListExpensesRequest;
use App\Services\ExpenseSummaryService;
use App\Services\ExpenseCsvExportService;
use App\Http\Resources\ExpenseResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\Builder;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseSummaryService $expenseSummaryService,
        private readonly ExpenseCsvExportService $expenseCsvExportService
    )
    {
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(ListExpensesRequest $request): AnonymousResourceCollection
    {
        $expenses = $this->filteredQuery($request)
            ->paginate(perPage: 15)
            ->withQueryString();
            
        return ExpenseResource::collection($expenses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpenseRequest $request): ExpenseResource
    {
        $expense = Expense::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return new ExpenseResource($expense);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Expense $expense): ExpenseResource
    {
        abort_if($expense->user_id !== $request->user()->id, 404);

        return new ExpenseResource($expense);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): ExpenseResource
    {
        abort_if($expense->user_id !== $request->user()->id, 404);

        $data = $request->validated();

        $expense->update($data);

        return new ExpenseResource($expense->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Expense $expense): Response
    {
        abort_if($expense->user_id !== $request->user()->id, 404);

        $expense->delete();

        return response()->noContent();
    }

    public function summary(Request $request): JsonResponse
    {
        $query = Expense::query()->where('user_id', $request->user()->id);
        return response()->json(['data' => $this->expenseSummaryService->summarise($query)]);
    }

    public function export(ListExpensesRequest $request): StreamedResponse
    {
        return $this->expenseCsvExportService->stream($this->filteredQuery($request));
    }

     private function filteredQuery(ListExpensesRequest $request): Builder
    {
        return Expense::query()
        ->where('user_id', $request->user()->id)
            ->when($request->filled('category'), fn ($query) =>
                $query->where('category', $request->string('category'))
            )
            ->when($request->filled('search'), fn ($query) =>
                $query->where('description', 'like', '%' . $request->string('search') . '%')
            )
            ->when($request->filled('from'), fn ($query) =>
                $query->whereDate('expense_date', '>=', $request->date('from'))
            )
            ->when($request->filled('to'), fn ($query) =>
                $query->whereDate('expense_date', '<=', $request->date('to'))
            )
            ->orderByDesc('expense_date')
            ->orderByDesc('id');
    }
}
