<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Improv Festivals Worldwide</title>
        <meta name="description" content="An open-source project that transforms a shared spreadsheet into a live directory of improv festivals around the world.">

        <meta property="og:title" content="Improv Festivals Worldwide" />
        <meta property="og:description" content="An open-source project that transforms a shared spreadsheet into a live directory of improv festivals around the world.">
        <meta property="og:type" content="website" />
        <meta property="og:url" content="{{ url()->current() }}" />
        <meta property="og:image" content="/improv_festivals_worldwide.png" />

        <meta name="twitter:card" content="website">
        <meta name="twitter:title" content="Improv Festivals Worldwide">
        <meta name="twitter:description" content="An open-source project that transforms a shared spreadsheet into a live directory of improv festivals around the world.">
        <meta name="twitter:image" content="/improv_festivals_worldwide.png">

        <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
        <link rel="shortcut icon" href="/favicon.ico" />
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
        <meta name="apple-mobile-web-app-title" content="Radical Elements" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="manifest" href="/site.webmanifest" />

        <!-- Fonts -->
        <link rel="preload" href="{{ Vite::asset('resources/fonts/Inter/Inter24pt-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="{{ Vite::asset('resources/fonts/Inter/Inter24pt-Medium.woff2') }}" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="{{ Vite::asset('resources/fonts/Inter/Inter24pt-Bold.woff2') }}" as="font" type="font/woff2" crossorigin>

        @vite('resources/css/app.css')
        @vite('resources/js/app.js')

    </head>

    <body>

        <header class="text-center mb-4 mb-lg-5 pt-3 px-2">
            <h1>Improv Festivals Worldwide</h1>
            <p><strong>Improvisers are awesome.</strong><br>They maintain a <a href="https://docs.google.com/spreadsheets/d/1uIyvbpZsPtWmJZJwSlAEG22A8qcozKmK9WuxRYSIOSY/edit" target="_blank" title="Improv Festivals Worlwide Google Sheet">shareable spreadsheet</a> of most improv festivals in the world.<br>This <a href="https://github.com/MissFahrenheit/improv-festivals-worldwide" target="_blank" title="Improv Festivals Worldwide Project Github page">open-source project</a> ✨magically✨ turns that spreadsheet into a website (refreshes hourly).</p>
        </header>

        <main>
            <div class="flex align-items-center justify-content-center px-2 px-lg-4 mb-4">
                <nav class="flex flex-wrap justify-content-center align-items-center py-2 gap-2 px-lg-3">
                    @foreach ($continents as $continent)
                        <button type="button" class="py-2 px-3 pill tab {{ $continent->id === 'europe' ? 'active' : '' }}" data-continent="{{ $continent->id }}" id="{{ $continent->id }}">
                            {{ Str::title($continent->label) }}
                        </button>
                    @endforeach
                </nav>
            </div>

            <div class="pt-3 pt-lg-4 pb-5">
                @foreach ($continents as $continent)
                    <div id="{{ $continent->id }}-content" data-content-continent="{{ $continent->id }}" style="{{ $continent->id !== 'europe' ? 'display:none' : '' }}">
                        {{-- @include('partials.skeleton') --}}
                        @include('partials.festivals', [
                            'festivals' => array_filter(
                                $festivals,
                                fn($festival) => $festival->continent->id === $continent->id
                            )
                        ]);
                    </div>
                @endforeach
            </div>
        </main>

        <footer class="text-center pb-1">
            <small>This is an open-source project. Find it on <a href="https://github.com/MissFahrenheit/improv-festivals-worldwide" target="_blank" title="Improv Festivals Worldwide Project Github page">GitHub</a>, fork it, go wild.</small>
        </footer>

    </body>
</html>
