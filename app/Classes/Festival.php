<?php

namespace App\Classes;

class Festival
{
    public function __construct(
        public string $festivalName,
        public string $city,
        public string $country,
        public int $mm,
        public string $languages,
        public string $webpage,
        public string $facebook,
        public string $email,
        public string $yearMonth,
        public string $currentYearDate,
        public string $nextYearDate,
    ) {}
}
