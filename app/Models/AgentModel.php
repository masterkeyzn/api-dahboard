<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AgentModel extends Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (Auth::guard('admin')->check()) {
            $adminId = Auth::guard('admin')->id();
            $this->setConnection("mysql_agent_{$adminId}");
        }
    }
}
