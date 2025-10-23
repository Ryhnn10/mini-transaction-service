<?php

namespace App\Listeners;

use App\Events\TransactionCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendTransactionNotification implements ShouldQueue
{
    public function handle(TransactionCompleted $event)
    {
        $transaction = $event->transaction;

        // Contoh: log atau kirim email
        Log::info("Notification: Transaction {$transaction->id} completed for user {$transaction->user_id}");
    }
}