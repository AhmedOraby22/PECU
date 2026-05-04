<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    public $timestamps = false;

    public const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'organization',
        'email',
        'phone',
        'category',
        'product',
        'quantity',
        'budget',
        'specs',
        'notes',
        'file_name',
        'status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
