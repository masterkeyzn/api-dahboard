<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Moneysite\Bonusdeposit;
use App\Models\Moneysite\Transaction;
use App\Models\Moneysite\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        $promotions = Bonusdeposit::query();

        $length = $request->length ?: 10;
        $page = $request->page ?: 1;
        $offset = ($page - 1) * $length;

        $promotions = $promotions->offset($offset)->limit($length)->orderBy('created_at', 'desc')->get();


        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => Bonusdeposit::count(),
            'recordsFiltered' => $promotions->count(),
            'data' => $promotions,
        ]);
    }

    public function destroy(string $id)
    {
        try {
            $bonusdeposit = Bonusdeposit::findOrFail($id);

            $bonusdeposit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bonus deposit berhasil dihapus.'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bonus deposit tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus bonus deposit.'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bonusName' => 'required|string|max:255',
            'typeBonus' => 'required|in:bonus_persen,bonus_fixed',
            'categoryUser' => 'required|in:all,new',
            'targetType' => 'required|in:target_turnover,max_withdrawal',
            'amountBonus' => 'required|integer',
            'maxAmountBonus' => 'required|string',
            'maxClaims' => 'required|integer',
            'minTransaction' => 'nullable|integer',
            'targetTOWO' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $bonusdeposit = Bonusdeposit::create([
                'name' => $request->bonusName,
                'type' => $request->typeBonus,
                'category' => $request->categoryUser,
                'condition_type' => $request->targetType,
                'amount' => $request->amountBonus,
                'max_bonus' => $request->maxAmountBonus,
                'max_claims' => $request->maxClaims,
                'min_deposit' => $request->minTransaction,
                'target_turnover' => $request->targetTOWO,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bonus deposit created successfully.',
                'data' => $bonusdeposit
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan bonus deposit.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'bonusName' => 'required|string|max:255',
            'typeBonus' => 'required|in:bonus_persen,bonus_fixed',
            'categoryUser' => 'required|in:all,new',
            'targetType' => 'required|in:target_turnover,max_withdrawal',
            'amountBonus' => 'required|integer',
            'maxAmountBonus' => 'required|string',
            'maxClaims' => 'required|integer',
            'minTransaction' => 'nullable|integer',
            'targetTOWO' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $bonusdeposit = Bonusdeposit::findOrFail($id);
            $bonusdeposit->update([
                'name' => $request->bonusName,
                'type' => $request->typeBonus,
                'category' => $request->categoryUser,
                'condition_type' => $request->targetType,
                'amount' => $request->amountBonus,
                'max_bonus' => $request->maxAmountBonus,
                'max_claims' => $request->maxClaims,
                'min_deposit' => $request->minTransaction,
                'target_turnover' => $request->targetTOWO,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bonus deposit updated successfully.',
                'data' => $bonusdeposit
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengupdate bonus deposit.',
            ], 500);
        }
    }

    public function bulkShareBonus(Request $request)
    {
        $data = $request->input('data');

        if (!is_array($data)) {
            return $this->apiResponse(false, 'Invalid data format', 422);
        }

        $results = [];
        $failed = false;

        foreach ($data as $value) {
            if (!isset($value['player_token'], $value['bonus_amount'], $value['decription'])) {
                $results[] = [
                    'player_token' => $value['player_token'] ?? 'Unknown',
                    'status' => 'Failed',
                    'message' => 'Missing required fields.'
                ];
                $failed = true;
                continue;
            }

            $postArray = [
                'method' => 'user_deposit',
                'user_code' => $value['player_token'],
                'amount' => (float) $value['bonus_amount'],
            ];

            try {
                $apiResponse = self::sendToNexus($postArray);

                $status = $apiResponse['status'] ?? null;
                $msg = $apiResponse['msg'] ?? 'Unknown error occurred.';
                $userBalance = $apiResponse['user_balance'] ?? null;

                if ($status !== 1) {
                    $results[] = [
                        'player_token' => $value['player_token'],
                        'status' => 'Failed',
                        'message' => $msg,
                    ];
                    $failed = true;
                    continue;
                }

                $user = User::where('player_token', $value['player_token'])->first();
                if (!$user) {
                    $results[] = [
                        'player_token' => $value['player_token'],
                        'status' => 'Failed',
                        'message' => 'User not found.',
                    ];
                    $failed = true;
                    continue;
                }

                $bonusTransaction = new Transaction();
                $bonusTransaction->user_id = $user->id;
                $bonusTransaction->transaction_id = $user->id . time();
                $bonusTransaction->amount = (float) $value['bonus_amount'];
                $bonusTransaction->type = 'Bonus';
                $bonusTransaction->sender_bank_name = 'Bank/Admin';
                $bonusTransaction->note = $value['decription'];
                $bonusTransaction->status = 'Approved';
                $bonusTransaction->admin = auth()->guard('admin')->user()->username;
                $bonusTransaction->save();

                $user->active_balance = (float) $userBalance;
                $user->save();

                $results[] = [
                    'player_token' => $value['player_token'],
                    'status' => 'Success',
                    'message' => 'Bonus processed successfully.',
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'player_token' => $value['player_token'],
                    'status' => 'Failed',
                    'message' => 'Exception: ' . $e->getMessage(),
                ];
                $failed = true;
            }
        }

        return $this->apiResponse(
            !$failed,
            $failed ? 'Some bonuses failed to process.' : 'All bonuses processed successfully.',
            $failed ? 500 : 200,
            $results
        );
    }
}
