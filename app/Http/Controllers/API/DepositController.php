<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Moneysite\Bonusdeposit;
use App\Models\Moneysite\GameTransaction;
use App\Models\Moneysite\Transaction;
use App\Models\Moneysite\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Pusher\Pusher;

class DepositController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['user:id,player_token,username'])
            ->select(
                'transactions.id',
                'transactions.type',
                'transactions.status',
                'transactions.created_at',
                'transactions.user_id',
                'transactions.bonus_id',
                'transactions.amount',
                'transactions.recipient_bank_name',
                'transactions.recipient_account_number',
                'transactions.recipient_account_name',
                'transactions.sender_bank_name',
                'transactions.sender_account_number',
                'transactions.sender_account_name',
                'transactions.note',
                DB::raw("IF(users.is_new_member = 1, 'NEW', 'no_transaction') AS new_tag")
            )
            ->where('type', 'Deposit')
            ->where('status', 'Pending')
            ->join('users', 'users.id', '=', 'transactions.user_id');

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;
            $q->where('users.username', 'like', "%{$search}%");
        });

        $length = $request->length ?: 10;
        $page = $request->page ?: 1;
        $offset = ($page - 1) * $length;

        $total = (clone $query)->count();
        $data = $query->offset($offset)->limit($length)->orderByDesc('transactions.created_at')->get();

        $bonusList = Bonusdeposit::select('id', 'name')->get();

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'bonusList' => $bonusList,
            'data' => $data,
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'deposit_id' => 'required|integer',
            'bonus' => 'nullable|integer',
            'note' => 'nullable|string|max:500',
            'action' => 'required|in:Approved,Rejected',
        ], [
            'deposit_id.required' => 'Deposit ID wajib diisi.',
            'deposit_id.integer' => 'Deposit ID harus berupa angka.',

            'bonus.integer' => 'Bonus harus berupa angka (ID).',
            'bonus.exists' => 'Bonus yang dipilih tidak ditemukan.',

            'note.string' => 'Catatan harus berupa teks.',
            'note.max' => 'Catatan maksimal 500 karakter.',

            'action.required' => 'Action wajib dipilih.',
            'action.in' => 'Action harus bernilai "Approved" atau "Rejected".',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(false, 'Failed to process the request: ' . $validator->errors()->first(), 500);
        }

        $validated = $validator->validated();
        $id = $validated['deposit_id'];
        $bonusId = isset($validated['bonus']) ? (int) $validated['bonus'] : null;
        $note = $validated['note'];
        $action = $validated['action'];

        $deposit = Transaction::find($id);
        if (!$deposit) return $this->apiResponse(false, 'Deposit not found.', 404);

        if ($deposit->amount > auth()->guard('admin')->user()->max_transaction) {
            return $this->apiResponse(false, 'Transaction amount exceeds the maximum limit.', 422);
        }

        $user = User::find($deposit->user_id);
        $amount = $deposit->amount;
        $bonusAmount = 0;
        $bonusDeposit = null;

        if ($bonusId !== null && $bonusId !== 0) {
            $bonusDeposit = Bonusdeposit::find($bonusId);
            if (!$bonusDeposit) return $this->apiResponse(false, 'Bonus not found.', 404);

            if ($bonusDeposit->type === 'bonus_persen') {
                $bonusAmount = ($bonusDeposit->amount / 100) * $amount;
            } elseif ($bonusDeposit->type === 'bonus_fixed') {
                $bonusAmount = $bonusDeposit->amount;
            }

            if ($bonusAmount > $bonusDeposit->max_bonus) {
                $bonusAmount = $bonusDeposit->max_bonus;
            }
        }

        $totalDepositAmount = $amount + $bonusAmount;

        // === APPROVE ===
        if ($action === 'Approved' && $deposit->status !== 'Approved') {
            DB::beginTransaction();
            try {
                $createTransactionBonus = null;

                if ($bonusAmount > 0) {
                    $createTransactionBonus = new Transaction([
                        'user_id' => $user->id,
                        'transaction_id' => $user->id . time(),
                        'amount' => $bonusAmount,
                        'type' => 'Bonus',
                        'sender_bank_name' => 'Bank/Admin',
                        'bonus_id' => $bonusDeposit->id,
                        'note' => $bonusDeposit->name,
                        'status' => 'Approved',
                        'admin' => auth()->guard('admin')->user()->username,
                    ]);
                }

                $response = null;
                $userBalance = null;

                $response = ApiTransactions::deposit(
                    $user->player_token,
                    (int) $totalDepositAmount
                );

                $admin = auth()->guard('admin')->user();
                $status = $response['data']['status'] ?? null;
                $msg = $response['data']['msg'] ?? 'Unknown error occurred.';
                $userBalance = $response['data']['user_balance'] ?? null;

                if ($status !== 1 || $userBalance === null) {
                    DB::rollBack();
                    return $this->apiResponse(false, $msg, 500);
                }

                GameTransaction::create([
                    'status'         => $status,
                    'msg'            => $msg,
                    'agent_code'     => $admin->credential->agent_code ?? '',
                    'agent_balance'  => $response['data']['agent_balance'] ?? 0,
                    'agent_type'     => $response['data']['agent_type'] ?? 'Transfer',
                    'user_code'      => $user->player_token,
                    'user_balance'   => $userBalance,
                    'deposit_amount' => $totalDepositAmount,
                    'currency'       => $response['data']['currency'] ?? 'IDR',
                    'order_no'       => $response['data']['order_no'] ?? 0,
                    'admin_id'       => $admin?->id,
                    'action_by'      => 'admin',
                    'action_note'    => $user->is_playing
                        ? 'Deposit dilakukan saat user sedang bermain'
                        : 'Deposit dilakukan meskipun user sedang tidak bermain',
                ]);


                if ($userBalance === null) {
                    DB::rollBack();
                    return $this->apiResponse(false, 'User balance not returned from API.', 422);
                }

                $deposit->update([
                    'status' => 'Approved',
                    'bonus' => $bonusDeposit->name ?? null,
                    'note' => $note,
                    'admin' => auth()->guard('admin')->user()->username,
                ]);

                $user->update([
                    'active_balance' => (float) $userBalance,
                    'is_new_member' => false,
                ]);

                if ($createTransactionBonus) $createTransactionBonus->save();

                $totalDeposits = Transaction::where('user_id', $user->id)
                    ->where('type', 'Deposit')
                    ->where('status', 'Approved')
                    ->count();

                $isFirstDeposit = $totalDeposits === 1;


                if (
                    $user->userReferral &&
                    $user->userReferral->referral &&
                    $user->userReferral->referral->status === 'active'
                ) {
                    $referral = $user->userReferral->referral;


                    $userReferral = $user->userReferral;

                    if ($isFirstDeposit) {
                        $ndpCommission = $referral->commission_ndp_type === 'percent'
                            ? ($referral->commission_ndp_value / 100) * $deposit->amount
                            : $referral->commission_ndp_value;

                        $referral->referral_balance += $ndpCommission;
                        $referral->save();

                        $userReferral->first_deposit_at = now();
                        $userReferral->first_deposit_amount = $deposit->amount;
                        $userReferral->ndp_commission = $ndpCommission;
                        $userReferral->commission_earned = $ndpCommission;
                        $userReferral->total_deposit_count = 1;
                        $userReferral->save();
                    } else {
                        $rdpCommission = $referral->commission_rdp_type === 'percent'
                            ? ($referral->commission_rdp_value / 100) * $deposit->amount
                            : $referral->commission_rdp_value;

                        $referral->referral_balance += $rdpCommission;
                        $referral->save();

                        $userReferral->rdp_commission_total += $rdpCommission;
                        $userReferral->commission_earned += $rdpCommission;
                        $userReferral->total_deposit_count = $totalDeposits;
                        $userReferral->save();
                    }
                }


                DB::commit();

                $this->triggerPusher([
                    'status' => 'Approved',
                    'transaction_id' => $deposit->id,
                ]);

                return $this->apiResponse(true, 'Deposit successfully Approved.', 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->apiResponse(false, 'Failed to process the deposit: ' . $e->getMessage(), 500);
            }
        }

        // === REJECT ===
        if ($action === 'Rejected') {
            $deposit->update([
                'status' => 'Rejected',
                'bonus' => null,
                'note' => $note,
                'admin' => auth()->guard('admin')->user()->username,
            ]);

            $this->triggerPusher([
                'status' => 'Rejected',
                'transaction_id' => $deposit->id,
            ]);

            return $this->apiResponse(true, 'Deposit successfully Rejected.', 200);
        }

        return $this->apiResponse(false, 'Invalid action.', 422);
    }

    private function triggerPusher($data)
    {
        $admin = Auth::guard('admin')->user()->credential;
        $pusher = new Pusher(
            $admin->pusher_key,
            $admin->pusher_secret,
            $admin->pusher_app_id,
            [
                'cluster' => 'ap1',
                'useTLS' => true,
            ]
        );
        $pusher->trigger('my-channel', 'deposit-status', [
            'status' => $data['status'],
            'transaction_id' => $data['transaction_id'],
            'admin' => Auth::guard('admin')->user()->username,
        ]);
    }
}
