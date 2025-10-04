<?php

namespace App\Models\Moneysite;

use App\Models\AgentModel;

class Domain extends AgentModel
{
    protected $fillable = ['name', 'custom_title', 'meta_tag'];
}
