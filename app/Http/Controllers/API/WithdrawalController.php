<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Moneysite\Transaction;
use App\Models\Moneysite\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Pusher\Pusher;

class WithdrawalController extends Controller
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
                'transactions.amount',
                'transactions.recipient_bank_name',
                'transactions.recipient_account_number',
                'transactions.recipient_account_name',
                'transactions.note'
            )
            ->where('transactions.type', 'Withdrawal')
            ->where('transactions.status', 'Pending')
            ->join('users', 'users.id', '=', 'transactions.user_id');

        $query->when($request->filled('search'), function ($q) use ($request) {
            $q->where('users.username', 'like', "%{$request->search}%");
        });

        $length = $request->length ?: 10;
        $page   = $request->page ?: 1;
        $offset = ($page - 1) * $length;

        $total = (clone $query)->count();

        $data = $query->offset($offset)
            ->limit($length)
            ->orderByDesc('transactions.created_at')
            ->get();

        return response()->json([
            'draw'            => $request->draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'withdrawal_id' => 'required',
            'note'          => 'nullable|string|max:500',
            'action'        => 'required|in:Approved,Rejected',
        ], [
            'note.string'     => 'Note must be a string.',
            'note.max'        => 'Note cannot exceed 500 characters.',
            'action.required' => 'Action is required.',
            'action.in'       => 'Action must be either "Approved" or "Rejected".',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(false, 'Failed to process the request: ' . $validator->errors()->first(), 500);
        }

        $validated = $validator->validated();
        $id        = $validated['withdrawal_id'];
        $note      = $validated['note'];
        $action    = $validated['action'];

        $withdrawal = Transaction::find($id);
        $user       = User::find($withdrawal->user_id);
        $admin      = auth()->guard('admin')->user();

        if (! $withdrawal || ! $user) {
            return $this->apiResponse(false, 'Withdrawal or user not found.', 404);
        }

        if ($withdrawal->amount > $admin->max_transaction) {
            return $this->apiResponse(false, 'Transaction amount exceeds the maximum limit.', 422);
        }

        // === APPROVE ===
        if ($action === 'Approved' && $withdrawal->status !== 'Approved') {
            $withdrawal->status = 'Approved';
            $withdrawal->note   = $note;
            $withdrawal->admin  = $admin->username;
            $withdrawal->save();

            $this->triggerPusher([
                'status'         => 'Approved',
                'transaction_id' => $withdrawal->id,
            ]);

            return $this->apiResponse(true, 'Withdrawal successfully Approved.', 200);
        }

        // === REJECT ===
        if ($action === 'Rejected' && $withdrawal->status === 'Pending') {
            try {
                $response = ApiTransactions::deposit($user->player_token, (int) $withdrawal->amount);
                $data     = $response['data'] ?? [];

                $status      = $data['success'] ?? false;
                $msg         = $response['messgae'] ?? 'Unknown error occurred.';
                $userBalance = $data['balance'] ?? null;

                if ($status || $userBalance === null) {
                    return $this->apiResponse(false, $msg ?: 'User balance not returned from API.', 422);
                }

                $withdrawal->update([
                    'status' => 'Rejected',
                    'note'   => $note,
                    'admin'  => $admin->username,
                ]);

                $user->update([
                    'active_balance' => (float) $userBalance,
                ]);

                $this->triggerPusher([
                    'status'         => 'Rejected',
                    'transaction_id' => $withdrawal->id,
                ]);

                return $this->apiResponse(true, 'Withdrawal rejected & deposit returned.', 200);
            } catch (\Throwable $e) {
                return $this->apiResponse(false, 'Failed to process withdrawal: ' . $e->getMessage(), 500);
            }
        }

        return $this->apiResponse(false, 'Invalid action or status.', 422);
    }

    private function triggerPusher($data)
    {
        $adminCredential = Auth::guard('admin')->user()->credential;
        $pusher          = new Pusher(
            $adminCredential->pusher_key,
            $adminCredential->pusher_secret,
            $adminCredential->pusher_app_id,
            [
                'cluster' => 'ap1',
                'useTLS'  => true,
            ]
        );
        $pusher->trigger('my-channel', 'withdraw-status', [
            'status'         => $data['status'],
            'transaction_id' => $data['transaction_id'],
            'admin'          => Auth::guard('admin')->user()->username,
        ]);
    }
}
