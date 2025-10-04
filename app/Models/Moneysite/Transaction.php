<?php

namespace App\Models\Moneysite;

use App\Models\AgentModel;

class Transaction extends AgentModel
{
    protected $fillable = [
        'user_id',
        'type',
        'transaction_id',
        'amount',
        'recipient_bank_name',
        'recipient_account_number',
        'recipient_account_name',
        'sender_bank_name',
        'sender_account_number',
        'sender_account_name',
        'bonus_id',
        'note',
        'admin',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userReferral()
    {
        return $this->belongsTo(UserReferral::class, 'user_id');
    }
}
