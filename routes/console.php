<?php

use Illuminate\Support\Facades\Artisan;
use App\Services\FestivalStaticGenerator;

Artisan::command("fetchFestivals", function (
    FestivalStaticGenerator $generator,
) {
    $this->comment("Generating static festivals HTML...");
    $generator->generate();
    $this->info("Done! Static index.html updated.");
})
    ->purpose(
        "To fetch festivals from the Google Spreadsheet and regenerate a static home.html",
    )
    ->hourly();
