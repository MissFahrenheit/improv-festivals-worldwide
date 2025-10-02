<?php

namespace App\Services;

use App\Classes\Continent;
use App\Classes\Festival;
use App\Classes\MappedRow;
use App\Classes\YearCase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Revolution\Google\Sheets\Facades\Sheets;

class FestivalStaticGenerator
{
    public function generate()
    {
        // Mapping URL-friendly slugs to actual sheet names in the spreadsheet
        $continents = [
            new Continent("europe", "EUROPE"),
            new Continent("north-america", "NORTH AMERICA"),
            new Continent("south-america", "SOUTH AMERICA"),
            new Continent("asia", "ASIA"),
            new Continent("australia-pacific", "AUSTRALASIA/PACIFIC"),
        ];

        $festivals = array_reduce(
            $continents,
            fn($allFestivals, Continent $continent) => array_merge(
                $allFestivals,
                $this->fetchAndProcessFestivals($continent),
            ),
            [],
        );

        // Render Blade to HTML
        $html = View::make("home", [
            "continents" => $continents,
            "festivals" => $festivals,
        ])->render();

        // Save static HTML to /public/home.html
        File::put(public_path("index.html"), $html);
    }

    /**
     * Fetch and process festivals data from the Google Sheets.
     *
     * @param Continent $continent
     * @return array The processed festivals array.
     */
    private function fetchAndProcessFestivals(Continent $continent): ?array
    {
        // Fetch raw spreadsheet data
        $spreadsheetRows = Sheets::spreadsheet(env("FESTIVALS_GSHEET_ID"))
            ->sheet($continent->label)
            ->all();

        $now = CarbonImmutable::now();

        $columnIndexmappings = $this->createColumnIndexMappings(
            $spreadsheetRows[0],
            $now,
        );

        MappedRow::loadMappings($columnIndexmappings);

        return collect($spreadsheetRows)
            ->slice(1)
            ->map(fn($row) => MappedRow::loadRow($row))
            ->filter(fn($mappedRow) => !empty($mappedRow->get("mm"))) // Filter out row with empty MM column
            ->map(
                fn($row) => $this->fetchRowAndCreateFestival(
                    $row,
                    $now,
                    $continent,
                ),
            )
            ->filter(fn(?Festival $festival) => !empty($festival))
            ->sortBy(
                fn(Festival $festival) => $festival->yearMonth->format("Y-m"),
            )
            ->values()
            ->toArray();
    }

    private function createColumnIndexMappings(
        array $columnTitles,
        CarbonImmutable $now,
    ): array {
        $slugifiedColumnTitles = array_map(Str::slug(...), $columnTitles);
        $mappings = [];
        $allowedKeys = [
            "festival-name",
            "city",
            "country",
            "mm",
            "languages",
            "webpage",
            "facebook",
            "email",
            $now->year,
            $now->addYear()->year,
        ];

        foreach ($slugifiedColumnTitles as $columnIndex => $slugifiedTitle) {
            if (!in_array($slugifiedTitle, $allowedKeys)) {
                continue;
            }

            $mappings[$slugifiedTitle] = $columnIndex;
        }
        return $mappings;
    }

    private function fetchRowAndCreateFestival(
        MappedRow $row,
        CarbonImmutable $now,
        Continent $continent,
    ): ?Festival {
        $currentYearDate = $this->getYearColumnValue($now->year, $row);
        $nextYearDate = $this->getYearColumnValue($now->addYear()->year, $row);
        $yearCase = $this->determineYearCase($currentYearDate, $nextYearDate);

        $yearMonth = $this->getFestivalYearAndMonth(
            $row->get("mm"),
            $now,
            $yearCase,
        );

        if (empty($yearMonth)) {
            return null;
        }

        return new Festival(
            name: $row->get("festival-name"),
            city: $row->get("city"),
            country: $row->get("country"),
            languages: $row->get("languages"),
            webpage: $row->get("webpage"),
            facebook: $row->get("facebook"),
            email: $row->get("email"),
            image: $this->getFestivalImage(
                $row->get("webpage"),
                $row->get("facebook"),
            ),
            yearMonth: $yearMonth,
            date: $this->getFestivalClosestDate(
                $currentYearDate,
                $nextYearDate,
                $yearMonth,
                $now,
            ),
            continent: $continent,
        );
    }

    private function getFestivalClosestDate(
        ?string $currentYearDate,
        ?string $nextYearDate,
        CarbonImmutable $yearMonth,
        CarbonImmutable $now,
    ): string {
        if ($yearMonth->year == $now->year) {
            return $currentYearDate;
        }

        return $nextYearDate;
    }

