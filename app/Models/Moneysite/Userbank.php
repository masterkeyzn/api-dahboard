<?php

namespace App\Models\Moneysite;

use App\Models\AgentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Userbank extends AgentModel
{

    protected $fillable = [
        'user_id',
        'type',
        'bank_name',
        'account_name',
        'account_number',
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
