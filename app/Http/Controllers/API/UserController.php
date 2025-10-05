<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Moneysite\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::select(
            'users.id',
            'users.username',
            'users.phone',
            'users.can_login',
            'users.can_play_game',
            'users.ip',
            'users.created_at',
            'users.active_balance',
            'users.is_new_member',
            DB::raw("IF(users.is_new_member = 1, 'NEW', 'has_transaction') AS transaction_tag"),
            'userbanks.bank_name',
            'userbanks.account_number',
            'userbanks.account_name'
        )->leftJoin('userbanks', 'userbanks.user_id', '=', 'users.id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('users.username', 'like', "%{$search}%")
                    ->orWhere('userbanks.account_name', 'like', "%{$search}%")
                    ->orWhere('userbanks.account_number', 'like', "%{$search}%")
                    ->orWhere('users.ip', 'like', "%{$search}%");
            });
        }

        if ($request->filled('filter')) {
            if ($request->filter === 'new_member') {
                $query->where('users.is_new_member', true);
            } elseif ($request->filter === 'have_balance') {
                $query->where('users.active_balance', '>', 0);
            }
        }

        if ($request->filled('daterange') && (! $request->filled('search') || empty($request->search))) {
            $startDate = Carbon::parse($request->daterange[0])->startOfDay();
            $endDate   = Carbon::parse($request->daterange[1])->endOfDay();
            $query->whereBetween('users.created_at', [$startDate, $endDate]);
        }

        $filteredQuery = clone $query;
        $totalFiltered = $filteredQuery->count();

        $length = $request->length ?: 10;
        $page   = $request->page ?: 1;

        $users = $query->orderByDesc('users.created_at')
            ->offset(($page - 1) * $length)
            ->limit($length)
            ->get();

        return response()->json([
            'draw'            => $request->draw,
            'recordsTotal'    => User::count(),
            'recordsFiltered' => $totalFiltered,
            'data'            => $users,
        ]);
    }

    public function show(string $userId)
    {
        try {
            $user = User::with('userbank')->findOrFail($userId);

            return $this->apiResponse(true, 'User data retrieved successfully.', 200, [
                'id'             => $user->id,
                'username'       => $user->username,
                'email'          => $user->email,
                'phone'          => $user->phone,
                'player_token'   => $user->player_token,
                'ip'             => $user->ip,
                'is_new_member'  => $user->is_new_member,
                'can_login'      => $user->can_login,
                'can_play_game'  => $user->can_play_game,
                'active_balance' => $user->active_balance,
                'status'         => $user->status,
                'userbank'       => $user->userbank ? [
                    'bank_name'      => $user->userbank->bank_name,
                    'account_name'   => $user->userbank->account_name,
                    'account_number' => $user->userbank->account_number,
                ] : null,
                'created_at'     => $user->created_at->toDateTimeString(),
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->apiResponse(false, 'User not found.', 404);
        } catch (\Exception $e) {
            return $this->apiResponse(false, 'An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function deposit(Request $request, string $id)
    {
        try {
            $validated = validator($request->all(), [
                'amount' => 'required|numeric|min:0.01',
            ]);

            if ($validated->fails()) {
                return $this->apiResponse(false, $validated->errors()->first(), 422);
            }

            $user   = User::findOrFail($id);
            $amount = (float) $request->amount;

            $admin    = auth()->guard('admin')->user();
            $response = ApiTransactions::deposit($user->player_token, (int) $amount);

            if (! $response['success']) {
                return $this->apiResponse(false, $response['message'] ?? 'Deposit gagal', 424);
            }

            $data        = $response['data'] ?? [];
            $msg         = $response['message'] ?? 'Unknown error';
            $userBalance = $data['balance'] ?? null;

            if ($userBalance === null) {
                return $this->apiResponse(false, 'User balance not returned from API.', 422);
            }

            $user->active_balance = $userBalance;
            $user->save();

            return $this->apiResponse(true, $msg ?? 'Deposit berhasil', 200, [
                'new_balance' => $userBalance,
            ]);
        } catch (\Throwable $e) {
            return $this->apiResponse(false, 'Failed to process the deposit: ' . $e->getMessage(), 500);
        }
    }

    public function withdrawal(Request $request, string $id)
    {
        try {
            $validated = validator($request->all(), [
                'amount' => 'required|numeric|min:0.01',
            ]);

            if ($validated->fails()) {
                return $this->apiResponse(false, $validated->errors()->first(), 422);
            }

            $user   = User::findOrFail($id);
            $amount = (float) $validated->validated()['amount'];

            $admin = auth()->guard('admin')->user();

            $response = ApiTransactions::withdraw($user->player_token, (int) $amount);

            if (! $response['success']) {
                return $this->apiResponse(false, $response['message'] ?? 'Withdraw gagal', 424);
            }

            $data        = $response['data'] ?? [];
            $msg         = $response['message'] ?? 'Unknown error';
            $userBalance = $data['balance'] ?? null;

            if ($userBalance === null) {
                return $this->apiResponse(false, 'User balance not returned from API.', 424);
            }

            $user->active_balance = $userBalance;
            $user->save();

            return $this->apiResponse(true, $msg, 200, [
                'new_balance' => $userBalance,
            ]);
        } catch (\Throwable $e) {
            return $this->apiResponse(false, 'Gagal memproses penarikan: ' . $e->getMessage(), 500);
        }
    }

    public function password(Request $request, string $id)
    {
        try {
            $validator = validator($request->all(), [
                'newPassword' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(false, $validator->errors()->first(), 422);
            }

            $user           = User::findOrFail($id);
            $user->password = Hash::make($request->newPassword);
            $user->save();

            return $this->apiResponse(true, 'Password reset successful.', 200);
        } catch (\Exception $e) {
            return $this->apiResponse(false, 'Failed to reset password: ' . $e->getMessage(), 500);
        }
    }

    public function status(string $userId, string $field)
    {
        try {
            $allowedFields = ['can_login', 'can_play_game'];

            if (! in_array($field, $allowedFields)) {
                return $this->apiResponse(false, 'Invalid status field requested.', 422);
            }

            $user = User::findOrFail($userId);

            $user->$field = ! $user->$field;
            $user->save();

            return $this->apiResponse(
                true,
                ucfirst(str_replace('_', ' ', $field)) . ' updated successfully.',
                200,
                [$field => $user->$field]
            );
        } catch (\Exception $e) {
            return $this->apiResponse(false, 'Failed to update status: ' . $e->getMessage(), 500);
        }
    }

    public function updateBankAccount(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'bank_name'      => 'required|string|max:255',
                'account_name'   => 'required|string|max:255',
                'account_number' => 'required|string|max:255',
            ]);

            $user     = User::findOrFail($id);
            $userBank = $user->userbank;

            if (! $userBank) {
                return $this->apiResponse(false, 'Bank account not found for this user.', 404);
            }

            $userBank->bank_name      = $validated['bank_name'];
            $userBank->account_name   = $validated['account_name'];
            $userBank->account_number = $validated['account_number'];
            $userBank->save();

            return $this->apiResponse(true, 'Bank account updated successfully.', 200, [
                'bank_name'      => $userBank->bank_name,
                'account_name'   => $userBank->account_name,
                'account_number' => $userBank->account_number,
            ]);
        } catch (\Exception $e) {
            return $this->apiResponse(false, 'Failed to update bank account: ' . $e->getMessage(), 500);
        }
    }
}
