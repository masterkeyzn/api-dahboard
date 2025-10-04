<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Moneysite\Referral;
use App\Models\Moneysite\UserReferral;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReferralController extends Controller
{
    public function index(Request $request)
    {
        $length = $request->input('length', 10);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $length;

        $query = Referral::query()
            ->select(
                'referrals.id as referral_id',
                'referrals.user_id',
                'referrals.referral_code',
                'referrals.id_card',
                'referrals.status',
                'referrals.referral_balance',
                'referrals.commission_ndp_type',
                'referrals.commission_ndp_value',
                'referrals.commission_rdp_type',
                'referrals.commission_rdp_value',
                'referrals.approved_at',
                'referrals.approved_by',
                'referrals.created_at',
                'users.username'
            )
            ->leftJoin('users', 'referrals.user_id', '=', 'users.id')
            ->withCount('referredUsers')
            ->with('approvedBy:id,username');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('users.username', 'like', "%{$search}%");
        }

        $filteredCount = (clone $query)->count();

        $referralList = $query
            ->offset($offset)
            ->limit($length)
            ->orderBy('referrals.created_at', 'desc')
            ->get();

        $total = Referral::count();

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filteredCount,
            'data' => $referralList,
        ]);
    }

    public function userList(Request $request, $id)
    {
        $referral = Referral::find($id);

        if (!$referral) {
            return response()->json(['message' => 'Referral not found'], 404);
        }

        $query = $referral->referredUsers()
            ->with([
                'user:id,username,created_at',
                'referral:id,user_id', // hanya ambil ID dan user_id dari referral
                'referral.user:id,username' // hanya ambil id & username dari user referral
            ]);

        // Filter by transaction status (optional)
        if ($request->filled('filter')) {
            if ($request->filter === 'with_transaction') {
                $query->whereHas('transactions', function ($q) {
                    $q->where('type', 'Deposit')->where('status', 'Approved');
                });
            } elseif ($request->filter === 'without_transaction') {
                $query->whereDoesntHave('transactions', function ($q) {
                    $q->where('type', 'Deposit')->where('status', 'Approved');
                });
            }
        }

        // Search by username (optional)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('username', 'like', "%$search%");
            });
        }

        // Filter by date range (optional)
        if ($request->filled('daterange') && is_array($request->daterange)) {
            try {
                $start = Carbon::parse($request->daterange[0], 'Asia/Jakarta')->startOfDay();
                $end = Carbon::parse($request->daterange[1], 'Asia/Jakarta')->endOfDay();
                $query->whereBetween('created_at', [$start, $end]);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Invalid date range'], 422);
            }
        }

        // Pagination
        $length = $request->input('length', 10);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $length;

        // Get total before pagination
        $total = $query->count();

        // Fetch data
        $data = $query->orderByDesc('created_at')
            ->skip($offset)
            ->take($length)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'referral_id' => $item->referral_id,
                    'user_id' => $item->user_id,
                    'first_deposit_at' => $item->first_deposit_at,
                    'first_deposit_amount' => $item->first_deposit_amount,
                    'ndp_commission' => $item->ndp_commission,
                    'total_deposit_count' => $item->total_deposit_count,
                    'rdp_commission_total' => $item->rdp_commission_total,
                    'commission_earned' => $item->commission_earned,
                    'created_at' => $item->created_at,
                    'referral' => [
                        'id' => optional($item->referral)->id,
                        'user' => optional(optional($item->referral)->user)->only(['id', 'username']),
                    ],
                    'user' => optional($item->user)->only(['id', 'username', 'created_at']),
                ];
            });

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data,
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $referral = Referral::find($id);

            if (!$referral) {
                return response()->json([
                    'success' => false,
                    'message' => 'Referral tidak ditemukan.'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'commission_ndp_type' => 'required|in:percent,idr',
                'commission_ndp_value' => 'required|numeric|min:0',
                'commission_rdp_type' => 'required|in:percent,idr',
                'commission_rdp_value' => 'required|numeric|min:0',
                'status' => 'required|in:active,suspended',
            ], [
                'commission_ndp_type.required' => 'Tipe komisi NDP wajib diisi.',
                'commission_ndp_type.in' => 'Tipe komisi NDP harus berupa "percent" atau "idr".',
                'commission_ndp_value.required' => 'Nilai komisi NDP wajib diisi.',
                'commission_ndp_value.numeric' => 'Nilai komisi NDP harus berupa angka.',
                'commission_rdp_type.required' => 'Tipe komisi RDP wajib diisi.',
                'commission_rdp_type.in' => 'Tipe komisi RDP harus berupa "percent" atau "idr".',
                'commission_rdp_value.required' => 'Nilai komisi RDP wajib diisi.',
                'commission_rdp_value.numeric' => 'Nilai komisi RDP harus berupa angka.',
                'status.required' => 'Status wajib diisi.',
                'status.in' => 'Status hanya bisa bernilai "active" atau "suspended".',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $referral->commission_ndp_type = $request->commission_ndp_type;
            $referral->commission_ndp_value = $request->commission_ndp_value;
            $referral->commission_rdp_type = $request->commission_rdp_type;
            $referral->commission_rdp_value = $request->commission_rdp_value;
            $referral->status = $request->status;

            $referral->save();

            return response()->json([
                'success' => true,
                'message' => 'Data referral berhasil diperbarui.',
                'data' => $referral
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function approveReferral(string $id)
    {
        try {
            $user = Auth::guard('admin')->user();

            if (!$user || !$user->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied: Only administrators can perform this action.'
                ], 403);
            }

            $referral = Referral::find($id);

            if (!$referral) {
                return response()->json([
                    'success' => false,
                    'message' => 'Referral not found.'
                ], 404);
            }

            // Prevent approving already active or suspended referrals
            if (in_array($referral->status, ['active', 'suspended'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Referral is already processed.'
                ], 400);
            }

            $referral->status = "active";
            $referral->approved_by = $user->id;
            $referral->approved_at = now();
            $referral->referral_code = rand(1000000000, 9999999999);

            $referral->save();

            return response()->json([
                'success' => true,
                'message' => 'Referral approved successfully.',
                'data' => $referral
            ]);
        } catch (\Exception $e) {
            Log::error("Error approving referral: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while approving the referral.'
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $user = Auth::guard('admin')->user();

            if (!$user || !$user->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied: Only administrators can perform this action.'
                ], 403);
            }

            $referral = Referral::find($id);

            if (!$referral) {
                return response()->json([
                    'success' => false,
                    'message' => 'Referral not found.',
                ], 404);
            }

            $referral->delete();

            return response()->json([
                'success' => true,
                'message' => 'Referral successfully deleted.',
            ]);
        } catch (\Exception $e) {
            Log::error("Error deleting referral: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the referral.'
            ], 500);
        }
    }


    public function bulkDelete(Request $request)
    {
        /** @var \App\Models\Panel\Admin|\Spatie\Permission\Traits\HasRoles $admin */
        $admin = Auth::guard('admin')->user();

        if (!$admin || !$admin->hasAnyRole(['Administrator', 'SuperAdmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied: Only administrators are allowed to perform this action.',
            ], 403);
        }

        $userReferralIds = $request->input('user_referral_ids');

        if (empty($userReferralIds)) {
            return response()->json(['error' => 'No IDs provided'], 400);
        }

        UserReferral::whereIn('id', $userReferralIds)->delete();

        return response()->json(['success' => true, 'message' => 'Referrals deleted successfully']);
    }

    public function deleteUserReferral(string $id)
    {

        /** @var \App\Models\Panel\Admin|\Spatie\Permission\Traits\HasRoles $admin */
        $admin = Auth::guard('admin')->user();

        if (!$admin || !$admin->hasAnyRole(['Administrator', 'SuperAdmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied: Only administrators are allowed to perform this action.',
            ], 403);
        }

        $referral = UserReferral::find($id);

        if (!$referral) {
            return response()->json([
                'message' => 'User Referral not found',
                'status' => 'error',
            ], 404);
        }

        $referral->delete();

        return response()->json([
            'success' => true,
            'message' => 'User Referral successfully deleted',
        ], 200);
    }
}
