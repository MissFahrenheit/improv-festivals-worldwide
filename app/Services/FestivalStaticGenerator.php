<?php

namespace App\Services;

use App\Classes\Festival;
use App\Classes\YearCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Revolution\Google\Sheets\Facades\Sheets;

class FestivalStaticGenerator
{
    public function generate()
    {
        // Mapping URL-friendly slugs to actual sheet names in the spreadsheet
        $continentMappings = [
            "europe" => "EUROPE",
            "north-america" => "NORTH AMERICA",
            "south-america" => "SOUTH AMERICA",
            "asia" => "ASIA",
            "australia-pacific" => "AUSTRALASIA/PACIFIC",
        ];

        $allFestivals = [];

        foreach ($continentMappings as $key => $label) {
            $allFestivals[$key] = $this->fetchAndProcessFestivals($label);
        }

        // Render Blade to HTML
        $html = View::make("home", [
            "continents" => $continentMappings,
            "festivals" => $allFestivals,
        ])->render();

        // Save static HTML to /public/home.html
        File::put(public_path("index.html"), $html);
    }

    /**
     * Fetch and process festivals data from the Google Sheets.
     *
     * @param string $mappedContinent The mapped continent name.
     * @return array The processed festivals array.
     */
    private function fetchAndProcessFestivals($mappedContinent): ?array
    {
        // Fetch raw spreadsheet data
        $spreadsheetValues = Sheets::spreadsheet(env("FESTIVALS_GSHEET_ID"))
            ->sheet($mappedContinent)
            ->all();

        $now = Carbon::now();

        $festivals = [];

        foreach ($spreadsheetValues as $rowIndex => $row) {
            if ($rowIndex === 0) {
                continue;
            }

            if (empty($row["mm"])) {
                continue;
            }

            // there should be a check here, before doing the image fetching, for the scenario of festival in the past?
            $festival = $this->fetchRowAndCreateFestival($row, $now);

            // if ($yearMonth) {
            //     $festivalData["year-month"] = $yearMonth;
            //     // $festivalData["image"] = "";
            //     $festivalData["image"] = $this->getFestivalImage(
            //         $festivalData["webpage"] ?? null,
            //         $festivalData["facebook"] ?? null,
            //     );
            //     $festivals[$rowIndex] = $festivalData;
            // }
        }

        if (!empty($festivals)) {
            // Sort by year-month ascending
            $festivals = Arr::sort(
                $festivals,
                fn($festival) => $festival["year-month"],
            );
        }

        return $festivals;
    }

    private function fetchRowAndCreateFestival(
        array $row,
        Carbon $now,
    ): Festival {
        $currentYearDate = $this->getYearColumnValue($now->year, $row);
        $nextYearDate = $this->getYearColumnValue($now->addYear()->year, $row);
        $yearCase = $this->determineYearCase($currentYearDate, $nextYearDate);

        return new Festival(
            name: $row["festival-name"],
            city: $row["city"],
            country: $row["country"],
            languages: $row["languages"],
            webpage: $row["webpage"],
            facebook: $row["facebook"],
            email: $row["email"],
            image: $this->getFestivalImage($row["webpage"], $row["facebook"]),
            yearMonth: $this->getFestivalYearAndMonth(
                $row["mm"],
                $now,
                $yearCase,
            ),
            currentYearDate: $currentYearDate,
            nextYearDate: $nextYearDate,
        );
    }

    private function getYearColumnValue(int $year, array $row): ?string
    {
        // Check if year column has value
        $value = $row[$year] ?? null;

        if (empty($value)) {
            return null;
        }

        // If value contains numeric characters
        if (preg_match("/\d/", $value)) {
            return $value;
        }

        return null;
    }

    private function determineYearCase(
        ?int $currentYear,
        ?int $nextYear,
    ): YearCase {
        if (empty($currentYear) && empty($nextYear)) {
            return YearCase::ALL_EMPTY;
        }
        if (empty($currentYear)) {
            return YearCase::NEXT_YEAR_EXISTS;
        }

        if (empty($nextYear)) {
            return YearCase::CURRENT_YEAR_EXISTS;
        }

        return YearCase::BOTH_YEARS_EXIST;
    }

    /**
     * Determine if a festival should be included in the upcoming list,
     * and return the year-month (YYYY--MM) for sorting or display.
     *
     * @param int $monthNumber Current year.
     * @param Carbon $now Current datetime Carbon object
     * @param YearCase $yearCase enum with possible year column scenarios
     * @return string|null Returns YYYY-MM string if the festival is upcoming, null otherwise.
     */
    private function getFestivalYearAndMonth(
        int $monthNumber,
        Carbon $now,
        YearCase $yearCase,
    ): ?string {
        $getYearMonthFormat = fn(int $year, int $monthNumber) => Carbon::create(
            $year,
            $monthNumber,
            1,
        )->format("Y-m");

        $isFestivalMonthAfterCurrentMonth = $monthNumber >= $now->month;

        return match ($yearCase) {
            YearCase::ALL_EMPTY => null,
            YearCase::BOTH_YEARS_EXIST => $isFestivalMonthAfterCurrentMonth
                ? $getYearMonthFormat($now->year, $monthNumber)
                : $getYearMonthFormat($now->addYear()->year, $monthNumber),
            YearCase::CURRENT_YEAR_EXISTS => $isFestivalMonthAfterCurrentMonth
                ? $getYearMonthFormat($now->year, $monthNumber)
                : null,
            YearCase::NEXT_YEAR_EXISTS => $getYearMonthFormat(
                $now->addYear()->year,
                $monthNumber,
            ),
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
