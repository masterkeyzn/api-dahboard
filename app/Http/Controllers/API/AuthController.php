<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Moneysite\ApiGame;
use App\Models\Moneysite\Transaction;
use App\Models\Moneysite\User;
use App\Models\Moneysite\WebSetting;
use App\Models\Panel\Admin;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException as ExceptionRequestException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'username' => 'required|string|max:255',
                'password' => 'required|string|min:4',
            ],
            [
                'username.required' => 'Username is required, please provide your username.',
                'username.string'   => 'Username must be a valid string.',
                'username.max'      => 'Username cannot be longer than 255 characters.',
                'password.required' => 'Password is required, please provide your password.',
                'password.string'   => 'Password must be a valid string.',
                'password.min'      => 'Password must be at least 4 characters long.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages()->first(),
            ], 400);
        }

        $admin = Admin::where('username', $request->username)->first();

        if (! $admin || ! Hash::check($request->password, $admin->password)) {
            return response()->json([
                'error' => 'Invalid credentials',
            ], 401);
        }

        $admin->tokens()->delete();

        $token = $admin->createToken('Admin Access Token')->plainTextToken;

        $adminCredential = $admin->credential;

        if (! $adminCredential) {
            return response()->json([
                'error' => 'Admin credentials not found',
            ], 404);
        }

        Cache::put("admin_{$admin->id}", [
            'username'          => $admin->username,
            'agent_code'        => $adminCredential->agent_code,
            'agent_token'       => $adminCredential->agent_token,
            'pusher_key'        => $adminCredential->pusher_key,
            'pusher_app_id'     => $adminCredential->pusher_app_id,
            'pusher_secret'     => $adminCredential->pusher_secret,
            'database_host'     => $adminCredential->database_host,
            'database_port'     => $adminCredential->database_port,
            'database_name'     => $adminCredential->database_name,
            'database_username' => $adminCredential->database_username,
            'database_password' => $adminCredential->database_password,
            'token'             => $token,
        ], now()->addDay());

        $menus = $this->getAccessibleMenus($admin);

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'admin'   => [
                'username'    => $admin->username,
                'role'        => $admin->getRoleNames()->first(),
                'agent_code'  => $adminCredential->agent_code,
                'agent_token' => $adminCredential->agent_token,
                'pusher_key'  => $adminCredential->pusher_key,
            ],
            'menu'    => $menus,
        ], 200);
    }

    private function getAccessibleMenus($admin, $parentId = null)
    {
        $menus = DB::table('menus')
            ->where('parent_id', $parentId)
            ->get();

        $accessibleMenus = [];

        foreach ($menus as $menu) {
            if ($menu->permission && ! $admin->can($menu->permission)) {
                continue;
            }

            $menuData = [
                'title'      => $menu->title,
                'link'       => $menu->link,
                'permission' => $menu->permission,
                'children'   => $this->getAccessibleMenus($admin, $menu->id),
            ];

            $accessibleMenus[] = $menuData;
        }

        return $accessibleMenus;
    }

    public function authAdmin()
    {
        /** @var \App\Models\Panel\Admin|\Spatie\Permission\Traits\HasRoles $admin */

        $admin = auth()->guard('admin')->user();

        if (! $admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $credential  = $admin->credential ?? null;
        $webSettings = WebSetting::first();

        return response()->json([
            'username'    => $admin->username,
            'role'        => $admin->getRoleNames()->first() ?? 'No Role',
            'agent_code'  => $credential?->agent_code,
            'agent_token' => $credential?->agent_token,
            'pusher_key'  => $credential?->pusher_key,
            'logo'        => $webSettings?->site_logo,
        ]);
    }

    public function resetPasswordAdmin(Request $request)
    {
        $validated = $request->validate([
            'newPassword' => 'required|string|min:8',
        ]);

        try {
            /** @var \App\Models\Panel\Admin $admin */
            $admin = Auth::guard('admin')->user();

            $admin->password = bcrypt($request->newPassword);
            $admin->save();

            $admin->tokens->each(function ($token) {
                $token->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diubah! Anda telah logout, silakan login dengan password baru.',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah password: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function agentStatistic()
    {
        $totalUser    = User::count();
        $totalNewUser = User::whereDoesntHave('transactions', function ($query) {
            $query->where('type', 'Deposit')
                ->where('status', 'Approved');
        })->count();
        $totalDeposit  = Transaction::where('type', 'Deposit')->where('status', 'Approved')->sum('amount');
        $totalWithdraw = Transaction::where('type', 'Withdrawal')->where('status', 'Approved')->sum('amount');
        $totalWinLose  = $totalDeposit - $totalWithdraw;

        return response()->json([
            'success' => true,
            'data'    => [
                'total_user'     => $totalUser,
                'total_new_user' => $totalNewUser,
                'total_deposit'  => $totalDeposit,
                'total_withdraw' => $totalWithdraw,
                'total_win_lose' => $totalWinLose,
            ],
        ], 200);
    }

    public function adminIndex()
    {
        $admin = Admin::where('created_by', Auth::guard('admin')->user()->id)
            ->with('roles')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $admin,
        ]);
    }

    public function createAdmin(Request $request)
    {
        $user = Auth::guard('admin')->user();

        if (! $user || (! $user->isSuperAdmin() && ! $user->isSuperMarketing())) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        if ($user->isSuperMarketing() && $request->level !== 'SuperMarketing') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied: SuperMarketing can only create SuperMarketing role.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'username'       => 'required|string|max:255|alpha_dash|unique:admins,username',
            'password'       => 'required|string|min:8',
            'maxTransaction' => 'required|numeric|min:0',
            'level'          => 'required|in:SuperAdmin,Admin,CustomerService,SuperMarketing',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error'   => $validator->errors(),
            ], 400);
        }

        try {
            $newAdmin = Admin::create([
                'username' => "{$user->admin_credential_id}{$request->username}",
                'password'            => Hash::make($request->password),
                'max_transaction'     => $request->maxTransaction,
                'admin_credential_id' => $user->admin_credential_id,
                'created_by'          => $user->id,
            ]);

            $newAdmin->assignRole($request->level);

            return response()->json([
                'success' => true,
                'message' => 'Admin registered successfully!',
                'data'    => $newAdmin,
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Database error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateAdmin(Request $request)
    {
        $user = Auth::guard('admin')->user();

        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied: Only administrators can perform this action.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'username' => "required|string|max:255|unique:admins,username,{$request->id}",
            'maxTransaction' => 'required|string',
            'level'          => 'required|in:SuperAdmin,Admin,CustomerService',
            'password'       => 'nullable|string|min:6',
            'credential_id'  => 'nullable|exists:admin_credentials,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ], 400);
        }

        try {
            $admin = Admin::findOrFail($request->id);

            $admin->update([
                'username'        => $request->username,
                'max_transaction' => $request->maxTransaction,
            ]);

            if ($request->filled('password')) {
                $admin->password = Hash::make($request->password);
                $admin->save();
            }

            if ($request->filled('credential_id')) {
                $admin->credential_id = $request->credential_id;
                $admin->save();
            }

            if ($request->filled('level')) {
                $admin->syncRoles([$request->level]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Admin updated successfully!',
                'data'    => $admin,
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Database error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteAdmin(string $id)
    {

        $user = Auth::guard('admin')->user();

        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied: Only administrators can perform this action.',
            ], 403);
        }

        try {
            $admin = Admin::findOrFail($id);

            $admin->delete();

            return response()->json([
                'success' => true,
                'message' => 'Admin deleted successfully!',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Admin not found.',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'An error occurred while deleting the admin. Please try again.',
            ], 500);
        }
    }

    public function apiCredential()
    {
        $user = Auth::guard('admin')->user();

        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied: Only administrators can perform this action.',
            ], 403);
        }

        try {
            $api = ApiGame::first();

            if (! $api) {
                return response()->json([
                    'success' => false,
                    'message' => 'API credentials not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $api,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching API credentials.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function updateApi(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'agent_code'  => 'required|string|max:255',
                'agent_token' => 'required|string|max:255',
                'api_url'     => 'required|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $api = ApiGame::first();

            if (! $api) {
                return response()->json([
                    'success' => false,
                    'message' => 'API credentials not found.',
                ], 404);
            }

            $admin = Auth::guard('admin')->user()->credential;

            $adminUpdated = false;
            if ($admin && ($admin->agent_code !== $request->agent_code || $admin->agent_token !== $request->agent_token)) {
                $admin->agent_code  = $request->agent_code;
                $admin->agent_token = $request->agent_token;
                $admin->save();
                $adminUpdated = true;
            }

            $apiUpdated = false;
            if ($api->agent_code !== $request->agent_code || $api->agent_token !== $request->agent_token || $api->api_url !== $request->api_url) {
                $api->agent_code  = $request->agent_code;
                $api->agent_token = $request->agent_token;
                $api->api_url     = $request->api_url;
                $api->save();
                $apiUpdated = true;
            }

            if (! $adminUpdated && ! $apiUpdated) {
                return response()->json([
                    'success' => true,
                    'message' => 'No changes detected, nothing was updated.',
                ], 200);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'API credentials updated successfully!',
                'data'    => $api,
            ], 200);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating API credentials.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function resetUserBalance()
    {

        try {
            $response = ApiTransactions::resetUserBalance();

            $responseBody = $response['data'];
            $responseJson = json_decode($responseBody, true);
            if (isset($responseJson['status']) && $responseJson['status'] === 1) {
                return response()->json([
                    'success' => true,
                    'message' => 'User balances reset successfully.',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $responseJson['message'] ?? 'An error occurred during the reset.',
                ], 500);
            }
        } catch (ExceptionRequestException $e) {

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while contacting the API.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function refreshMigration()
    {
        $user = Auth::guard('admin')->user();

        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied: Only administrators can perform this action.',
            ], 403);
        }

        $adminId        = $user->id;
        $connectionName = "mysql_agent_{$adminId}";
        config(['seeder.admin_expired' => $user->credential->expired]);

        try {
            Artisan::call(
                'migrate:fresh',
                [
                    '--path'     => 'database/migrations/moneysite',
                    '--database' => $connectionName,
                    '--force'    => true,
                    '--seed'     => true,
                ]
            );

            $this->purgeCachePattern("*");

            return response()->json([
                'success' => true,
                'message' => 'Migration completed successfully!',
            ], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Migration failed: ' . $e->getMessage()], 500);
        }
    }

    public function webInformations()
    {
        try {
            $exp = WebSetting::select('is_maintenance')->first();

            $response = ApiTransactions::getBalance();

            if (! ($response['success'] ?? false)) {
                Log::error('Agent Info API failed', ['response' => $response]);

                return response()->json([
                    'error'   => 'API responded with failure',
                    'message' => $response['message'] ?? 'Unknown error',
                ], 400);
            }

            $data = $response['data'];

            $agentBalance = $data['agent_balance'] ?? 0;
            $userList     = $data['user_list'] ?? [];

            $totalUserBalance = collect($userList)->sum('user_balance');

            return response()->json([
                'total_balance_agent' => $agentBalance,
                'total_balance_user'  => $totalUserBalance,
                'is_maintenance'      => $exp?->is_maintenance,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch webInformations', ['error' => $e->getMessage()]);

            return response()->json([
                'error'   => 'Request failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function purgeCachePattern(string $pattern): void
    {
        $admin       = Auth::guard('admin')->user();
        $fullPattern = "{$admin->credential->redis_prefix}{$pattern}";

        config(['database.redis.server_admin' => [
            'host'     => $admin->credential->redis_host,
            'password' => $admin->credential->redis_password,
            'port'     => $admin->credential->redis_port,
            'database' => 1,
        ]]);

        $client = Redis::connection('server_admin')->client();

        $cursor       = null;
        $deletedCount = 0;

        do {
            $keys = $client->scan($cursor, $fullPattern, 100);
            if ($keys !== false && ! empty($keys)) {
                $deletedCount += $client->del($keys);
            }
        } while ($cursor !== 0);

        Log::info("Deleted keys", ['pattern' => $fullPattern, 'count' => $deletedCount]);
    }

}
