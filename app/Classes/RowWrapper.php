<?php

namespace App\Classes;

class RowWrapper
{
    public static function loadMappings(array $mappings)
    {
        return function ($row) use ($mappings) {
            return function ($key) use ($mappings, $row) {
                $columnIndex = $mappings[$key] ?? null;
                if (is_null($columnIndex)) {
                    return null;
                }

                return $row[$columnIndex] ?? null;
            };
        };
    }
}
