<?php

namespace Obelaw\Shippulse\Pins;

class Mapper
{
    /**
     * Collection of registered mapper files
     */
    private static array $mappers = [];

    /**
     * Add a mapper file to the collection
     *
     * @param string $file Path to the mapper JSON file
     */
    public static function addMapper(string $file): void
    {
        if (!in_array($file, self::$mappers)) {
            self::$mappers[] = $file;
        }
    }

    /**
     * Get all registered mapper files
     *
     * @return array
     */
    public static function getMappers(): array
    {
        return self::$mappers;
    }

    /**
     * Check if any mappers are registered
     *
     * @return bool
     */
    public static function hasMappers(): bool
    {
        return !empty(self::$mappers);
    }

    /**
     * Get the count of registered mappers
     *
     * @return int
     */
    public static function count(): int
    {
        return count(self::$mappers);
    }

    /**
     * Clear all registered mappers
     */
    public static function clearMappers(): void
    {
        self::$mappers = [];
    }

    /**
     * Load and validate a mapper file
     *
     * @param string $file Path to the mapper file
     * @return array Parsed mapper data
     * @throws \RuntimeException If file cannot be read or parsed
     */
    public static function loadMapper(string $file): array
    {
        if (!file_exists($file)) {
            throw new \RuntimeException("Mapper file not found: {$file}");
        }

        $content = file_get_contents($file);
        if ($content === false) {
            throw new \RuntimeException("Failed to read mapper file: {$file}");
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in mapper file: " . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new \RuntimeException("Mapper file must contain a JSON object");
        }

        return $data;
    }
}
