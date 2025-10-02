<?php

namespace App\Classes;

class MappedRow
{
    private function __construct(public array $row) {}

    private static array $mappings = [];

    public static function loadMappings(array $mappings)
    {
        self::$mappings = $mappings;
    }

    public static function loadRow(array $row)
    {
        return new self($row);
    }

    public function get($key)
    {
        $columnIndex = self::$mappings[$key] ?? null;
        if ($columnIndex === null) {
            return null;
        }

        return $this->row[$columnIndex] ?? null;
    }
}