    private function getYearColumnValue(int $year, MappedRow $row): ?string
    {
        // Check if year column has value and return null if not
        $value = $row->get($year) ?? null;

        if (empty($value)) {
            return null;
        }

        // Return only if the date column value for said year contains numeric characters
        if (preg_match("/\d/", $value)) {
            return $value;
        }

        return null;
    }

    private function determineYearCase(
        ?string $currentYear,
        ?string $nextYear,
    ): YearCase {
        if (empty($currentYear) && empty($nextYear)) {
            return YearCase::ALL_EMPTY;
        }
        if (empty($currentYear)) {
            return YearCase::NEXT_YEAR_ONLY;
        }

        if (empty($nextYear)) {
            return YearCase::CURRENT_YEAR_ONLY;
        }

        return YearCase::BOTH_YEARS_EXIST;
    }

    /**
     * Determine if a festival should be included in the upcoming list,
     * and return the year-month (YYYY--MM) for sorting or display.
     *
     * @param int $monthNumber Current year.
     * @param CarbonImmutable $now Current datetime CarbonImmutable object
     * @param YearCase $yearCase enum with possible year column scenarios
     * @return string|null Returns YYYY-MM string if the festival is upcoming, null otherwise.
     */
    private function getFestivalYearAndMonth(
        int $monthNumber,
        CarbonImmutable $now,
        YearCase $yearCase,
    ): ?CarbonImmutable {
        $getYearMonthFormat = fn(
            int $year,
            int $monthNumber,
        ) => CarbonImmutable::create($year, $monthNumber, 1);

        $isFestivalMonthAfterCurrentMonth = $monthNumber >= $now->month;

        return match ($yearCase) {
            YearCase::ALL_EMPTY => null,
            YearCase::CURRENT_YEAR_ONLY => $isFestivalMonthAfterCurrentMonth
                ? $getYearMonthFormat($now->year, $monthNumber)
                : null,
            YearCase::NEXT_YEAR_ONLY => $getYearMonthFormat(
                $now->addYear()->year,
                $monthNumber,
            ),
            YearCase::BOTH_YEARS_EXIST => $isFestivalMonthAfterCurrentMonth
                ? $getYearMonthFormat($now->year, $monthNumber)
                : $getYearMonthFormat($now->addYear()->year, $monthNumber),
        };
    }

    /**
     * Fetch the OG image from the festival's webpage or fallback to Facebook.
     *
     * @param string|null $webpage The URL of the festival's webpage.
     * @param string|null $facebook The URL of the festival's Facebook page.
     * @return string|null The URL of the image, or a default image if not found.
     */
    protected function getFestivalImage(
        ?string $webpage,
        ?string $facebook,
    ): ?string {
        if (empty($webpage) && empty($facebook)) {
            return null;
        }

        // Try fetching the OG image from the webpage
        if (!empty($webpage)) {
            $image = $this->fetchOgImage($webpage);
            if ($image) {
                return $image;
            }
        }

        // If no OG image found, try fetching the Facebook image
        if (!empty($facebook)) {
            $image = $this->fetchFacebookImage($facebook);
            if ($image) {
                return $image;
            }
        }

        return null;
    }

    /**
     * Fetch the OG image from the webpage.
     *
     * @param string $url The URL of the webpage.
     * @return string|null The OG image URL or null if not found.
     */
    protected function fetchOgImage(string $url): ?string
    {
        try {
            $response = Http::get($url);

            if ($response->successful()) {
                $html = $response->body();
                preg_match(
                    '/<meta property="og:image" content="([^"]+)"/i',
                    $html,
                    $matches,
                );
                return $matches[1] ?? null;
            }
        } catch (\Exception $e) {
            // Handle exceptions
        }

        return null;
    }

    /**
     * Fetch the Facebook image from the Facebook Graph API.
     *
     * @param string $facebookUrl The URL of the Facebook page or event.
     * @return string|null The Facebook image URL or null if not found.
     */
    protected function fetchFacebookImage(string $facebookUrl): ?string
    {
        $facebookId = $this->extractFacebookId($facebookUrl);

        if ($facebookId) {
            try {
                $response = Http::get(
                    "https://graph.facebook.com/{$facebookId}/picture?type=large&access_token=" .
                        env("FACEBOOK_ACCESS_TOKEN"),
                );

                if ($response->successful()) {
                    return (string) $response->effectiveUri(); // The redirected URL is the image URL
                }
            } catch (\Exception $e) {
                // Handle exceptions
            }
        }

        return null;
    }

    /**
     * Extract the Facebook ID from the URL.
     *
     * @param string $facebookUrl The URL of the Facebook page or event.
     * @return string|null The Facebook ID or null if not found.
     */
    protected function extractFacebookId(string $facebookUrl): ?string
    {
        $parts = explode("/", rtrim($facebookUrl, "/"));
        return end($parts);
    }
}
