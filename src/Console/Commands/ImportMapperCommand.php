<?php

namespace Obelaw\Shippulse\Pins\Console\Commands;

use Illuminate\Console\Command;
use Obelaw\Shippulse\Pins\Mapper;
use Obelaw\Shippulse\Pins\Models\Pin;

class ImportMapperCommand extends Command
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'shippulse:mapper:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import geographical pin data from a JSON file.';

    public function handle(): void
    {
        $this->info('Starting mapper import...');
        
        $totalProcessed = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        try {
            $mappers = Mapper::getMappers();
            
            if (empty($mappers)) {
                $this->error('No mapper files found.');
                return;
            }

            foreach ($mappers as $mapperFile) {
                $this->info("Processing mapper file: " . basename($mapperFile));
                
                $stats = $this->processMapperFile($mapperFile);
                
                $totalProcessed += $stats['processed'];
                $totalUpdated += $stats['updated'];
                $totalSkipped += $stats['skipped'];
                $totalErrors += $stats['errors'];
            }

            $this->displaySummary($totalProcessed, $totalUpdated, $totalSkipped, $totalErrors);
            
        } catch (\Exception $e) {
            $this->error("Import failed: {$e->getMessage()}");
            if ($this->output->isVerbose()) {
                $this->line($e->getTraceAsString());
            }
        }
    }

    /**
     * Process a single mapper file
     */
    private function processMapperFile(string $mapperFile): array
    {
        $stats = ['processed' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

        try {
            $data = $this->loadMapperData($mapperFile);
            
            if (!isset($data['for']) || !isset($data['data'])) {
                throw new \InvalidArgumentException("Mapper file must contain 'for' and 'data' fields");
            }

            $provider = $data['for'];
            $mappingData = $data['data'];
            
            $this->line("  Provider: {$provider}");
            
            foreach ($mappingData as $type => $pins) {
                if (!is_array($pins)) {
                    $this->warn("  Skipping invalid data for type: {$type}");
                    continue;
                }

                $typeStats = $this->processPinsByType($type, $pins, $provider);
                
                foreach ($typeStats as $key => $value) {
                    $stats[$key] += $value;
                }
            }
            
        } catch (\Exception $e) {
            $this->error("Error processing mapper file {$mapperFile}: {$e->getMessage()}");
            $stats['errors']++;
        }

        return $stats;
    }

    /**
     * Load and validate mapper data from file
     */
    private function loadMapperData(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Mapper file not found: {$filePath}");
        }

        $fileContent = file_get_contents($filePath);
        
        if ($fileContent === false) {
            throw new \RuntimeException("Failed to read mapper file: {$filePath}");
        }

        $data = json_decode($fileContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException("Invalid JSON in mapper file: " . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new \InvalidArgumentException("Mapper file must contain a JSON object");
        }

        return $data;
    }

    /**
     * Process pins by type
     */
    private function processPinsByType(string $type, array $pins, string $provider): array
    {
        $stats = ['processed' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
        
        $pinType = $this->mapDataTypeToModelType($type);
        
        $this->line("  Processing {$type} -> {$pinType} (" . count($pins) . " items)");

        foreach ($pins as $pinName => $pinValue) {
            $stats['processed']++;
            
            try {
                $result = $this->updatePinMapper($pinName, $pinType, $pinValue, $provider);
                
                if ($result === 'updated') {
                    $stats['updated']++;
                    if ($this->output->isVerbose()) {
                        $this->line("    ✓ Updated: {$pinName} -> {$pinValue}");
                    }
                } elseif ($result === 'not_found') {
                    $stats['skipped']++;
                    if ($this->output->isVerbose()) {
                        $this->line("    - Skipped: {$pinName} (pin not found)");
                    }
                } else {
                    $stats['skipped']++;
                    if ($this->output->isVerbose()) {
                        $this->line("    - Skipped: {$pinName} (no change needed)");
                    }
                }
                
            } catch (\Exception $e) {
                $stats['errors']++;
                $this->error("    ✗ Error updating pin '{$pinName}': {$e->getMessage()}");
            }
        }

        return $stats;
    }

    /**
     * Update a pin's mapper data
     */
    private function updatePinMapper(string $pinName, string $pinType, string $pinValue, string $provider): string
    {
        $pin = Pin::where('name', $pinName)->where('type', $pinType)->first();
        
        if (!$pin) {
            return 'not_found';
        }

        // Get current mapper data
        $currentMapper = $pin->mapper ?? [];
        
        // Check if the value has actually changed
        if (isset($currentMapper[$provider]) && $currentMapper[$provider] === $pinValue) {
            return 'no_change';
        }

        // Update the mapper data
        if (method_exists($pin, 'setMapper')) {
            // Use the model's setMapper method if available
            $pin->setMapper($provider, $pinValue);
        } else {
            // Fallback to direct property update
            $pin->mapper = array_merge($currentMapper, [$provider => $pinValue]);
            $pin->save();
        }

        return 'updated';
    }

    /**
     * Map data type to model type
     */
    private function mapDataTypeToModelType(string $dataType): string
    {
        $typeMapping = [
            'countries' => 'country',
            'cities' => 'city',
            'states' => 'state',
            'areas' => 'area',
        ];

        return $typeMapping[$dataType] ?? strtolower($dataType);
    }

    /**
     * Display import summary
     */
    private function displaySummary(int $processed, int $updated, int $skipped, int $errors): void
    {
        $this->newLine();
        $this->info('Import Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', number_format($processed)],
                ['Successfully Updated', number_format($updated)],
                ['Skipped', number_format($skipped)],
                ['Errors', number_format($errors)],
            ]
        );

        if ($errors > 0) {
            $this->warn('Some pins could not be updated. Use -v (verbose) flag for detailed information.');
        } else {
            $this->info('Mapper import completed successfully!');
        }
    }
}
