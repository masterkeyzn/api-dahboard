<?php

namespace App\Models\Moneysite;

use App\Models\AgentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserReferral extends AgentModel
{

    protected $fillable = [
        'referral_id',
        'user_id',
        'first_deposit_at',
        'first_deposit_amount',
        'ndp_commission',
        'rdp_commission_total',
        'total_deposit_count',
        'commission_earned',
    ];

    protected $casts = [
        'first_deposit_at' => 'datetime',
        'first_deposit_amount' => 'decimal:2',
        'ndp_commission' => 'decimal:2',
        'rdp_commission_total' => 'decimal:2',
        'commission_earned' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class, 'referral_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id', 'user_id');
    }
}
