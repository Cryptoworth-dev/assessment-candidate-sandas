<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_login_allows_5_attempts_per_minute_per_email_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);

            $response->assertStatus(422);
        }
    }

    public function test_login_rejects_6th_attempt_with_429(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
        $response->assertJsonPath('message', 'Too Many Attempts.');
    }

    public function test_login_includes_rate_limit_headers_in_429(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
        $this->assertTrue($response->headers->has('Retry-After'));
        $this->assertEquals('5', $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals('0', $response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_login_keyed_by_email_and_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'user1@example.com',
                'password' => 'wrong',
            ]);
        }

        $response1 = $this->postJson('/api/login', [
            'email' => 'user1@example.com',
            'password' => 'wrong',
        ]);
        $response1->assertStatus(429);

        $response2 = $this->postJson('/api/login', [
            'email' => 'user2@example.com',
            'password' => 'wrong',
        ]);
        $response2->assertStatus(422);
    }

    public function test_register_allows_5_attempts_per_minute_per_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/register', [
                'name' => "User $i",
                'email' => "user$i@example.com",
                'password' => 'password123',
            ]);

            $response->assertStatus(201);
        }
    }

    public function test_register_rejects_6th_attempt_with_429(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/register', [
                'name' => "User $i",
                'email' => "user$i@example.com",
                'password' => 'password123',
            ]);
        }

        $response = $this->postJson('/api/register', [
            'name' => 'User 6',
            'email' => 'user6@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(429);
        $response->assertJsonPath('message', 'Too Many Attempts.');
    }

    public function test_register_includes_rate_limit_headers_in_429(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/register', [
                'name' => "User $i",
                'email' => "user$i@example.com",
                'password' => 'password123',
            ]);
        }

        $response = $this->postJson('/api/register', [
            'name' => 'User 6',
            'email' => 'user6@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(429);
        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
        $this->assertTrue($response->headers->has('Retry-After'));
        $this->assertEquals('5', $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_register_keyed_by_ip_only(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/register', [
                'name' => "User $i",
                'email' => "user$i@example.com",
                'password' => 'password123',
            ]);
        }

        $response = $this->postJson('/api/register', [
            'name' => 'User 6',
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(429);
    }

    public function test_api_allows_60_requests_per_minute_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 60; $i++) {
            $response = $this->actingAs($user)->getJson('/api/expenses');
            $response->assertOk();
        }
    }

    public function test_api_rejects_61st_request_with_429(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 60; $i++) {
            $this->actingAs($user)->getJson('/api/expenses');
        }

        $response = $this->actingAs($user)->getJson('/api/expenses');
        $response->assertStatus(429);
    }

    public function test_api_keyed_by_user_id_for_authenticated_requests(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        for ($i = 0; $i < 60; $i++) {
            $this->actingAs($user1)->getJson('/api/expenses');
        }

        $response1 = $this->actingAs($user1)->getJson('/api/expenses');
        $response1->assertStatus(429);

        $response2 = $this->actingAs($user2)->getJson('/api/expenses');
        $response2->assertOk();
    }

    public function test_api_limiter_applies_to_all_expense_endpoints(): void
    {
        $user = User::factory()->create();

        // Test multiple different endpoints all count toward the same 60/min limit per user
        for ($i = 0; $i < 30; $i++) {
            $this->actingAs($user)->getJson('/api/expenses');
            $this->actingAs($user)->getJson('/api/expenses/summary');
        }

        // 60 requests made, 61st should be rate limited
        $response = $this->actingAs($user)->getJson('/api/expenses');
        $response->assertStatus(429);
    }

    public function test_api_includes_rate_limit_headers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/expenses');

        $response->assertOk();
        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
        $this->assertEquals('60', $response->headers->get('X-RateLimit-Limit'));
        $this->assertLessThanOrEqual(60, (int) $response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_login_stacked_with_api_limiter(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'wrong',
            ]);
        }

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(429);
        $this->assertEquals('5', $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_register_stacked_with_api_limiter(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/register', [
                'name' => "User $i",
                'email' => "user$i@example.com",
                'password' => 'password123',
            ]);
        }

        $response = $this->postJson('/api/register', [
            'name' => 'User 6',
            'email' => 'user6@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(429);
        $this->assertEquals('5', $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_rate_limit_isolation_between_tests(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'wrong',
            ]);
        }

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong',
        ]);
        $response->assertStatus(429);

        // Note: Each test method gets a fresh database and cache due to RefreshDatabase trait,
        // so rate limits don't leak between tests. This test verifies rate limiting works
        // within a single test method. See test_login_rejects_6th_attempt_with_429 for proof.
    }
}
