<?php

namespace App\Classes;

class Festival
{
    public function __construct(
        public string $name,
        public string $city,
        public string $country,
        public string $languages,
        public string $webpage,
        public string $facebook,
        public string $email,
        public string $image,
        public string $yearMonth,
        public string $currentYearDate,
        public string $nextYearDate,
    ) {}
}
