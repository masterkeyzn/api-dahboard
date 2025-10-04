<?php
namespace App\Models\Panel;

use Illuminate\Database\Eloquent\Model;

class AdminCredential extends Model
{
    protected $fillable = [
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
        'redis_password',
        'redis_port',
        'redis_prefix',
        'expired',
    ];

    protected $casts = [
        'expired' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_credential_id');
    }
}
