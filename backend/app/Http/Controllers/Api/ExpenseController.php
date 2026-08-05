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
use App\Services\ExpenseSummaryService;

class ExpenseController extends Controller
{
    public function __construct(private readonly ExpenseSummaryService $expenseSummaryService)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $expenses = Expense::query()
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get();
        return response()->json(['data' => $expenses]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $expense = Expense::create($data);

        return response()->json(['data' => $expense], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense): JsonResponse
    {
        return response()->json(['data' => $expense]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): JsonResponse
    {
        $data = $request->validated();

        $expense->update($data);

        return response()->json(['data' => $expense]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense): Response
    {
        $expense->delete();

        return response()->noContent();
    }

    public function summary(): JsonResponse
    {
        return response()->json(['data' => $this->expenseSummaryService->summarise()]);
    }
}
