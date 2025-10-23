<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\User;
use App\Events\TransactionCreated;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:DEBIT,CREDIT',
            'amount' => 'required|integer|min:1',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        if ($request->type === 'DEBIT' && $user->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient balance'], 400);
        }

        $transaction = Transaction::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => $request->type,
            'amount' => $request->amount,
            'status' => 'PENDING',
            'remarks' => $request->remarks,
        ]);

        event(new TransactionCreated($transaction));

        return response()->json([
            'transactionId' => $transaction->id,
            'status' => $transaction->status
        ], 201);
    }

    public function show($id)
    {
        $transaction = Transaction::with('user')->find($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $user = Auth::user();
        if ($transaction->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'id' => $transaction->id,
            'type' => $transaction->type,
            'amount' => $transaction->amount,
            'status' => $transaction->status,
            'remarks' => $transaction->remarks,
            'user' => [
                'id' => $transaction->user->id,
                'name' => $transaction->user->name,
                'email' => $transaction->user->email
            ],
            'created_at' => $transaction->created_at,
            'updated_at' => $transaction->updated_at
        ]);
    }

    public function getBalance($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'user_id' => $user->id,
            'balance' => $user->balance
        ]);
    }

}