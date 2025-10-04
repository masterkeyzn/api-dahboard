<?php

namespace App\Models\Moneysite;

use App\Models\AgentModel;

class UserBonusDeposit extends AgentModel
{
    protected $fillable = [
        'user_id',
        'bonusdeposit_id',
        'status',
        'claim_count',
        'deposit_amount',
        'bonus_amount',
        'achieved_turnover',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bonusdeposit()
    {
        return $this->belongsTo(Bonusdeposit::class);
    }
}
