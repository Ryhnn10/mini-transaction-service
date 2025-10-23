<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use App\Events\TransactionCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessWalletTransaction implements ShouldQueue
{
    public $tries = 3; // pengulangan max 3x

    public function handle(TransactionCreated $event)
    {
        $transaction = $event->transaction;

        DB::beginTransaction();
        try {
            $user = $transaction->user()->lockForUpdate()->first(); 

            if ($transaction->type === 'DEBIT') {
                if ($user->balance < $transaction->amount) {
                    $transaction->status = 'FAILED';
                    $transaction->save();
                    Log::warning("Insufficient balance for transaction {$transaction->id}");
                    DB::commit();
                    return;
                }
                $user->balance -= $transaction->amount;
            } else if ($transaction->type === 'CREDIT') {
                $user->balance += $transaction->amount;
            }

            $user->save();

            $transaction->status = 'SUCCESS';
            $transaction->save();

            TransactionCompleted::dispatch($transaction);

            Log::info("Transaction {$transaction->id} completed successfully");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Transaction {$transaction->id} failed: " . $e->getMessage());
            throw $e; 
        }
    }
}