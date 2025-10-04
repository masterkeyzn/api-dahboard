<?php

namespace App\Models\Panel;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = ['title', 'link', 'permission', 'parent_id'];

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function scopeMainMenu($query)
    {
        return $query->whereNull('parent_id');
    }
}
