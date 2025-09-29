# Improv Festivals Worldwide

Turning the community-maintained **Improv Festivals Spreadsheet** into a live, browsable website.


## About

Hi! 👋 I’m **Elvira Lingris**, co-founder of [Radical Elements](https://radical-elements.com) and a front-end developer.
For several years, I was also an improviser, and a proud member of **[QUAKE](https://www.facebook.com/groups/336042448891209)**, an Athens-based improv collective that organized jams, workshops, and collaboration sessions.

Back then, beginners and experienced improvisers alike often had no idea the **[Improv Festivals Worldwide spreadsheet](https://docs.google.com/spreadsheets/d/1uIyvbpZsPtWmJZJwSlAEG22A8qcozKmK9WuxRYSIOSY/edit?gid=0#gid=0)** even existed, and they’d ask us where to find information about upcoming festivals.

I’ve wanted to turn that spreadsheet into a website for years. I started it once and only recently found the time to finish it.

The beauty of this project is that the **community already keeps the spreadsheet up to date**, so there’s no new database, no admin panel, and no maintenance burden. Just fetch the data and display it.

This project is **open source**, because I see it as a community effort. Anyone can fork it, customize it, and host it. No attribution required.


## Hosting

Currently, the site is hosted as a demo at:

👉 [improv-festivals.radical-elements.com](https://improv-festivals.radical-elements.com)

My hope is that another (or many!) developer(s) from the improv community will pick it up, host it under a proper domain (👀 improv-festivals.com is available at the time of writing), and keep it alive.

Feel free to:

* Change the design, play with colors (see `/resources/css/colors.css` for some extras),
* Or rebuild it completely and make it your own.


## Technical Details

This project is built with **Laravel 12** (requires **PHP 8.2+**).

### Dependencies

* [`google/apiclient` ^2.0](https://github.com/googleapis/google-api-php-client)
* [`revolution/laravel-google-sheets` ^7.0](https://github.com/invokable/laravel-google-sheets)

### Frontend

* No JS framework. Just a little **vanilla JavaScript** for tab navigation and image fallback handling.
* No CSS framework (though spacing and flex classes borrow syntax from Bootstrap for convenience).
* Uses **npm** and **Vite** for asset bundling (optional, can be adjusted for simpler setups).

### How It Works

1. An **Artisan command** (`php artisan fetchFestivals`) fetches data from the spreadsheet and formats it.

   * Logic lives in `/app/Services/FestivalStaticGenerator.php`.
2. The data is injected into Blade templates (`/resources/views/home.blade.php` and `/resources/views/partials/festivals.blade.php`).
3. The result is saved as **static HTML** (`index.html`) in the `/public` directory.
4. The `/` route serves this static file directly, for maximum speed.
5. A **scheduled task** (see `/routes/console.php`) runs the command hourly to keep the site fresh.

   * Requires that **queue workers** are enabled on your server.


## Contributing

This is a small token of gratitude to the improv community, which has shaped my life in many ways.\
If you’d like to extend or host it, please do!

Questions?\
Need setup help?\
📧 [elvira@radical-elements.com](mailto:elvira@radical-elements.com)
