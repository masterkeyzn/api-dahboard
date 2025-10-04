<?php

namespace App\Models\Moneysite;

use App\Models\AgentModel;

class SocialMedia extends AgentModel
{
    protected $fillable = [
        'title',
        'link',
        'icon',
        'description',
    ];
}
