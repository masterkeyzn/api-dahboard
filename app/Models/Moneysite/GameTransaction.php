<?php

namespace App\Models\Moneysite;

use App\Models\AgentModel;

class GameTransaction extends AgentModel
{

    protected $fillable = [
        'status',
        'msg',
        'agent_code',
        'agent_balance',
        'agent_type',
        'user_code',
        'user_balance',
        'deposit_amount',
        'currency',
        'order_no',
        'admin_id',
        'action_by',
        'action_note',
    ];
}
