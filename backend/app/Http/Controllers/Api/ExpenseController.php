<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use App\Enums\ExpenseCategory;

class ExpenseController extends Controller
{
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
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'description'  => ['required', 'string', 'max:255'],
            'amount'       => ['required', 'numeric', 'gt:0'],
            'category'     => ['required', Rule::enum(ExpenseCategory::class)],
            'expense_date' => ['required', 'date_format:Y-m-d'],
        ]);

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
    public function update(Request $request, Expense $expense): JsonResponse
    {
        $data = $request->validate([
            'description'  => ['required', 'string', 'max:255'],
            'amount'       => ['required', 'numeric', 'gt:0'],
            'category'     => ['required', Rule::enum(ExpenseCategory::class)],
            'expense_date' => ['required', 'date_format:Y-m-d'],
        ]);

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
}
