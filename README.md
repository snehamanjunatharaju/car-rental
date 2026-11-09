# Car Rental

A server-rendered car-rental demo built with PHP and MySQL. The customer flow covers searching a sample Bengaluru fleet for a date range, comparing indicative daily estimates, reviewing a booking total, and choosing PayU test checkout or cash on delivery.

## Live demo

Visit the deployed application at [sneha-car-rental.infinityfree.me](https://sneha-car-rental.infinityfree.me/).

## What it demonstrates

- Responsive, keyboard-accessible storefront with a date-led search and sample fleet data.
- Optional, button-triggered browser geolocation with an OpenStreetMap street-address lookup; the demo fleet is currently available in Bengaluru only.
- Rule-based Smart Trip Match that ranks available cars by passenger fit, daily budget, trip style, luggage preference and transmission, with an explanation for each result.
- Budget-focused sample fleet with an entry-level Alto-class option starting at an illustrative ₹1,499 per 24-hour day; higher-priced luxury demo cars are inactive.
- Indicative Bengaluru daily estimates in INR with a clear notice that rates are not live provider quotes.
- Separate online and cash-on-delivery checkout choices; COD bookings remain unpaid until an administrator confirms cash receipt.
- Cancelled online checkout returns to the selected car and trip dates when the customer session is available.
- Separate document verification, booking/payment, and manually updated vehicle delivery progress in the customer profile and booking detail.
- Server-side availability checks using overlapping booking intervals, plus brand, vehicle-style, transmission, text-search, and price/name sorting.
- Prepared database queries for search, car details, and booking availability.
- Prepared customer sign-in, session ID rotation, and a CSRF token on the login form.
- Validated rental dates, server-calculated booking totals, escaped dynamic HTML, local image fallbacks, and an itemized daily booking estimate.
- A small dependency-free PHP test for shared view and date-handling helpers.

## Stack

- PHP 7.4+ (tested with PHP 8.5), MySQLi and `mysqlnd`
- MySQL or MariaDB
- HTML, CSS, and the project's existing Bootstrap assets

## Run locally

1. Install PHP with the MySQLi extension and a local MySQL-compatible database.
2. Create and seed the database:

   ```sh
   mysql -u "your-local-db-user" -e "CREATE DATABASE car_rental CHARACTER SET utf8mb4"
   mysql -u "your-local-db-user" car_rental < database/schema.sql
   ```

3. Configure the connection with environment variables:

   ```sh
   export DB_HOST=localhost
   export DB_NAME=car_rental
   export DB_USER="your-local-db-user"
   export DB_PASSWORD="your-local-db-password"
   ```

   For a local account with no password, leave `DB_PASSWORD` unset. Do not commit production credentials.
4. Start the PHP development server from the repository root:

   ```sh
   php -S 127.0.0.1:8000 -t .
   ```

5. Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

The sanitized schema supplies sample cars and brands but no customer, booking, or administrator accounts. Register a customer account to try the customer flow. To enable admin verification and management, create an administrator from a trusted CLI with the `ADMIN_NAME`, `ADMIN_USERNAME`, and `ADMIN_PASSWORD` environment variables set, then run `php scripts/create-admin.php`; passwords must be at least 12 characters. PayU credentials are read from `PAYU_KEY` and `PAYU_SALT`; online payment stays unavailable until these are configured. Never put production credentials in source control.

## Deploy on Render

The repository includes a Docker configuration and Render Blueprint. Deploying requires a separately hosted MySQL/MariaDB database; Render does not provide a managed MySQL service. Import `database/schema.sql` into that database, then set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` on the Render web service. Do not use the historical `database/car_rental*.sql` local dumps; they contain personal demo records and are intentionally excluded from the public repository.

The included free web-service configuration is for a portfolio demo. Its local filesystem is ephemeral, so uploaded profile documents and admin-added images are not durable and may disappear after a restart or deploy. Do not collect real identity documents or rely on this service for real bookings or payments. PayU is disabled until valid environment variables are configured; leave it disabled for a demo.

## Deploy on InfinityFree

InfinityFree can host the PHP/MySQL version without converting the application to PostgreSQL. Create an InfinityFree account and a hosting account, then use its control panel to create a MySQL database and import `database/schema.sql` with phpMyAdmin. Copy the contents of this repository to the hosting account's web root (`htdocs`) using the file manager or FTP. Copy `src/local-config.example.php` to `src/local-config.php`, enter the database host, name, username, and password shown by the provider, and keep the private config file off GitHub. Register a customer account after deployment; the included schema intentionally has no customer or admin account. Use the trusted CLI admin-creation script only if the hosting environment provides an appropriate secure CLI; otherwise, use the customer demo or set up an administrator through a safe, documented process before relying on admin features.

The provider controls available PHP versions, database limits, upload limits, and outbound requests on its free plan. Check these limits in the hosting control panel before deployment. Test registration, bookings, PDF download, location lookup, and uploads on the hosted site. Do not upload real identity documents or enable real payments on free demo hosting.

PayU callback URLs are built from the host and application path used to open checkout. Keep using the same local host and port throughout a test flow (for example, do not switch between `localhost` and `127.0.0.1`). A cancelled checkout returns to that car's detail page with its selected trip dates, provided the session is retained.

The seeded daily rates are illustrative Bengaluru self-drive estimates, not live or verified supplier prices. They are for demonstrating the experience; actual availability, taxes, fuel, tolls, parking, mileage limits, deposits, and supplier terms vary. Confirm a provider's final quote before taking payment. Rates apply per 24-hour block and partial blocks round up. Update `price_per_day` with verified supplier prices when operating a real fleet. Without a provider inventory and pricing integration, this application cannot offer live bookable cars or guarantee the shown rate.

Existing local databases created with the hourly schema must be migrated once before running the updated application:

```sh
mysql -u "your-local-db-user" car_rental < database/migrations/2026-11-08-daily-rates.sql
```

For the separate customer delivery-progress field, existing databases also need:

```sh
mysql -u "your-local-db-user" car_rental < database/migrations/2026-11-09-delivery-progress.sql
```

The location button does not request GPS on page load. After a customer taps it and approves the browser permission, the browser sends coordinates to OpenStreetMap Nominatim for a nearby street-address lookup. The result fills an editable pickup-address field; map data may not identify an exact house number, so customers should check it. On search, the address and selected city are stored in the PHP session (not the URL) and shown on the trip and payment review pages only when they match. The address is not sent to a rental provider or payment gateway, and this demo cannot confirm delivery or a nearby pickup point.

## Verify

```sh
php tests/car-rental-view-test.php
```

The test covers date parsing, malformed dates, quote calculations, safe local car-image URLs, booking links, and HTML escaping.

## Design notes

- Search filters are regular GET parameters, so a trip search can be bookmarked or shared.
- Smart Trip Match is an explainable rules-based ranking, not generative AI. It uses listed passenger seats, sample daily estimates and vehicle attributes; luggage fit is only a body-style hint because verified boot capacity is not in the database. It cannot verify live supplier inventory, actual prices, or vehicle suitability beyond the stored data.
- Document verification status is separate from bookings. A paid online booking is created by the payment callback. A COD booking is reserved as unpaid, and an administrator must confirm physically receiving cash before marking delivery progress. Administrators can then manually set delivery progress to Confirmed, Preparing, On the way, or Delivered. These updates are not live GPS or proof of dispatch. COD is offered on this app's checkout page; payment methods within PayU's hosted page are controlled by PayU.
- Availability treats rentals as half-open time ranges: a car returned at 10:00 can be picked up again at 10:00. Search results and the detail page both check for overlapping booked/live reservations.
- The estimate rounds any partial 24-hour rental block up and does not add a made-up service fee. Checkout recalculates the total from the selected car's stored daily rate and dates rather than trusting a price in the URL.
- Invalid trip filters show a validation message with HTTP 422; unknown cars return 404; and a newly booked car returns 409.

## Project map

- `index.php` — landing page and trip-date search
- `cars.php` — server-rendered fleet search, filters, sorting, and availability checks
- `trip-match.php` — preference-based trip matcher using the same availability data as the fleet search
- `car-details.php` — car details, availability recheck, and booking estimate
- `my-bookings.php` and `view-my-booking.php` — customer-owned booking history and details
- `booking.php` and `checkout.php` — legacy entry points that route customers to the current fleet/booking flow
- `partials/car-card.php` — shared fleet card
- `src/car-rental-view.php` — shared escaping, date, and URL helpers
- `src/trip-match.php` — transparent rule-based vehicle-fit scoring
- `database/car_rental.sql` — sample schema and data

## Before production

This repository is a portfolio/demo application, not a production booking platform or live rental marketplace. Real-world use requires a verified fleet and supplier price feed, pickup/delivery coverage and a location search covering the cities you serve, vehicle-specific rental terms, tax and deposit rules, production payment credentials, payment callback verification, and atomic booking confirmation. Before deployment, move database and payment credentials to environment-managed secrets and review authentication, authorization, CSRF protection, and file uploads.
