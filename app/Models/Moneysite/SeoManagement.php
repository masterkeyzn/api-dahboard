<?php

namespace App\Models\Moneysite;

use App\Models\AgentModel;

class SeoManagement extends AgentModel
{

    protected $fillable = [
        'robots',
        'meta_tag',
        'script_head',
        'script_body',
        'sitemap'
    ];
}
