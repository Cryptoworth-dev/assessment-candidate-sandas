<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Expense;
use App\Enums\ExpenseCategory;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
     public function run(): void
    {
        $samples = [
            ['Weekly groceries',   12500.00, ExpenseCategory::Food,          '2026-08-01'],
            ['Bus season ticket',   3200.00, ExpenseCategory::Transport,     '2026-07-30'],
            ['Monthly room rent',  45000.00, ExpenseCategory::Rent,          '2026-07-28']
        ];

        foreach ($samples as [$description, $amount, $category, $date]) {
            Expense::create([
                'description'  => $description,
                'amount'       => $amount,
                'category'     => $category,
                'expense_date' => $date,
            ]);
        }

        Expense::factory()->count(15)->create();
    }
}
