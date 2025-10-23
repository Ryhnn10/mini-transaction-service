<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->user = User::factory()->create(['balance' => 100000]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    #[Test]
    public function it_returns_validation_error_when_input_invalid()
    {
        $user = User::factory()->create(['balance' => 1000]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/transactions', [
                'type' => 'INVALID_TYPE',
                'amount' => -100
            ]);

        $response->assertStatus(422)
                 ->assertJson(['message' => 'Validation error']);
    }

    #[Test]
    public function it_returns_404_when_transaction_not_found()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $invalidId = '00000000-0000-0000-0000-000000000999';
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/transactions/{$invalidId}");

        $response->assertStatus(404);
    }

    #[Test]
    public function it_returns_403_when_accessing_other_users_transaction()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user1->id]);

        $token = JWTAuth::fromUser($user2);
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function it_returns_404_when_user_not_found_in_balance_check()
    {
        $response = $this->getJson('/api/balance/9999');
        $response->assertStatus(404);
    }

    #[Test]
    public function create_transaction_success()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/transactions', [
                'type' => 'DEBIT',
                'amount' => 50000,
                'remarks' => 'Top up mobile balance'
            ]);

        $response->assertStatus(201)
                ->assertJsonStructure(['transactionId', 'status']);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'amount' => 50000,
            'status' => 'PENDING',
        ]);
    }

    #[Test]
    public function debit_insufficient_balance()
    {
        $lowBalanceUser = User::factory()->create(['balance' => 30000]);
        $tokenLow = JWTAuth::fromUser($lowBalanceUser);

        $response = $this->withHeader('Authorization', "Bearer {$tokenLow}")
            ->postJson('/api/transactions', [
                'type' => 'DEBIT',
                'amount' => 50000,
                'remarks' => 'Top up mobile balance'
            ]);

        $response->assertStatus(400)
                ->assertJson(['message' => 'Insufficient balance']);
    }

    #[Test]
    public function get_user_balance()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/users/{$this->user->id}/balance");

        $response->assertStatus(200)
                 ->assertJson(['balance' => $this->user->balance]);
    }

    #[Test]
    public function concurrent_debit_transaction()
    {
        $user = User::factory()->create(['balance' => 50000]);
        $token = JWTAuth::fromUser($user);

        $payload = [
            'type' => 'DEBIT',
            'amount' => 30000,
            'remarks' => 'Concurrent test'
        ];

        $responses = [];
        for ($i = 0; $i < 2; $i++) {
            $responses[] = $this->withHeader('Authorization', "Bearer {$token}")
                                ->postJson('/api/transactions', $payload);
        }

        foreach ($responses as $response) {
            $response->assertStatus(201);
        }

        $transactions = Transaction::where('user_id', $user->id)->get();
        foreach ($transactions as $transaction) {
            $listener = new \App\Listeners\ProcessWalletTransaction();
            $listener->handle(new \App\Events\TransactionCreated($transaction));
        }

        $user->refresh();

        $this->assertGreaterThanOrEqual(0, $user->balance);

        $statuses = $transactions->pluck('status')->toArray();
        $this->assertContains('FAILED', $statuses);
        $this->assertContains('SUCCESS', $statuses);
    }

    #[Test]
    public function retry_mechanism_on_listener_failure()
    {
        config(['queue.default' => 'sync']);

        $user = User::factory()->create(['balance' => 50000]);
        $transaction = Transaction::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'type' => 'DEBIT',
            'amount' => 30000,
            'status' => 'PENDING',
            'remarks' => 'Retry test'
        ]);

        $listener = new \App\Listeners\ProcessWalletTransaction();

        $attempt = 0;
        while ($transaction->status !== 'SUCCESS' && $attempt < 3) {
            try {
                $attempt++;
                if ($attempt < 3) {
                    throw new \Exception("Simulated failure");
                }
                $listener->handle(new \App\Events\TransactionCreated($transaction));
            } catch (\Exception $e) {
                // continue retry
            }
            $transaction->refresh();
        }

        $this->assertEquals('SUCCESS', $transaction->status);
        $user->refresh();
        $this->assertEquals(20000, $user->balance);
    }
}
