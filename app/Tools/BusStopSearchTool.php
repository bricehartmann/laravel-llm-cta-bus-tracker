<?php

namespace App\Tools;

use App\Enums\BusDirection;
use App\Models\BusStop;
use Prism\Prism\Tool;

class BusStopSearchTool extends Tool
{
    public function __construct()
    {
        $this
            ->as('search_bus_stops')
            ->for('useful when you need to search for bus stops by name')
            ->withStringParameter(
                name: 'name',
                description: 'The name of the bus stop; typically cross streets.',
            )
            ->withEnumParameter(
                name: 'direction',
                description: 'The travel direction of the bus stop.',
                options: collect(BusDirection::cases())
                    ->map(fn ($direction) => $direction->name)
                    ->all(),
                required: false
            )
            ->using($this);
    }

    public function __invoke(string $name, ?string $direction = null): string
    {
        $dir = $direction && defined(BusDirection::class.'::'.$direction)
            ? constant(BusDirection::class.'::'.$direction)
            : null;

        $search = str_replace(' and ', ' & ', $name);

        $busStops = BusStop::search($search)
            ->when($dir, fn ($query) => $query->where('direction', $dir->value))
            ->take($dir ? 3 : 6)
            ->get();

        return view('prompts.bus-stop-search-results', [
            'stops' => $busStops,
        ])->render();
    }
}
