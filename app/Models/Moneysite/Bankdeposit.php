<?php

namespace App\Models\Moneysite;

use App\Models\AgentModel;

class Bankdeposit extends AgentModel
{
    protected $fillable = [
        'type',
        'bank_name',
        'account_name',
        'account_number',
        'min_deposit',
        'max_deposit',
        'unique_code',
        'qris_img',
        'show_form',
        'status_bank',
        'show_bank'
    ];
}
