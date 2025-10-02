<?php

namespace App\Classes;

use Carbon\CarbonImmutable;

class Festival
{
    public function __construct(
        public string $name,
        public string $city,
        public string $country,
        public string $languages,
        public ?string $webpage,
        public ?string $facebook,
        public ?string $email,
        public ?string $image,
        public CarbonImmutable $yearMonth,
        public string $date,
        public Continent $continent,
    ) {}
}
