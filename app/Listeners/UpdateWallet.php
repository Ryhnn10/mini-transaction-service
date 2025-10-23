<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateWallet implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TransactionCreated $event)
    {
        $transaction = $event->transaction;
        $user = $transaction->user()->lockForUpdate()->first();

        if ($transaction->type === 'DEBIT') {
            if ($user->balance >= $transaction->amount) {
                $user->balance -= $transaction->amount;
                $transaction->status = 'SUCCESS';
            } else {
                $transaction->status = 'FAILED';
            }
        } elseif ($transaction->type === 'CREDIT') {
            $user->balance += $transaction->amount;
            $transaction->status = 'SUCCESS';
        }

        $user->save();
        $transaction->save();
    }
}
