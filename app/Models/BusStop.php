<?php

namespace App\Models;

use App\Enums\BusDirection;
use App\Enums\BusStopPosition;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class BusStop extends Model
{
    use HasSpatial;

    protected $fillable = [
        'stop_identifier',
        'name',
        'location',
        'direction',
        'position',
    ];

    protected $casts = [
        'location' => Point::class,
        'direction' => BusDirection::class,
        'position' => BusStopPosition::class,
    ];
}
