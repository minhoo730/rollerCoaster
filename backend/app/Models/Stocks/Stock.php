<?php

namespace App\Models\Stocks;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'stocks';

    protected $fillable = [
        'code',
        'name',
        'market',
        'venue',
        'sector',
        'is_active',
        'source',
        'raw_payload',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'raw_payload' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }
}
