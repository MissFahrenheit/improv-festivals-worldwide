<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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

        foreach ($continentMappings as $key => $label) {
            $allFestivals[$key] = $this->fetchAndProcessFestivals($label);
        }

        // Render Blade to HTML
        $html = View::make("home", [
            "continents" => $continentMappings,
            "festivals" => $allFestivals,
        ])->render();

        // Save static HTML to /public/home.html
        File::put(public_path("home.html"), $html);
    }

    /**
     * Fetch and process festivals data from the Google Sheets.
     *
     * @param string $mappedContinent The mapped continent name.
     * @return array The processed festivals array.
     */
    private function fetchAndProcessFestivals($mappedContinent)
    {
        // Fetch raw spreadsheet data
        $spreadsheetValues = Sheets::spreadsheet(env("FESTIVALS_GSHEET_ID"))
            ->sheet($mappedContinent)
            ->all();

        $currentYear = Carbon::now()->year;
        $nextYear = $currentYear + 1;
        $currentMonth = Carbon::now()->month;

        $allowedKeys = [
            "festival-name",
            "city",
            "country",
            "mm",
            "languages",
            "webpage",
            "facebook",
            "email",
            (int) $currentYear,
            (int) $nextYear,
        ];

        $festivals = [];
        $spreadsheetColumns = array_map(
            fn($column) => Str::slug($column),
            $spreadsheetValues[0],
        );

        foreach ($spreadsheetValues as $rowIndex => $row) {
            if ($rowIndex === 0) {
                continue;
            }

            $festivalData = [];
            foreach ($row as $columnIndex => $attribute) {
                // Skip if this column index doesn’t exist in headers
                if (!isset($spreadsheetColumns[$columnIndex])) {
                    continue;
                }
                $columnTitle = $spreadsheetColumns[$columnIndex];

                // Skip if the header is empty
                if (empty($columnTitle)) {
                    continue;
                }

                // Store spreadsheet values: cast "mm" (month) column to int, keep all other allowed columns as strings
                if (in_array($columnTitle, $allowedKeys)) {
                    $festivalData[$columnTitle] =
                        $columnTitle === "mm" ? (int) $attribute : $attribute;
                }
            }

            $yearMonth = $this->getFestivalYearAndMonth(
                $festivalData,
                $currentYear,
                $nextYear,
                $currentMonth,
            );
            if ($yearMonth) {
                $festivalData["year-month"] = $yearMonth;
                // $festivalData["image"] = "";
                $festivalData["image"] = $this->getFestivalImage(
                    $festivalData["webpage"] ?? null,
                    $festivalData["facebook"] ?? null,
                );
                $festivals[$rowIndex] = $festivalData;
            }
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
    /**
     * Determine if a festival should be included in the upcoming list,
     * and return the year-month (YYYY--MM) for sorting or display.
     *
     * @param array $festivalData Festival row data from the spreadsheet.
     * @param int $currentYear Current year.
     * @param int $nextYear Next year.
     * @param int $currentMonth Current month (1-12).
     * @return string|null Returns YYYY-MM string if the festival is upcoming, null otherwise.
     */
    private function getFestivalYearAndMonth(
        array $festivalData,
        int $currentYear,
        int $nextYear,
        int $currentMonth,
    ): ?string {
        $festivalMonth = $festivalData["mm"] ?? null;
        if (!$festivalMonth) {
            return null;
        } // skip if no month info

        // Check current year column
        $currentYearValue = $festivalData[$currentYear] ?? null;
        if (
            $currentYearValue &&
            preg_match("/\d/", $currentYearValue) &&
            $festivalMonth >= $currentMonth
        ) {
            return Carbon::create($currentYear, $festivalMonth, 1)->format(
                "Y-m",
            );
        }

        // Check next year column
        $nextYearValue = $festivalData[$nextYear] ?? null;
        if ($nextYearValue && preg_match("/\d/", $nextYearValue)) {
            return Carbon::create($nextYear, $festivalMonth, 1)->format("Y-m");
        }

        // Past festival, skip
        return null;
    }

    /**
     * Fetch the OG image from the festival's webpage or fallback to Facebook.
     *
     * @param string|null $webpage The URL of the festival's webpage.
     * @param string|null $facebook The URL of the festival's Facebook page.
     * @return string|null The URL of the image, or a default image if not found.
     */
    protected function getFestivalImage($webpage, $facebook)
    {
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
    protected function fetchOgImage($url)
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
    protected function fetchFacebookImage($facebookUrl)
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
    protected function extractFacebookId($facebookUrl)
    {
        $parts = explode("/", rtrim($facebookUrl, "/"));
        return end($parts);
    }
}
