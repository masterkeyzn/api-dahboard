<?php

namespace App\Models\Panel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * App\Models\Panel\Admin
 *
 * @property int $id
 * @property string $username
 * @property string $password
 * @property int|null $admin_credential_id
 * @property int|null $created_by
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Permission\Models\Role[] $roles
 * @property-read \Illuminate\Support\Collection $permissions
 * @method static \Illuminate\Database\Eloquent\Builder|Admin query()
 */
/**
 * @method bool save(array $options = [])
 */

class Admin extends Authenticatable
{
    use HasRoles, HasApiTokens, HasFactory;

    protected $guard_name = 'admin';
    protected $connection = 'mysql';

    protected $fillable = [
        'username',
        'password',
        'max_transaction',
        'admin_credential_id',
        'created_by',
    ];

    protected $hidden = [
        'created_by',
        'password',
    ];

    public function credential()
    {
        return $this->belongsTo(AdminCredential::class, 'admin_credential_id');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function createdAdmins()
    {
        return $this->hasMany(Admin::class, 'created_by');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasAnyRole(['Administrator', 'SuperAdmin']);
    }

    public function isSuperMarketing(): bool
    {
        return $this->hasRole('SuperMarketing');
    }
}
