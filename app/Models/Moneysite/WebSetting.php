<?php
namespace App\Models\Moneysite;

use App\Models\AgentModel;

class WebSetting extends AgentModel
{

    protected $fillable = [
        'site_name',
        'site_title',
        'marquee',
        'site_logo',
        'popup',
        'sc_livechat',
        'url_livechat',
        'proggressive_img',
        'themes',
        'favicon',
        'min_deposit',
        'max_deposit',
        'min_withdrawal',
        'max_withdrawal',
        'unique_code',
        'is_maintenance',
    ];
}
