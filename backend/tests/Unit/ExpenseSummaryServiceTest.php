<?php

namespace Tests\Unit;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\User;
use App\Services\ExpenseSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExpenseSummaryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExpenseSummaryService();
    }

    public function test_summarise_returns_empty_summary_with_no_expenses(): void
    {
        $result = $this->service->summarise();

        $this->assertSame('0.00', $result['total']);
        $this->assertEmpty($result['by_category']);
    }

    public function test_summarise_calculates_total_correctly(): void
    {
        $user = User::factory()->create();

        Expense::factory()
            ->for($user)
            ->create(['amount' => 1000.50, 'category' => ExpenseCategory::Food]);
        Expense::factory()
            ->for($user)
            ->create(['amount' => 500.25, 'category' => ExpenseCategory::Transport]);
        Expense::factory()
            ->for($user)
            ->create(['amount' => 250.75, 'category' => ExpenseCategory::Rent]);

        $result = $this->service->summarise();

        $this->assertSame('1751.50', $result['total']);
    }

    public function test_summarise_groups_by_category(): void
    {
        $user = User::factory()->create();

        Expense::factory(3)->for($user)->create(['category' => ExpenseCategory::Food, 'amount' => 100]);
        Expense::factory(2)->for($user)->create(['category' => ExpenseCategory::Transport, 'amount' => 50]);
        Expense::factory(1)->for($user)->create(['category' => ExpenseCategory::Rent, 'amount' => 500]);

        $result = $this->service->summarise();

        $this->assertCount(3, $result['by_category']);
        $categories = collect($result['by_category'])->pluck('category')->toArray();
        $this->assertContains('Food', $categories);
        $this->assertContains('Transport', $categories);
        $this->assertContains('Rent', $categories);
    }

    public function test_summarise_orders_categories_by_total_descending(): void
    {
        $user = User::factory()->create();

        Expense::factory(1)->for($user)->create(['category' => ExpenseCategory::Food, 'amount' => 100]);
        Expense::factory(1)->for($user)->create(['category' => ExpenseCategory::Transport, 'amount' => 300]);
        Expense::factory(1)->for($user)->create(['category' => ExpenseCategory::Rent, 'amount' => 200]);

        $result = $this->service->summarise();

        $totals = collect($result['by_category'])->pluck('total')->map('floatval')->toArray();
        $this->assertSame([300.0, 200.0, 100.0], $totals);
    }

    public function test_summarise_formats_money_to_two_decimal_places(): void
    {
        $user = User::factory()->create();

        Expense::factory()
            ->for($user)
            ->create(['amount' => 1000.1, 'category' => ExpenseCategory::Food]);
        Expense::factory()
            ->for($user)
            ->create(['amount' => 500.9, 'category' => ExpenseCategory::Transport]);

        $result = $this->service->summarise();

        $this->assertSame('1501.00', $result['total']);
        $this->assertMatchesRegularExpression('/\d+\.\d{2}/', $result['by_category'][0]['total']);
        $this->assertMatchesRegularExpression('/\d+\.\d{2}/', $result['by_category'][1]['total']);
    }

    public function test_summarise_with_user_filtered_query(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Expense::factory(5)->for($user1)->create(['amount' => 100, 'category' => ExpenseCategory::Food]);
        Expense::factory(3)->for($user2)->create(['amount' => 200, 'category' => ExpenseCategory::Food]);

        $query = Expense::where('user_id', $user1->id);
        $result = $this->service->summarise($query);

        $this->assertSame('500.00', $result['total']);
        $this->assertCount(1, $result['by_category']);
        $this->assertSame('500.00', $result['by_category'][0]['total']);
    }

    public function test_summarise_handles_zero_amount_expenses(): void
    {
        $user = User::factory()->create();

        Expense::factory()
            ->for($user)
            ->create(['amount' => 0, 'category' => ExpenseCategory::Food]);
        Expense::factory()
            ->for($user)
            ->create(['amount' => 100, 'category' => ExpenseCategory::Transport]);

        $result = $this->service->summarise();

        $this->assertSame('100.00', $result['total']);
    }

    public function test_summarise_preserves_decimal_precision(): void
    {
        $user = User::factory()->create();

        Expense::factory()
            ->for($user)
            ->create(['amount' => 99.99, 'category' => ExpenseCategory::Food]);
        Expense::factory()
            ->for($user)
            ->create(['amount' => 100.01, 'category' => ExpenseCategory::Food]);

        $result = $this->service->summarise();

        $this->assertSame('200.00', $result['total']);
    }

    public function test_summarise_with_large_numbers(): void
    {
        $user = User::factory()->create();

        Expense::factory()
            ->for($user)
            ->create(['amount' => 9999999999.99, 'category' => ExpenseCategory::Rent]);

        $result = $this->service->summarise();

        $this->assertSame('9999999999.99', $result['total']);
    }
}
