<?php

namespace App\Tools;

use Illuminate\Support\Facades\Http;
use Prism\Prism\Tool;
use RuntimeException;

class BusStopNextArrivalTool extends Tool
{
    public function __construct()
    {
        $this
            ->as('next_bus_arrival')
            ->for('useful when you need to determine when the next bus will arrive at a specific stop')
            ->withStringParameter(
                name: 'bus_stop_id',
                description: 'The ID of the bus stop',
            )
            ->using($this);
    }

    public function __invoke(string $bus_stop_id): string
    {
        return $this->predictNextBus($bus_stop_id).' minutes';
    }

    protected function predictNextBus(string $busStopId): int
    {
        $response = Http::get(config('services.ctabustracker.url') . '/getpredictions', [
            'key' => config('services.ctabustracker.key'),
            'stpid' => $busStopId,
            'format' => 'json',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to predict next bus');
        }

        $minutes = $response->json('bustime-response.prd.0.prdctdn');

        if ($minutes === null) {
            throw new RuntimeException('Unable to find next bus prediction from results');
        }

        return strtolower($minutes) === 'due' ? 0 : (int) $minutes;
    }
}
