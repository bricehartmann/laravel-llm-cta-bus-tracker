<?php

namespace App\Models;

use App\Enums\BusDirection;
use App\Enums\BusStopPosition;
use Illuminate\Database\Eloquent\Model;

class BusStop extends Model
{
    protected $fillable = [
        'stop_identifier',
        'name',
        'location',
        'direction',
        'position',
    ];

    protected $casts = [
        'direction' => BusDirection::class,
        'position' => BusStopPosition::class,
    ];
}
