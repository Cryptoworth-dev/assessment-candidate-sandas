<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseCsvExportService
{
    public function stream(Builder $query): StreamedResponse
    {
        $filename = 'expenses-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Date', 'Description', 'Category', 'Amount']);

            foreach ($query->cursor() as $expense) {
                fputcsv($handle, [
                    $expense->expense_date->format('Y-m-d'),
                    $expense->description,
                    $expense->category->value,
                    $expense->amount,
                ]);
            }

            fclose($handle);
        }, $filename, $headers);
    }
}