<?php

namespace App\Models\Moneysite;

use App\Models\AgentModel;

class Promotion extends AgentModel
{
    protected $fillable = [
        'title',
        'cdnImages',
        'category',
        'endDate',
        'description',
    ];
}
