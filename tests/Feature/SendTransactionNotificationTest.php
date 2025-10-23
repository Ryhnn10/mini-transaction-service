<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Events\TransactionCompleted;
use App\Listeners\SendTransactionNotification;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class SendTransactionNotificationTest extends TestCase
{
    public function test_send_transaction_notification_listener_logs_message()
    {
        Log::shouldReceive('info')->once();

        //dummy Transaction model
        $transaction = new Transaction([
            'id' => 1,
            'user_id' => 1,
            'amount' => 1000,
            'type' => 'credit',
        ]);

        $event = new TransactionCompleted($transaction);

        $listener = new SendTransactionNotification();
        $listener->handle($event);

        $this->assertTrue(true);
    }
}
