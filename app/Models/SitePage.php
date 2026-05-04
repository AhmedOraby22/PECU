<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SitePage extends Model
{
    protected $fillable = [
        'slug',
        'nav_label',
        'title',
        'summary',
        'body',
        'image',
        'links',
        'sort_order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'active' => 'boolean',
            'links' => 'array',
        ];
    }
}
