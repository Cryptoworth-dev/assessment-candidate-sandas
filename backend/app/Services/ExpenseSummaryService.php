<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Builder;

class ExpenseSummaryService
{
    public function summarise(?Builder $query = null): array
    {
        $query = $query ?? Expense::query();
        $totalExpenses = (clone $query)->sum('amount');

        $byCategory = (clone $query)
            ->selectRaw('category, SUM(amount) as category_total')
            ->groupBy('category')
            ->orderByDesc('category_total')
            ->get()
            ->map(fn (Expense $row) => [
                'category' => $row->category->value,
                'category_total' => $this->money($row->category_total),
            ])
            ->all();

        return [
            'total' => $this->money($totalExpenses),
            'by_category' => $byCategory,
        ];
    }

    private function money(int|float|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}