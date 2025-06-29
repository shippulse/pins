<?php

namespace Obelaw\Shippulse\Pins\Services;

use Illuminate\Support\Facades\Log;
use Obelaw\Shippulse\Pins\Models\Pin;

class ImportPinService
{
    /**
     * Imports geographical data from a JSON file into the 'pins' table.
     *
     * @param string $file The absolute path to the JSON file.
     * @return bool True on success, false on failure.
     */
    public static function import($file)
    {
        $jsonContent = file_get_contents($file);

        if ($jsonContent === false) {
            Log::error("ImportPin: Failed to read file: {$file}");
            return false;
        }

        $countries = json_decode($jsonContent);

        if ($countries === null && json_last_error() !== JSON_ERROR_NONE) {
            Log::error("ImportPin: Failed to decode JSON from file: {$file}. Error: " . json_last_error_msg());
            return false;
        }

        if (!is_array($countries)) {
             Log::error("ImportPin: JSON content is not an array of countries: {$file}");
             return false;
        }

        foreach ($countries as $country) {
            // Create country
            $countryPin = self::createPin(null, $country, 'country');

            if ($countryPin && isset($country->cities) && is_array($country->cities)) {
                // Process cities
                foreach ($country->cities as $city) {
                    $cityPin = self::createPin($countryPin->id, $city, 'city');

                    if ($cityPin && isset($city->states) && is_array($city->states)) {
                        // Process states
                        foreach ($city->states as $state) {
                            $statePin = self::createPin($cityPin->id, $state, 'state');

                            if ($statePin && isset($state->areas) && is_array($state->areas)) {
                                // Process areas
                                foreach ($state->areas as $area) {
                                    self::createPin($statePin->id, $area, 'area');
                                }
                            } else if ($statePin && (!isset($state->areas) || !is_array($state->areas))) {
                                Log::warning("ImportPin: Expected 'areas' array for state '{$state->name}' (ID: {$statePin->id}) but not found or not array.");
                            }
                        }
                    } else if ($cityPin && (!isset($city->states) || !is_array($city->states))) {
                         Log::warning("ImportPin: Expected 'states' array for city '{$city->name}' (ID: {$cityPin->id}) but not found or not array.");
                    }
                }
            } else if ($countryPin && (!isset($country->cities) || !is_array($country->cities))) {
                 Log::warning("ImportPin: Expected 'cities' array for country '{$country->name}' (ID: {$countryPin->id}) but not found or not array.");
            }
        }

        return true; // Indicate success
    }

    /**
     * Helper method to create a Pin record using firstOrCreate.
     *
     * @param int|null $parentId The parent pin ID.
     * @param object $itemData The data object (e.g., country, city, state, area) - must have a 'name' property.
     * @param string $type The type of pin ('country', 'city', 'state', 'area').
     * @return Pin|null The created or existing Pin model, or null on failure.
     */
    private static function createPin(?int $parentId, object $itemData, string $type): ?Pin
    {
        if (!isset($itemData->name)) {
            Log::warning("ImportPin: Skipping creation of {$type} pin: 'name' property missing in item data.", (array)$itemData);
            return null;
        }

        try {
            return Pin::firstOrCreate([
                'parent_id' => $parentId,
                'name' => $itemData->name,
                'type' => $type,
            ]);
        } catch (\Exception $e) {
            Log::error("ImportPin: Error creating {$type} pin '{$itemData->name}' (Parent ID: {$parentId}): " . $e->getMessage(), ['exception' => $e]);
            return null;
        }
    }
}