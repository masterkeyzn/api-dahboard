<?php

namespace App\Models\Moneysite;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\AgentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends AgentModel
{

    protected $fillable = [
        'username',
        'password',
        'email',
        'phone',
        'player_token',
        'ip',
        'status',
        'is_new_member',
        'can_login',
        'can_play_game',
        'is_playing',
        'active_balance',
    ];

    /**
     * Atribut yang disembunyikan saat serialisasi (misalnya JSON).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting tipe data kolom.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_new_member' => 'boolean',
            'can_login' => 'boolean',
            'can_play_game' => 'boolean',
            'is_playing' => 'boolean',
            'active_balance' => 'decimal:2',
        ];
    }

    public function userbank(): HasOne
    {
        return $this->hasOne(Userbank::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function userReferral(): HasOne
    {
        return $this->hasOne(UserReferral::class, 'user_id');
    }

    public function bonusdeposits()
    {
        return $this->hasMany(UserBonusDeposit::class);
    }
}
