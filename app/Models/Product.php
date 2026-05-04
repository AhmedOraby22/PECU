<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public $timestamps = false;

    public const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'name_en',
        'category',
        'price',
        'image',
        'description',
        'stock',
        'featured',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'featured' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
