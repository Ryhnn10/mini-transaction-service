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
    /**
     * @OA\Post(
     *     path="/api/transactions",
     *     summary="Create a new transaction (DEBIT or CREDIT)",
     *     tags={"Transactions"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type","amount"},
     *             @OA\Property(property="type", type="string", enum={"DEBIT","CREDIT"}, example="DEBIT"),
     *             @OA\Property(property="amount", type="integer", example=5000),
     *             @OA\Property(property="remarks", type="string", example="Payment for service")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transaction created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="transactionId", type="string", format="uuid"),
     *             @OA\Property(property="status", type="string", example="PENDING")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Insufficient balance"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/transactions/{id}",
     *     summary="Get transaction details by ID",
     *     tags={"Transactions"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Transaction ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction details retrieved",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="type", type="string", example="DEBIT"),
     *             @OA\Property(property="amount", type="integer", example=5000),
     *             @OA\Property(property="status", type="string", example="PENDING"),
     *             @OA\Property(property="remarks", type="string", example="Payment for service"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Alif Reyhan"),
     *                 @OA\Property(property="email", type="string", example="user@example.com")
     *             ),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Transaction not found")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/balance/{id}",
     *     summary="Get user balance by user ID",
     *     tags={"Transactions"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User balance retrieved",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="balance", type="integer", example=100000)
     *         )
     *     ),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
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
