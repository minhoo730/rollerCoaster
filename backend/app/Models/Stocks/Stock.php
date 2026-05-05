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
        'sector'
    ];
}