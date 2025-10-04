<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Panel\Admin;
use App\Models\Panel\AdminCredential;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use PDO;
use Spatie\Permission\Models\Role;

class AgentController extends Controller
{
    public function index()
    {
        $agents = Admin::whereNull('created_by')
            ->orderBy('id', 'desc')
            ->select(
                'id',
                'username',
                'max_transaction',
                'created_by',
                'admin_credential_id'
            )
            ->with('roles')
            ->with('credential')
            ->get();

        $agents = $agents->map(function ($agent) {
            $role       = $agent->roles->first();
            $credential = $agent->credential;

            return [
                'id'                  => $agent->id,
                'admin_credential_id' => $credential->id,
                'username'            => $agent->username,
                'max_transaction'     => $agent->max_transaction,
                'database_host'       => $credential->database_host,
                'database_port'       => $credential->database_port,
                'database_name'       => $credential->database_name,
                'database_username'   => $credential->database_username,
                'database_password'   => $credential->database_password,
                'role'                => $role ? $role->name : '',
                'pusher_key'          => $credential->pusher_key,
                'pusher_app_id'       => $credential->pusher_app_id,
                'pusher_secret'       => $credential->pusher_secret,
                'agent_code'          => $credential->agent_code,
                'agent_token'         => $credential->agent_token,
                'expired'             => $credential->expired,
                'created_by'          => $agent->created_by,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $agents,
        ]);
    }

    public function agentStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_credential_id' => 'required|exists:admin_credentials,id',
            'username'            => 'required|string|unique:admins,username|max:255',
            'password'            => 'required|string|min:4',
            'role'                => 'required|string|exists:roles,name',
            'max_transaction'     => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $admin = Admin::create([
                'admin_credential_id' => $request->admin_credential_id,
                'created_by'          => Auth::guard('admin')->id() ?? null,
                'username'            => $request->username,
                'password'            => Hash::make($request->password),
                'max_transaction'     => $request->max_transaction,
            ]);

            $admin->assignRole($request->role);

            return response()->json([
                'success' => true,
                'message' => 'Admin created and role assigned successfully!',
                'data'    => $admin,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating admin.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $admin = Admin::find($id);

        if (! $admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin not found.',
            ], 404);
        }

        $admin->delete();

        return response()->json([
            'success' => true,
            'message' => 'Admin deleted successfully.',
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'admin_credential_id' => 'required|exists:admin_credentials,id',
            'username'            => "required|string|max:255|unique:admins,username,$id",
            'password'            => 'nullable|string|min:6',
            'role'                => 'required|string|exists:roles,name',
            'max_transaction'     => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $admin = Admin::findOrFail($id);

            $admin->update([
                'admin_credential_id' => $request->admin_credential_id,
                'username'            => $request->username,
                'max_transaction'     => $request->max_transaction,
                'password'            => $request->password ? Hash::make($request->password) : $admin->password,
            ]);

            $admin->syncRoles([$request->role]);

            return response()->json([
                'success' => true,
                'message' => 'Admin updated and role updated successfully!',
                'data'    => $admin,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating admin.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function purgeCacheKey(string $key, string $prefix): void
    {
        try {

            $fullKey = "{$prefix}{$key}";

            $redis   = Redis::connection('moneysite')->client();
            $deleted = $redis->del($fullKey);

            if ($deleted > 0) {
            } else {
                Log::warning("Cache key not found or already deleted", ['key' => $fullKey]);
            }
        } catch (Exception $e) {
            Log::error("Cache purge error", ['error' => $e->getMessage()]);
        }
    }

    public function indexCredential()
    {
        $dataCredential = AdminCredential::all();

        return response()->json([
            'success' => true,
            'data'    => $dataCredential,
        ]);
    }

    public function storeCredential(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pusher_key'        => 'required|string|max:255',
            'pusher_app_id'     => 'required|string|max:255',
            'pusher_secret'     => 'required|string|max:255',
            'agent_token'       => 'required|string|max:255',

            'database_host'     => 'required|string|max:255',
            'database_port'     => 'required|string|max:10',
            'database_name'     => 'required|string|max:255',
            'database_username' => 'required|string|max:255',
            'database_password' => 'required|string|max:255',

            'agent_code'        => 'required|string|max:255',

            'redis_host'        => 'required|string|max:255',
            'redis_port'        => 'nullable|string|max:10',
            'redis_password'    => 'nullable|string|max:255',
            'redis_prefix'      => 'required|string|max:255',
            'type'              => 'required|in:OLD,NEW',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            // set default kalau tidak dikirim
            $redisPort     = $request->input('redis_port', '6379');
            $redisPassword = $request->input('redis_password', 'kmzwayxx');
            $expiredAt     = $request->filled('expired')
            ? Carbon::parse($request->expired)
            : null;

            $adminCredential = AdminCredential::create([
                'pusher_key'        => $request->pusher_key,
                'pusher_app_id'     => $request->pusher_app_id,
                'pusher_secret'     => $request->pusher_secret,
                'agent_token'       => $request->agent_token,

                'database_host'     => $request->database_host,
                'database_port'     => $request->database_port,
                'database_name'     => $request->database_name,
                'database_username' => $request->database_username,
                'database_password' => $request->database_password,

                'agent_code'        => $request->agent_code,

                'redis_host'        => $request->redis_host,
                'redis_password'    => $redisPassword,
                'redis_port'        => $redisPort,
                'redis_prefix'      => $request->redis_prefix,

                'expired'           => $expiredAt, // nullable
            ]);

            if ($request->type === 'OLD') {
                return response()->json([
                    'message' => 'Admin registered and migration skipped for OLD type.',
                    'data'    => $adminCredential,
                ], 201);
            }

            $connectionName = "mysql_agent_{$adminCredential->id}";

            config([
                "database.connections.{$connectionName}" => [
                    'driver'    => 'mysql',
                    'host'      => $adminCredential->database_host,
                    'port'      => $adminCredential->database_port,
                    'database'  => $adminCredential->database_name,
                    'username'  => $adminCredential->database_username,
                    'password'  => $adminCredential->database_password,
                    'charset'   => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'options'   => [
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET time_zone = "+07:00"',
                    ],
                ],
            ]);

            DB::purge($connectionName);

            try {
                DB::connection($connectionName)->getPdo();
            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot connect to target database.',
                    'error'   => $e->getMessage(),
                ], 500);
            }

            Artisan::call('migrate', [
                '--path'     => 'database/migrations/moneysite',
                '--database' => $connectionName,
                '--force'    => true,
                '--seed'     => true,
            ]);

            return response()->json([
                'message' => 'Admin registered and migration completed successfully!',
                'data'    => $adminCredential,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating admin credential.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function updateCredential(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'pusher_key'        => 'sometimes|required|string|max:255',
            'pusher_app_id'     => 'sometimes|required|string|max:255',
            'pusher_secret'     => 'sometimes|required|string|max:255',
            'agent_token'       => 'sometimes|required|string|max:255',

            'database_host'     => 'sometimes|required|string|max:255',
            'database_port'     => 'sometimes|required|string|max:10',
            'database_username' => 'sometimes|required|string|max:255',
            'database_password' => 'sometimes|required|string|max:255',
            'database_name'     => 'sometimes|required|string|max:255',

            'agent_code'        => 'sometimes|required|string|max:255',

            'redis_host'        => 'sometimes|required|string|max:255',
            'redis_port'        => 'sometimes|nullable|string|max:10',
            'redis_password'    => 'sometimes|nullable|string|max:255',
            'redis_prefix'      => 'sometimes|required|string|max:255',

            'expired'           => 'sometimes|nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $adminCredential = AdminCredential::findOrFail($id);

            $data = $request->only([
                'pusher_key',
                'pusher_app_id',
                'pusher_secret',
                'agent_token',
                'database_host',
                'database_port',
                'database_name',
                'database_username',
                'database_password',
                'agent_code',
                'redis_host',
                'redis_port',
                'redis_password',
                'redis_prefix',
            ]);

            if ($request->has('expired')) {
                $data['expired'] = $request->filled('expired')
                ? Carbon::parse($request->input('expired'))
                : null;
            }

            $adminCredential->fill($data)->save();

            return response()->json([
                'success' => true,
                'message' => 'Admin credential updated successfully!',
                'data'    => $adminCredential,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Admin credential not found.',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating admin credential.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteCredential($id)
    {
        try {
            $adminCredential = AdminCredential::findOrFail($id);

            $adminCredential->delete();

            return response()->json([
                'success' => true,
                'message' => 'Admin credential deleted successfully!',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Admin credential not found.',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting admin credential.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function rolesIndex()
    {
        $user = auth('admin')->user();

        $roles = ($user->hasRole('SuperMarketing')) ? Role::where('name', 'SuperMarketing')->get() : Role::all();

        return response()->json([
            'success' => true,
            'data'    => $roles,
        ]);
    }
}
