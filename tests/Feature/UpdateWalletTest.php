<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Events\TransactionCreated;
use App\Listeners\UpdateWallet;
use PHPUnit\Framework\Attributes\Test;

class UpdateWalletTest extends TestCase
{
    #[Test]
    public function update_wallet_listener_updates_balance()
    {
        $user = User::factory()->create(['balance' => 0]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 5000,
            'type' => 'CREDIT',
        ]);

        $event = new TransactionCreated($transaction);
        $listener = new UpdateWallet();
        $listener->handle($event);

        $user->refresh();
        $this->assertEquals(5000, $user->balance);
    }

    #[Test]
    public function it_sets_transaction_failed_when_balance_insufficient()
    {
        $user = User::factory()->create(['balance' => 50]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'DEBIT',
            'amount' => 100,
        ]);

        $listener = new UpdateWallet();
        $listener->handle(new TransactionCreated($transaction));

        $transaction->refresh();
        $this->assertEquals('FAILED', $transaction->status);
    }

    #[Test]
    public function it_handles_credit_transaction_successfully()
    {
        $user = User::factory()->create(['balance' => 100]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'CREDIT',
            'amount' => 200,
        ]);

        $listener = new UpdateWallet();
        $listener->handle(new TransactionCreated($transaction));

        $user->refresh();
        $transaction->refresh();

        $this->assertEquals(300, $user->balance);
        $this->assertEquals('SUCCESS', $transaction->status);
    }
}
