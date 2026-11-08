<?php

require_once __DIR__ . '/../src/car-rental-view.php';
require_once __DIR__ . '/../src/trip-match.php';

function expect($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
}

$validDate = app_parse_datetime_input('2026-11-09T10:30');
expect($validDate instanceof DateTimeImmutable, 'parses valid local date-times');
expect($validDate->format('Y-m-d\TH:i') === '2026-11-09T10:30', 'preserves date-time precision');
expect(app_parse_datetime_input('2026-02-30T10:30') === null, 'rejects impossible calendar dates');
expect(app_parse_datetime_input('not-a-date') === null, 'rejects malformed date-times');
expect(app_parse_datetime_input('') === null, 'rejects empty date-times');
$rentalDate = app_parse_rental_datetime('09-11-2026 10:30 AM');
expect($rentalDate instanceof DateTimeImmutable, 'parses booking date-times used at checkout');
expect($rentalDate->format('d-m-Y h:i A') === '09-11-2026 10:30 AM', 'preserves checkout date-time values');
expect(app_parse_rental_datetime('31-02-2026 10:30 AM') === null, 'rejects impossible checkout dates');
expect(app_parse_rental_datetime('9-11-2026 10:30 AM') === null, 'rejects non-canonical checkout dates');
$quote = app_calculate_rental_quote(
    1499,
    new DateTimeImmutable('2026-11-09 10:00:00'),
    new DateTimeImmutable('2026-11-10 10:30:00')
);
expect($quote['days'] === 2, 'rounds a partial 24-hour block up to a second rental day');
expect($quote['subtotal'] === 2998.0, 'calculates the daily rental subtotal');
expect($quote['total'] === 2998.0, 'keeps the estimate equal to the daily rental subtotal');
$oneDayQuote = app_calculate_rental_quote(
    1499,
    new DateTimeImmutable('2026-11-09 10:00:00'),
    new DateTimeImmutable('2026-11-10 10:00:00')
);
expect($oneDayQuote['days'] === 1 && $oneDayQuote['total'] === 1499.0, 'charges one daily rate for exactly 24 hours');
expect(app_supported_rental_city('Bangalore') === 'Bengaluru', 'normalizes the Bangalore city alias');
expect(app_supported_rental_city(' Bengaluru ') === 'Bengaluru', 'normalizes the supported rental city');
expect(app_supported_rental_city('Mumbai') === null, 'rejects cities outside the current service area');
expect(app_delivery_status_steps() === ['Confirmed', 'Preparing', 'On the way', 'Delivered'], 'defines the customer delivery progress sequence');
expect(app_delivery_status_position('On the way') === 2, 'resolves a valid delivery progress step');
expect(app_delivery_status_position('Unknown') === 0, 'does not advance progress for an unknown status');
expect(app_payment_method_label('cash_on_delivery') === 'Cash on delivery', 'labels cash-on-delivery bookings');
expect(app_payment_method_label('online') === 'Online payment', 'labels online bookings');
expect(app_payment_status_label('cash_on_delivery', 'pending') === 'Due on delivery', 'explains COD payment due status');
expect(app_payment_status_label('cash_on_delivery', 'success') === 'Paid on delivery', 'labels completed COD payment');
expect(app_payment_status_label('online', 'success') === 'Paid online', 'labels completed online payment');
expect(
    app_car_image_url('../admin/uploaded-files/cars/vehicle photo.jpg') === 'admin/uploaded-files/cars/vehicle%20photo.jpg',
    'limits car image URLs to the uploaded-cars directory'
);
expect(app_car_image_url('') === 'img/car3.png', 'uses a local fallback for missing car images');
expect(app_car_image_url('..') === 'img/car3.png', 'uses the fallback for invalid image paths');
expect(
    strpos(app_car_detail_url(7, '2026-11-09T10:30', '2026-11-10T10:30'), 'id=7') !== false,
    'builds detail links with the car identifier'
);
expect(
    strpos(app_car_detail_url(7, '2026-11-09T10:30', '2026-11-10T10:30', 'Bengaluru'), 'city=Bengaluru') !== false,
    'preserves the pickup city in car links'
);
expect(
    strpos(app_car_detail_url(7, '2026-11-09T10:30', '2026-11-10T10:30'), 'start=2026-11-09T10%3A30') !== false,
    'URL-encodes date-times in detail links'
);
$familyPreferences = [
    'passengers' => 4,
    'luggage' => 'roomy',
    'budget' => 3000,
    'style' => 'family',
    'transmission' => 'automatic',
];
$familyMatch = app_trip_match_car([
    'seating_capacity' => '7',
    'price_per_day' => '2500',
    'car_body_type' => 'SUV',
    'transmission' => 'Automatic',
], $familyPreferences);
expect(is_array($familyMatch), 'matches a vehicle that fits the requested trip');
expect($familyMatch['score'] === 80, 'scores preference fit out of 100');
expect(
    app_trip_match_car([
        'seating_capacity' => '3',
        'price_per_day' => '2500',
        'car_body_type' => 'SUV',
        'transmission' => 'Automatic',
    ], $familyPreferences) === null,
    'excludes a vehicle without enough seats'
);
expect(
    app_trip_match_car([
        'seating_capacity' => '7',
        'price_per_day' => '3500',
        'car_body_type' => 'SUV',
        'transmission' => 'Automatic',
    ], $familyPreferences) === null,
    'excludes a vehicle above the daily budget'
);
expect(app_escape('<script>') === '&lt;script&gt;', 'escapes dynamic text for HTML');

echo "Car-rental view tests passed.\n";
