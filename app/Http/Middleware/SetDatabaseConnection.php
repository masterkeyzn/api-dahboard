<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use PDO;

class SetDatabaseConnection
{
    public function handle(Request $request, Closure $next)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) return response()->json(['error' => 'Session expired. Please log in again.'], 401);

        $cacheKey = "admin_{$admin->id}";
        $connectionName = "mysql_agent_{$admin->id}";

        if (!array_key_exists($connectionName, config('database.connections'))) {
            $databaseConfig = Cache::get($cacheKey);

            if (!$databaseConfig) {
                return response()->json(['error' => 'Missing DB config.'], 401);
            }

            config([
                "database.connections.{$connectionName}" => [
                    'driver' => 'mysql',
                    'host' => $databaseConfig['database_host'],
                    'port' => $databaseConfig['database_port'],
                    'database' => $databaseConfig['database_name'],
                    'username' => $databaseConfig['database_username'],
                    'password' => $databaseConfig['database_password'],
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'options' => [
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET time_zone = "+07:00"',
                    ],
                ],
            ]);
        }

        return $next($request);
    }
}
