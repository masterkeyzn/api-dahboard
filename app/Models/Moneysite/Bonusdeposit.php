<?php

namespace App\Models\Moneysite;

use App\Models\AgentModel;

class Bonusdeposit extends AgentModel
{
    protected $fillable = [
        'name',
        'type',
        'category',
        'condition_type',
        'amount',
        'max_bonus',
        'max_claims',
        'min_deposit',
        'target_turnover',
        'description'
    ];

    public function users()
    {
        return $this->hasMany(UserBonusDeposit::class);
    }
}
