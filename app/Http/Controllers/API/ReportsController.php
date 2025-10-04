<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Moneysite\Transaction;
use App\Models\Moneysite\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['user:id,username']);

        $length = $request->length ?: 10;
        $page = $request->page ?: 1;
        $offset = ($page - 1) * $length;

        if ($request->has('filter') && !empty($request->filter)) {
            $filter = $request->filter;

            if ($filter === 'deposit') {
                $query->where('type', 'Deposit');
            }

            if ($filter === 'deposit_approved') {
                $query->where('type', 'Deposit')->where('status', 'Approved');
            }

            if ($filter === 'deposit_rejected') {
                $query->where('type', 'Deposit')->where('status', 'Rejected');
            }

            if ($filter === 'withdrawal') {
                $query->where('type', 'Withdrawal');
            }

            if ($filter === 'withdrawal_approved') {
                $query->where('type', 'Withdrawal')->where('status', 'Approved');
            }

            if ($filter === 'withdrawal_rejected') {
                $query->where('type', 'Withdrawal')->where('status', 'Rejected');
            }
        }

        $query->whereIn('type', ['Deposit', 'Withdrawal'])->where('status', '!=', 'Pending');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($query) use ($search) {
                $query->where('sender_account_name', 'like', "%{$search}%")
                    ->orWhere('recipient_account_name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($query) use ($search) {
                        $query->where('username', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('daterange') && !empty($request->daterange)) {
            if (!$request->has('search') || empty($request->search)) {
                $daterange = $request->daterange;
                $startDate = Carbon::parse($daterange[0])->setTimezone('Asia/Jakarta')->startOfDay();
                $endDate = Carbon::parse($daterange[1])->setTimezone('Asia/Jakarta')->endOfDay();
                $query->whereBetween('transactions.created_at', [$startDate, $endDate]);
            }
        }

        $transactionList = $query->offset($offset)->limit($length)->orderBy('id', 'desc')->get();

        $total = Transaction::whereIn('type', ['Deposit', 'Withdrawal'])->where('status', '!=', 'Pending')->count();

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $transactionList,
        ]);
    }

    public function dailyReports(Request $request)
    {
        $date = $request->date ?? Carbon::now()->toDateString();
        $startDate = Carbon::parse($date)->setTimezone('Asia/Jakarta')->startOfDay();
        $endDate = Carbon::parse($date)->setTimezone('Asia/Jakarta')->endOfDay();

        $userToday = User::whereBetween('created_at', [$startDate, $endDate]);
        $totalUser = $userToday->count();

        $usersWithApprovedDeposits = User::whereHas('transactions', function ($query) {
            $query->where('type', 'deposit')
                ->where('status', 'approved');
        })->whereBetween('created_at', [$startDate, $endDate])->count();

        $usersWithoutApprovedDeposits = User::whereDoesntHave('transactions', function ($query) {
            $query->where('type', 'deposit')
                ->where('status', 'approved');
        })->whereBetween('created_at', [$startDate, $endDate])->count();

        $usersFromReferral = User::whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('userReferral')
            ->count();

        $usersClaimBonus = Transaction::where('type', 'Bonus')->whereBetween('created_at', [$startDate, $endDate]);

        $totalDepositApproved = Transaction::where('type', 'Deposit')->where('status', 'Approved')->whereBetween('created_at', [$startDate, $endDate])->count();
        $totalDepositAmountApproved = Transaction::where('type', 'Deposit')->where('status', 'Approved')->whereBetween('created_at', [$startDate, $endDate])->sum('amount');

        $totalDepositRejected = Transaction::where('type', 'Deposit')->where('status', 'Rejected')->whereBetween('created_at', [$startDate, $endDate])->count();
        $totalDepositAmountRejected = Transaction::where('type', 'Deposit')->where('status', 'Rejected')->whereBetween('created_at', [$startDate, $endDate])->sum('amount');

        $totalWithdrawApproved = Transaction::where('type', 'Withdrawal')->where('status', 'Approved')->whereBetween('created_at', [$startDate, $endDate])->count();
        $totalWithdrawAmountApproved = Transaction::where('type', 'Withdrawal')->where('status', 'Approved')->whereBetween('created_at', [$startDate, $endDate])->sum('amount');

        $totalWithdrawRejected = Transaction::where('type', 'Withdrawal')->where('status', 'Rejected')->whereBetween('created_at', [$startDate, $endDate])->count();
        $totalWithdrawAmountRejected = Transaction::where('type', 'Withdrawal')->where('status', 'Rejected')->whereBetween('created_at', [$startDate, $endDate])->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'total_user' => $totalUser,
                'users_with_approved_deposits' => $usersWithApprovedDeposits,
                'users_without_approved_deposits' => $usersWithoutApprovedDeposits,
                'users_from_referral' => $usersFromReferral,
                'total_bonus_claims' => $usersClaimBonus->count(),
                'total_amount_claims' => $usersClaimBonus->sum('amount'),
                'total_deposit_approved' => $totalDepositApproved,
                'total_deposit_amount_approved' => $totalDepositAmountApproved,
                'total_deposit_rejected' => $totalDepositRejected,
                'total_deposit_amount_rejected' => $totalDepositAmountRejected,
                'total_withdraw_approved' => $totalWithdrawApproved,
                'total_withdraw_amount_approved' => $totalWithdrawAmountApproved,
                'total_withdraw_rejected' => $totalWithdrawRejected,
                'total_withdraw_amount_rejected' => $totalWithdrawAmountRejected,
            ]
        ]);
    }

    public function promotions(Request $request)
    {
        $query = Transaction::with(['user:id,username']);

        $length = $request->length ?: 10;
        $page = $request->page ?: 1;
        $offset = ($page - 1) * $length;

        $query->where('type', 'Bonus');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($query) use ($search) {
                $query->whereHas('user', function ($query) use ($search) {
                    $query->where('username', 'like', "%{$search}%");
                });
            });
        }

        if ($request->has('daterange') && !empty($request->daterange)) {
            if (!$request->has('search') || empty($request->search)) {
                $daterange = $request->daterange;
                $startDate = Carbon::parse($daterange[0])->setTimezone('Asia/Jakarta')->startOfDay();
                $endDate = Carbon::parse($daterange[1])->setTimezone('Asia/Jakarta')->endOfDay();
                $query->whereBetween('transactions.created_at', [$startDate, $endDate]);
            }
        }

        $transactionList = $query->offset($offset)->limit($length)->orderBy('id', 'desc')->get();

        $total = Transaction::where('type', 'Bonus')->count();

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $transactionList,
        ]);
    }
    public function winLose(Request $request)
    {
        $daterange = $request->daterange ?? [
            Carbon::now()->startOfDay()->setTimezone('UTC')->toISOString(),
            Carbon::now()->endOfDay()->setTimezone('UTC')->toISOString()
        ];

        $startDate = Carbon::parse($daterange[0])->setTimezone('UTC')->startOfDay();
        $endDate = Carbon::parse($daterange[1])->setTimezone('UTC')->endOfDay();

        $datePeriod = "{$startDate} - {$endDate}";

        $search = $request->search;
        $page = (int) $request->page - 1 ?: 0;
        $perPage = (int) $request->length ?: 25;

        $postArray = [
            "method" => "agent_statistics",
            "agent_code" => Auth::guard("admin")->user()->agent_code,
            "agent_token" => Auth::guard("admin")->user()->agent_token,
            "start" => Carbon::parse($startDate)->format('Y-m-d H:i:s'),
            "end" => Carbon::parse($endDate)->format('Y-m-d H:i:s'),
            "page" => $page,
            "perPage" => $perPage,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(5)->post('https://api.nexusggr.com', $postArray);

            if ($response['status'] === 1) {
                $responseData = $response->json();

                if ($search) {
                    $responseData['users'] = array_filter($responseData['users'], function ($user) use ($search) {
                        return stripos($user['uc'], $search) !== false;
                    });

                    $responseData['users'] = array_values($responseData['users']);
                }

                return response()->json([
                    'daterange' => $datePeriod,
                    'recordsTotal' => $responseData['total_count'],
                    'recordsFiltered' => count($responseData['users']),
                    'data' => $responseData['users'],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['error' => 'Failed to fetch data.'], 500);
    }


    public function winLoseByPlayerToken(Request $request, $playerToken)
    {
        $postArray = [
            "method" => "get_game_log",
            "agent_code" => Auth::guard("admin")->user()->agent_code,
            "agent_token" => Auth::guard("admin")->user()->agent_token,
            "user_code" => $playerToken,
            "game_type" => "slot",
            "start" => Carbon::parse($request->start)->format('Y-m-d H:i:s'),
            "end" => Carbon::parse($request->end)->format('Y-m-d H:i:s'),
            "page" => (int) $request->page - 1 ?: 0,
            "perPage" => (int) $request->length ?: 25,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(5)->post('https://api.nexusggr.com', $postArray);
            if ($response['status'] === 1) {
                $response = $response->json();
                return response()->json([
                    'recordsTotal' => $response['total_count'],
                    'recordsFiltered' => $response['total_count'],
                    'data' => $response['slot'],
                ]);
            }
        } catch (\Exception $e) {
            response()->json(['error' => $e->getMessage()]);
        }
    }
}
