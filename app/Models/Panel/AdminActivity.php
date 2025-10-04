<?php

namespace App\Models\Panel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminActivity extends Model
{
    protected $fillable = [
        'admin_id',
        'action_type',
        'target_type',
        'target_id',
        'description',
        'ip_address',
    ];

    /**
     * Relasi ke admin yang melakukan aksi
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Relasi polymorphic ke target entitas (user, bonus, transaction, dll)
     */
    public function target(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'target_type', 'target_id');
    }
}
