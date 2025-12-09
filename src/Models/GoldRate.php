<?php

namespace DenizTezc\TcmbGold\Models;

use Illuminate\Database\Eloquent\Model;

class GoldRate extends Model
{
    protected $table = 'gold_rates';

    protected $fillable = [
        'code',
        'name',
        'buying',
        'unit',
        'date',
    ];

    protected $casts = [
        'buying' => 'float',
        'unit' => 'integer',
        'date' => 'date',
    ];
}
