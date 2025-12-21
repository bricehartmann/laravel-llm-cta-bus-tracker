<?php

namespace App\Console\Commands;

use App\Enums\BusDirection;
use App\Enums\BusStopPosition;
use App\Tools\BusStopNearestTool;
use App\Tools\BusStopNextArrivalTool;
use App\Tools\BusStopSearchTool;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Enums\ToolChoice;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

use function Laravel\Prompts\spin;

class BusFinder extends Command implements PromptsForMissingInput
{
    protected const string MODEL = 'claude-sonnet-4-5-20250929';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:bus-finder {query}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find a bus stop or next bus';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        spin(
            callback: function () use (&$formatted) {
                $response = Prism::structured()
                    ->using(Provider::Anthropic, static::MODEL)
                    ->withMaxSteps(3)
                    ->withProviderOptions(['use_tool_calling' => true])
                    ->withSchema($this->getOutputSchema())
                    ->withSystemPrompt(view('prompts.bus-finder-structured'))
                    ->withPrompt($this->argument('query'))
                    ->withTools([
                        Tool::make(BusStopSearchTool::class),
                        Tool::make(BusStopNearestTool::class),
                        Tool::make(BusStopNextArrivalTool::class),
                    ])
                    ->withToolChoice(ToolChoice::Any)
                    ->asStructured();

                $formatted = Prism::text()
                    ->using(Provider::Anthropic, static::MODEL)
                    ->withSystemPrompt(view('prompts.bus-finder-formatted', ['data' => $response->structured]))
                    ->withPrompt($this->argument('query'))
                    ->asText();
            },
            message: 'thinking...',
        );

        $this->info($formatted->text);

        return static::SUCCESS;
    }

    protected function getOutputSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'bus_finder_results',
            description: 'Results from searching for a next bus and/or bus stop.',
            properties: [
                new StringSchema(
                    name: 'bus_stop_name',
                    description: 'The name of the bus stop',
                    nullable: true,
                ),
                new EnumSchema(
                    name: 'bus_direction',
                    description: 'The direction of the bus',
                    options: collect(BusDirection::cases())
                        ->map(fn ($direction) => $direction->name)
                        ->all(),
                    nullable: true,
                ),
                new EnumSchema(
                    name: 'bus_stop_position',
                    description: 'The position of the bus stop relative to the street',
                    options: collect(BusStopPosition::cases())
                        ->map(fn ($position) => $position->name)
                        ->all(),
                    nullable: true,
                ),
                new NumberSchema(
                    name: 'bus_arrival_minutes',
                    description: 'When the next bus will arrive at a specific stop, in minutes',
                    nullable: true,
                    minimum: 0,
                ),
            ],
        );
    }
}
