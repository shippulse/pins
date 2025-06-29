<?php

namespace Obelaw\Shippulse\Pins\Console\Commands;

use function Laravel\Prompts\select;
use Illuminate\Console\Command;
use Obelaw\Shippulse\Pins\Services\ImportPinService;

class ImportPinsCommand extends Command
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'shippulse:pin:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import geographical pin data from a JSON file.';

    public function handle(): void
    {
        $pinsDir = __DIR__ . '/../../../pins/';
        $jsonFiles = glob($pinsDir . '*.json');

        if (empty($jsonFiles)) {
            $this->error("No JSON files found in the '{$pinsDir}' directory.");
            return;
        }

        $file = select(
            label: 'Select files?',
            options: array_map(function ($filename) {
                return basename($filename);
            }, $jsonFiles),
        );

        $import = ImportPinService::import($pinsDir . $file);

        if ($import) {
            $this->info("Successfully imported pins from '{$file}'.");
        } else {
            $this->error("Failed to import pins from '{$file}'. Check logs for details.");
        }
    }
}
