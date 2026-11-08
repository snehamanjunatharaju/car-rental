<?php

function app_trip_match_car(array $car, array $preferences)
{
    $seats = isset($car['seating_capacity']) ? (int) $car['seating_capacity'] : 0;
    $passengers = (int) ($preferences['passengers'] ?? 0);
    $dailyRate = isset($car['price_per_day']) ? (float) $car['price_per_day'] : 0;
    $budget = (float) ($preferences['budget'] ?? 0);

    if ($seats < $passengers || $dailyRate < 1 || $dailyRate > $budget) {
        return null;
    }

    $bodyType = strtolower(trim((string) ($car['car_body_type'] ?? '')));
    $transmission = strtolower(trim((string) ($car['transmission'] ?? '')));
    $style = (string) ($preferences['style'] ?? 'flexible');
    $luggage = (string) ($preferences['luggage'] ?? 'standard');
    $preferredTransmission = (string) ($preferences['transmission'] ?? 'any');
    $score = 0;
    $reasons = [];

    $spareSeats = $seats - $passengers;
    $seatScore = $spareSeats <= 2 ? 30 : 20;
    $score += $seatScore;
    $reasons[] = 'Fits ' . $passengers . ' passenger' . ($passengers === 1 ? '' : 's')
        . ' in the listed ' . $seats . '-seat capacity.';

    $budgetRatio = $dailyRate / $budget;
    $budgetScore = $budgetRatio <= 0.5 ? 25 : ($budgetRatio <= 0.75 ? 20 : ($budgetRatio <= 0.9 ? 15 : 10));
    $score += $budgetScore;
    $reasons[] = 'Estimated at ₹' . number_format($dailyRate) . ' per day, within your ₹'
        . number_format($budget) . ' daily budget.';

    $styleGroups = [
        'city' => ['hatchback', 'compact', 'sedan'],
        'family' => ['suv', 'mpv', 'van'],
        'highway' => ['sedan', 'suv'],
    ];
    if ($style === 'flexible') {
        $score += 20;
        $reasons[] = 'No trip-style preference selected.';
    } elseif (in_array($bodyType, $styleGroups[$style] ?? [], true)) {
        $score += 20;
        $reasons[] = 'Its ' . trim((string) ($car['car_body_type'] ?? '')) . ' body style matches your '
            . $style . '-trip preference.';
    } else {
        $score += 8;
        $reasons[] = 'It meets your passenger and budget needs; its body style is not the closest match for '
            . $style . ' trips.';
    }

    if ($luggage === 'light') {
        $score += 10;
        $reasons[] = 'Your light-luggage choice does not require a larger body style.';
    } elseif (
        ($luggage === 'standard' && in_array($bodyType, ['sedan', 'suv', 'mpv'], true))
        || ($luggage === 'roomy' && in_array($bodyType, ['suv', 'mpv', 'van'], true))
    ) {
        $score += 10;
        $reasons[] = 'The ' . trim((string) ($car['car_body_type'] ?? '')) . ' style is a useful luggage-space hint.';
    } else {
        $score += 4;
        $reasons[] = 'Luggage fit is uncertain from the available vehicle details.';
    }

    if ($preferredTransmission === 'any') {
        $score += 15;
    } elseif ($transmission === $preferredTransmission) {
        $score += 15;
        $reasons[] = 'Transmission matches your ' . $preferredTransmission . ' preference.';
    } else {
        $reasons[] = 'The listed transmission is ' . ($transmission ?: 'not specified') . ', not your '
            . $preferredTransmission . ' preference.';
    }

    return [
        'score' => $score,
        'reasons' => $reasons,
    ];
}
