<?php

namespace Database\Seeders;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => 'Demo User', 'password' => Hash::make('password')]
        );

        $samples = [
            ['Weekly groceries',   12500.00, ExpenseCategory::Food,          '2026-08-01'],
            ['Bus season ticket',   3200.00, ExpenseCategory::Transport,     '2026-07-30'],
            ['Monthly room rent',  45000.00, ExpenseCategory::Rent,          '2026-07-28'],
        ];

        foreach ($samples as [$description, $amount, $category, $date]) {
            Expense::create([
                'user_id'      => $user->id,
                'description'  => $description,
                'amount'       => $amount,
                'category'     => $category,
                'expense_date' => $date,
            ]);
        }

        Expense::factory()->count(15)->create(['user_id' => $user->id]);
    }
}