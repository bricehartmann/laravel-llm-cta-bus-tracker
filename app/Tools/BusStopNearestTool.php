<?php

namespace App\Tools;

use App\Enums\BusDirection;
use App\Models\BusStop;
use Illuminate\Support\Facades\Http;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Prism\Prism\Tool;
use RuntimeException;

class BusStopNearestTool extends Tool
{
    public function __construct()
    {
        $this
            ->as('nearest_bus_stop')
            ->for('useful when you need to search for bus stops nearest to an address')
            ->withStringParameter(
                name: 'address',
                description: 'The address of the location to search from',
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

    public function __invoke(string $address, ?string $direction = null): string
    {
        if (! stristr($address, 'chicago')) {
            $address .= ', '.'Chicago, IL, USA';
        }

        $point = $this->geocodeAddress($address);

        $dir = $direction && defined(BusDirection::class.'::'.$direction)
            ? constant(BusDirection::class.'::'.$direction)
            : null;

        $nearestStop = BusStop::orderByDistance('location', $point)
            ->when($dir, fn ($query) => $query->where('direction', $dir->value))
            ->first();

        if (! $nearestStop) {
            throw new RuntimeException('Could not determine nearest bus stop');
        }

        return view('prompts.bus-stop-search-results', [
            'stops' => [$nearestStop],
        ])->render();
    }

    protected function geocodeAddress(string $address): Point
    {
        $response = Http::get(config('services.opencagedata.url'), [
            'key' => config('services.opencagedata.key'),
            'q' => $address,
            'countrycode' => 'us',
            'proximity' => '41.881832,-87.623177', // center of chicago
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to geocode address');
        }

        $lat = $response->json('results.0.geometry.lat');
        $lng = $response->json('results.0.geometry.lng');

        if (! $lat || ! $lng) {
            throw new RuntimeException('Unable to find lat/lng from results');
        }

        return new Point($lat, $lng);
    }
}
