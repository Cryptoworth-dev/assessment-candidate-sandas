<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\ExpenseCategory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description'  => fake()->sentence(3),
            'amount'       => fake()->randomFloat(2, 100, 25000),
            'category'     => fake()->randomElement(ExpenseCategory::cases()),
            'expense_date' => fake()->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
        ];
    }
}
