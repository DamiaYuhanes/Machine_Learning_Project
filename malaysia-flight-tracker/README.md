# ✈️ TerbangMY — Malaysia Flight Price Tracker

A full-featured Malaysia domestic flight price tracker website built with **PHP**, similar to Agoda Flights but focused exclusively on Malaysian routes.

## Features

- 🔍 **Flight Search** — Search across 15 Malaysian airports with real airline data (AirAsia, Malaysia Airlines, Batik Air, Firefly)
- 💰 **Price Comparison** — Sort by price, duration, departure time, or airline
- 📊 **Price History Charts** — 30-day price trend chart for every flight with canvas-drawn graphs
- 🔔 **Price Alerts** — Set a target price and get notified when fares drop
- 📅 **Flexible Date Search** — Date strip lets you quickly check prices ±3 days
- 🎯 **Smart Filters** — Filter by max price, stops, and airline
- 💺 **Economy & Business** — Compare both cabin classes
- 📱 **Fully Responsive** — Works on mobile, tablet, and desktop

## Pages

| Page | Description |
|------|-------------|
| `index.php` | Homepage with hero search form and popular routes |
| `search.php` | Search results with filtering, sorting, and price trend bars |
| `flight-details.php` | Detailed view with fare comparison, price history chart, and booking CTA |
| `price-tracker.php` | Price alert setup form and live route monitor |

## Airlines Covered

| Code | Airline | Type |
|------|---------|------|
| AK | AirAsia | Low-Cost |
| MH | Malaysia Airlines | Full-Service |
| OD | Batik Air Malaysia | Full-Service |
| FY | Firefly | Regional |
| D7 | AirAsia X | Long-Haul LCC |

## Airports (15 Covered)

KUL · PEN · BKI · KCH · JHB · LGK · KBR · IPH · MYY · TWU · SBW · MKZ · AOR · SDK · LBU

## Tech Stack

- **Backend:** PHP 8.0+ (no database required — uses dynamic mock data engine)
- **Frontend:** Vanilla HTML5 / CSS3 / JavaScript (no frameworks)
- **Charts:** Canvas API (no external chart libraries)
- **Fonts:** Google Fonts — Inter
- **Icons:** Unicode emoji (no icon library dependencies)

## Setup

### Requirements
- PHP 8.0 or higher
- Any web server (Apache, Nginx, or PHP built-in server)

### Run Locally (PHP Built-in Server)

```bash
cd malaysia-flight-tracker
php -S localhost:8080
```

Then open: http://localhost:8080

### Apache/Nginx
Point your virtual host document root to the `malaysia-flight-tracker/` folder.

## Project Structure

```
malaysia-flight-tracker/
├── index.php               # Homepage
├── search.php              # Search results
├── flight-details.php      # Flight detail & booking
├── price-tracker.php       # Price alerts
├── assets/
│   ├── css/style.css       # Main stylesheet
│   └── js/main.js          # Client-side JavaScript
└── includes/
    ├── header.php          # Site header
    ├── footer.php          # Site footer
    └── flights_data.php    # Flight data engine & pricing logic
```

## How the Price Engine Works

The flight data engine (`includes/flights_data.php`) generates realistic flight data based on:

- **Base prices** for each route (e.g., KUL–BKI: RM189 base)
- **Demand multipliers** — weekends +15%, last-minute +35%, early booking −10%
- **Airline multipliers** — Malaysia Airlines +35%, Batik Air +20%, AirAsia −15%
- **Time-of-day multipliers** — peak hours (6–8am, 5–7pm) +15–20%
- **30-day price history** — simulated using random walk for realistic chart data

## Screenshots

> Homepage with hero search, popular routes grid, airline strip, and features section.
> Search results with sidebar filters, flight cards with price trend bars, and date strip.
> Flight details with fare comparison cards, interactive price history chart, and booking summary.
> Price tracker with alert form, live route monitor, and travel tips.

## License

MIT License — Free to use and modify for educational and personal projects.

---

*TerbangMY is a demo/educational project. It is not affiliated with any airline or booking platform. Prices shown are simulated.*
