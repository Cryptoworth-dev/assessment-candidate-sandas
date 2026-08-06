<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_user_cannot_list_expenses(): void
    {
        $response = $this->getJson('/api/expenses');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_own_expenses(): void
    {
        $expense = Expense::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)->getJson('/api/expenses');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $expense->id)
            ->assertJsonPath('data.0.description', $expense->description);
    }

    public function test_user_cannot_see_other_users_expenses(): void
    {
        $otherUser = User::factory()->create();
        Expense::factory()->for($otherUser)->create(['description' => 'Secret expense']);

        $response = $this->actingAs($this->user)->getJson('/api/expenses');

        $response->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_create_expense_requires_authentication(): void
    {
        $response = $this->postJson('/api/expenses', [
            'description' => 'Groceries',
            'amount' => 50,
            'category' => 'Food',
            'expense_date' => '2026-08-01',
        ]);

        $response->assertUnauthorized();
    }

    public function test_create_expense_with_valid_data(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/expenses', [
            'description' => 'Weekly groceries',
            'amount' => 12500,
            'category' => 'Food',
            'expense_date' => '2026-08-01',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.description', 'Weekly groceries')
            ->assertJsonPath('data.amount', '12500.00')
            ->assertJsonPath('data.category', 'Food');

        $this->assertDatabaseHas('expenses', [
            'description' => 'Weekly groceries',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_create_expense_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/expenses', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['description', 'amount', 'category', 'expense_date']);
    }

    public function test_create_expense_validates_description_max_length(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/expenses', [
            'description' => str_repeat('a', 256),
            'amount' => 50,
            'category' => 'Food',
            'expense_date' => '2026-08-01',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);
    }

    public function test_create_expense_validates_amount_greater_than_zero(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/expenses', [
            'description' => 'Test',
            'amount' => 0,
            'category' => 'Food',
            'expense_date' => '2026-08-01',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_create_expense_validates_amount_max_value(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/expenses', [
            'description' => 'Test',
            'amount' => 9999999999.99 + 0.01,
            'category' => 'Food',
            'expense_date' => '2026-08-01',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_create_expense_validates_invalid_category(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/expenses', [
            'description' => 'Test',
            'amount' => 50,
            'category' => 'InvalidCategory',
            'expense_date' => '2026-08-01',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['category']);
    }

    public function test_create_expense_validates_expense_date_format(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/expenses', [
            'description' => 'Test',
            'amount' => 50,
            'category' => 'Food',
            'expense_date' => '08-01-2026',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['expense_date']);
    }

    public function test_create_expense_rejects_future_dates(): void
    {
        $futureDate = now()->addDay()->format('Y-m-d');

        $response = $this->actingAs($this->user)->postJson('/api/expenses', [
            'description' => 'Test',
            'amount' => 50,
            'category' => 'Food',
            'expense_date' => $futureDate,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['expense_date']);
    }

    public function test_get_expense_requires_authentication(): void
    {
        $expense = Expense::factory()->for($this->user)->create();

        $response = $this->getJson("/api/expenses/{$expense->id}");

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_own_expense(): void
    {
        $expense = Expense::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)->getJson("/api/expenses/{$expense->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $expense->id)
            ->assertJsonPath('data.description', $expense->description);
    }

    public function test_user_cannot_view_other_users_expense(): void
    {
        $otherUser = User::factory()->create();
        $expense = Expense::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user)->getJson("/api/expenses/{$expense->id}");

        $response->assertNotFound();
    }

    public function test_update_expense_requires_authentication(): void
    {
        $expense = Expense::factory()->for($this->user)->create();

        $response = $this->putJson("/api/expenses/{$expense->id}", [
            'description' => 'Updated',
            'amount' => 100,
            'category' => 'Food',
            'expense_date' => '2026-08-01',
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_update_own_expense(): void
    {
        $expense = Expense::factory()->for($this->user)->create([
            'description' => 'Original',
            'amount' => 50,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/expenses/{$expense->id}", [
            'description' => 'Updated description',
            'amount' => 12500,
            'category' => 'Food',
            'expense_date' => '2026-08-02',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.description', 'Updated description')
            ->assertJsonPath('data.amount', '12500.00');

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'description' => 'Updated description',
        ]);
    }

    public function test_user_cannot_update_other_users_expense(): void
    {
        $otherUser = User::factory()->create();
        $expense = Expense::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user)->putJson("/api/expenses/{$expense->id}", [
            'description' => 'Hacked',
            'amount' => 100,
            'category' => 'Food',
            'expense_date' => '2026-08-01',
        ]);

        $response->assertNotFound();
    }

    public function test_delete_expense_requires_authentication(): void
    {
        $expense = Expense::factory()->for($this->user)->create();

        $response = $this->deleteJson("/api/expenses/{$expense->id}");

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_delete_own_expense(): void
    {
        $expense = Expense::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/expenses/{$expense->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_user_cannot_delete_other_users_expense(): void
    {
        $otherUser = User::factory()->create();
        $expense = Expense::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/expenses/{$expense->id}");

        $response->assertNotFound();
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    public function test_summary_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/expenses/summary');

        $response->assertUnauthorized();
    }

    public function test_summary_filters_by_authenticated_user(): void
    {
        $otherUser = User::factory()->create();

        Expense::factory(3)->for($this->user)
            ->create(['amount' => 100, 'category' => ExpenseCategory::Food]);
        Expense::factory(2)->for($otherUser)
            ->create(['amount' => 500, 'category' => ExpenseCategory::Food]);

        $response = $this->actingAs($this->user)->getJson('/api/expenses/summary');

        $response->assertOk()
            ->assertJsonPath('data.total', '300.00');
    }

    public function test_summary_includes_all_user_categories(): void
    {
        Expense::factory()->for($this->user)
            ->create(['amount' => 100, 'category' => ExpenseCategory::Food]);
        Expense::factory()->for($this->user)
            ->create(['amount' => 200, 'category' => ExpenseCategory::Transport]);
        Expense::factory()->for($this->user)
            ->create(['amount' => 300, 'category' => ExpenseCategory::Rent]);

        $response = $this->actingAs($this->user)->getJson('/api/expenses/summary');

        $response->assertOk()
            ->assertJsonPath('data.total', '600.00')
            ->assertJsonCount(3, 'data.by_category');
    }
}
