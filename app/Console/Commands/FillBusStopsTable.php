<?php

namespace App\Console\Commands;

use App\Enums\BusDirection;
use App\Enums\BusStopPosition;
use App\Models\BusStop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use MatanYadaev\EloquentSpatial\Objects\Point;
use PlinCode\KmlParser\KmlParser;
use RuntimeException;

class FillBusStopsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fill-bus-stops-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill bus stops table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('Downloading KMZ archive...');
        $kmzPath = $this->downloadKmz();

        $this->line('Loading KML document from KMZ archive...');
        $kml = \PlinCode\KmlParser\Facades\KmlParser::loadFromKmz($kmzPath);

        $this->line('Deleting KMZ archive...');
        File::delete($kmzPath);

        $this->line('Extracting data from KML...');
        $data = $this->extractData($kml);

        $this->line('Truncating table...');
        DB::table('bus_stops')->truncate();

        $this->line('Inserting data...');
        $this->fillTable($data);

        $this->getOutput()->newLine();

        $this->info('Filled bus_stops table with '.count($data).' records.');

        return static::SUCCESS;
    }

    protected function downloadKmz(): string
    {
        // see: https://www.transitchicago.com/data/
        $kmz = Http::get('https://data.cityofchicago.org/download/84eu-buny/application%2Fvnd.google-earth.kmz')
            ->body();

        $path = storage_path('app/private/cta-bus-stops.kmz');

        if (! File::put($path, $kmz)) {
            throw new RuntimeException('Error writing KMZ file');
        }

        return $path;
    }

    protected function extractData(KmlParser $kml): array
    {
        $data = [];

        foreach ($kml->getPlacemarks() as $index => $placemark) {
            $stopId = $this->extractFromHtml($placemark['description'], 'SYSTEMSTOP');

            if (! $stopId) {
                $this->warn("Failed to find system stop for index: $index");

                continue;
            }

            $direction = $this->extractFromHtml($placemark['description'], 'DIR');

            if (! $direction) {
                $this->warn("Failed to find direction for stop: $stopId");

                continue;
            }

            $position = $this->extractFromHtml($placemark['description'], 'POS');

            if (! $position) {
                $this->warn("Failed to find position for stop: $stopId");

                continue;
            }

            $name = $placemark['name'];
            $latitude = $placemark['coordinates']['latitude'] ?? false;
            $longitude = $placemark['coordinates']['longitude'] ?? false;

            if (! $latitude || ! $longitude) {
                $this->warn("Failed to find lat/lng for stop: $stopId");

                continue;
            }

            $data[] = [
                'stop_identifier' => $stopId,
                'name' => $name,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'direction' => BusDirection::from($direction),
                'position' => BusStopPosition::from($position),
            ];
        }

        return $data;
    }

    protected function fillTable(array $data): void
    {
        $this->withProgressBar($data, fn ($datum) => BusStop::create([
            'stop_identifier' => $datum['stop_identifier'],
            'name' => $datum['name'],
            'location' => new Point($datum['latitude'], $datum['longitude']),
            'direction' => $datum['direction'],
            'position' => $datum['position'],
        ]));
    }

    protected function extractFromHtml(string $html, string $identifier): ?string
    {
        preg_match("/<td>$identifier<\/td>\s*<td>(.+)<\/td>/", $html, $matches);

        return $matches[1] ?? null;
    }
}
