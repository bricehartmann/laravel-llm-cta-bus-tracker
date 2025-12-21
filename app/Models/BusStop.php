<?php

namespace App\Models;

use App\Enums\BusDirection;
use App\Enums\BusStopPosition;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class BusStop extends Model
{
    use HasSpatial;
    use Searchable;

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

    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'direction' => $this->direction->value,
        ];
    }
}
