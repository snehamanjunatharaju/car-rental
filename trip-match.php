<?php
require_once './src/Database.php';
require_once './src/car-rental-view.php';
require_once './src/trip-match.php';

$defaultPickup = (new DateTimeImmutable('tomorrow'))->setTime(10, 0);
$defaultReturn = $defaultPickup->modify('+1 day');
$minimumPickup = (new DateTimeImmutable('now'))->format('Y-m-d\TH:i');
$startValue = isset($_GET['start']) && is_string($_GET['start'])
    ? $_GET['start']
    : (array_key_exists('start', $_GET) ? '' : $defaultPickup->format('Y-m-d\TH:i'));
$endValue = isset($_GET['end']) && is_string($_GET['end'])
    ? $_GET['end']
    : (array_key_exists('end', $_GET) ? '' : $defaultReturn->format('Y-m-d\TH:i'));
$pickup = app_parse_datetime_input($startValue);
$dropoff = app_parse_datetime_input($endValue);
$submitted = isset($_GET['match']) && $_GET['match'] === '1';
$errors = [];

$passengersRaw = isset($_GET['passengers']) && is_string($_GET['passengers'])
    ? $_GET['passengers']
    : (array_key_exists('passengers', $_GET) ? '' : '2');
$passengers = ctype_digit($passengersRaw) ? (int) $passengersRaw : 0;
if ($passengers < 1 || $passengers > 9) {
    $errors[] = 'Choose between 1 and 9 passengers.';
    $passengers = 2;
}

$luggageOptions = ['light' => 'Light (1 small bag)', 'standard' => 'Standard (2–3 bags)', 'roomy' => 'More luggage (4+ bags)'];
$luggage = isset($_GET['luggage']) && is_string($_GET['luggage'])
    ? $_GET['luggage']
    : (array_key_exists('luggage', $_GET) ? '' : 'standard');
if (!isset($luggageOptions[$luggage])) {
    $errors[] = 'Choose a luggage preference from the list.';
    $luggage = 'standard';
}

$budgetRaw = isset($_GET['budget']) && is_string($_GET['budget'])
    ? $_GET['budget']
    : (array_key_exists('budget', $_GET) ? '' : '3000');
$budget = filter_var($budgetRaw, FILTER_VALIDATE_FLOAT);
if ($budget === false || $budget < 1 || $budget > 100000) {
    $errors[] = 'Enter a daily budget between ₹1 and ₹1,00,000.';
    $budget = 3000;
}

$styleOptions = [
    'flexible' => 'No preference',
    'city' => 'City driving',
    'family' => 'Family and group trip',
    'highway' => 'Highway comfort',
];
$style = isset($_GET['style']) && is_string($_GET['style'])
    ? $_GET['style']
    : (array_key_exists('style', $_GET) ? '' : 'flexible');
if (!isset($styleOptions[$style])) {
    $errors[] = 'Choose a trip style from the list.';
    $style = 'flexible';
}

$transmissionOptions = [
    'any' => 'No preference',
    'manual' => 'Manual',
    'automatic' => 'Automatic',
];
$transmission = isset($_GET['transmission']) && is_string($_GET['transmission'])
    ? $_GET['transmission']
    : (array_key_exists('transmission', $_GET) ? '' : 'any');
if (!isset($transmissionOptions[$transmission])) {
    $errors[] = 'Choose a transmission preference from the list.';
    $transmission = 'any';
}

if ($pickup === null || $dropoff === null) {
    $errors[] = 'Enter valid pick-up and return dates.';
} elseif ($pickup < new DateTimeImmutable('now')) {
    $errors[] = 'Pick-up must be in the future.';
} elseif ($dropoff <= $pickup) {
    $errors[] = 'Return time must be after pick-up.';
}

$matches = [];
if ($submitted && !$errors) {
    $startSql = $pickup->format('Y-m-d H:i:s');
    $endSql = $dropoff->format('Y-m-d H:i:s');
    $statement = $db->prepare(
        "SELECT c.*, b.brand_name
         FROM cars c
         LEFT JOIN brands b ON b.id = c.brand
         WHERE c.status = 'Active'
           AND NOT EXISTS (
               SELECT 1
               FROM booking booking
               WHERE booking.car = c.id
                 AND booking.booking_status IN ('Booked', 'Live')
                 AND booking.start_date < ?
                 AND booking.end_date > ?
           )
         ORDER BY CAST(c.price_per_day AS DECIMAL(10, 2)) ASC, c.id ASC"
    );
    $statement->bind_param('ss', $endSql, $startSql);
    $statement->execute();
    $availableCars = $statement->get_result()->fetch_all(MYSQLI_ASSOC);

    $preferences = [
        'passengers' => $passengers,
        'luggage' => $luggage,
        'budget' => (float) $budget,
        'style' => $style,
        'transmission' => $transmission,
    ];
    foreach ($availableCars as $car) {
        $fit = app_trip_match_car($car, $preferences);
        if ($fit !== null) {
            $car['match_score'] = $fit['score'];
            $car['match_reasons'] = $fit['reasons'];
            $matches[] = $car;
        }
    }
    usort($matches, static function ($first, $second) {
        return $second['match_score'] <=> $first['match_score']
            ?: (float) $first['price_per_day'] <=> (float) $second['price_per_day']
            ?: (int) $first['id'] <=> (int) $second['id'];
    });
}

if ($errors) {
    http_response_code(422);
}

$budgetCarId = 0;
if ($matches) {
    $cheapestMatch = $matches[0];
    foreach ($matches as $match) {
        if ((float) $match['price_per_day'] < (float) $cheapestMatch['price_per_day']) {
            $cheapestMatch = $match;
        }
    }
    $budgetCarId = (int) $cheapestMatch['id'];
}

include './header.php';
?>

<main id="main" class="cr-listing-page cr-match-page">
    <section class="cr-listing-intro">
        <div class="cr-wrap">
            <p class="cr-eyebrow">A better shortlist</p>
            <h1 id="match-page-title">Find a car that fits your trip.</h1>
            <p>Tell us what matters. We rank the available cars against your passenger count, daily budget and preferences, and show why each one made the list.</p>
        </div>
    </section>

    <section class="cr-listing" aria-labelledby="match-page-title">
        <div class="cr-wrap">
            <?php foreach ($errors as $error): ?>
                <p class="cr-alert" role="alert"><?php echo app_escape($error); ?></p>
            <?php endforeach; ?>

            <form class="cr-filter-panel cr-match-form" action="./trip-match.php" method="get">
                <input type="hidden" name="match" value="1">
                <div class="cr-field">
                    <label for="match-passengers">Passengers</label>
                    <select id="match-passengers" name="passengers" required>
                        <?php for ($count = 1; $count <= 9; $count++): ?>
                            <option value="<?php echo $count; ?>" <?php echo $passengers === $count ? 'selected' : ''; ?>>
                                <?php echo $count; ?> passenger<?php echo $count === 1 ? '' : 's'; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="cr-field">
                    <label for="match-luggage">Luggage preference</label>
                    <select id="match-luggage" name="luggage" required>
                        <?php foreach ($luggageOptions as $value => $label): ?>
                            <option value="<?php echo app_escape($value); ?>" <?php echo $luggage === $value ? 'selected' : ''; ?>>
                                <?php echo app_escape($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cr-field">
                    <label for="match-budget">Maximum daily budget (₹)</label>
                    <input id="match-budget" type="number" name="budget" min="1" max="100000" step="100"
                        value="<?php echo app_escape((string) $budget); ?>" required>
                </div>
                <div class="cr-field">
                    <label for="match-style">Trip style</label>
                    <select id="match-style" name="style" required>
                        <?php foreach ($styleOptions as $value => $label): ?>
                            <option value="<?php echo app_escape($value); ?>" <?php echo $style === $value ? 'selected' : ''; ?>>
                                <?php echo app_escape($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cr-field">
                    <label for="match-transmission">Transmission preference</label>
                    <select id="match-transmission" name="transmission" required>
                        <?php foreach ($transmissionOptions as $value => $label): ?>
                            <option value="<?php echo app_escape($value); ?>" <?php echo $transmission === $value ? 'selected' : ''; ?>>
                                <?php echo app_escape($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cr-field">
                    <label for="match-start">Pick-up</label>
                    <input id="match-start" type="datetime-local" name="start"
                        value="<?php echo app_escape($startValue); ?>"
                        min="<?php echo app_escape($minimumPickup); ?>" required>
                </div>
                <div class="cr-field">
                    <label for="match-end">Return</label>
                    <input id="match-end" type="datetime-local" name="end"
                        value="<?php echo app_escape($endValue); ?>"
                        min="<?php echo app_escape($minimumPickup); ?>" required>
                </div>
                <div class="cr-filter-panel__actions">
                    <button class="cr-button" type="submit">Match my trip</button>
                    <a class="cr-clear-link" href="./cars.php">Browse all cars instead</a>
                </div>
            </form>

            <p class="cr-match-note">
                This is a transparent, rule-based shortlist—not AI or a live supplier recommendation. Passenger capacity and daily estimates come from this demo fleet. Luggage fit is only a body-style hint; exact boot capacity, live inventory and final provider prices are not available here.
            </p>

            <?php if ($submitted && !$errors): ?>
                <div class="cr-results-head">
                    <h2 id="match-form-title">Your trip matches</h2>
                    <p><?php echo count($matches); ?> car<?php echo count($matches) === 1 ? '' : 's'; ?> fit the selected seats, budget and trip dates.</p>
                </div>

                <?php if ($matches): ?>
                    <div class="cr-car-grid">
                        <?php foreach ($matches as $car): ?>
                            <?php include './partials/car-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="cr-empty">
                        <h2>No available cars fit those preferences</h2>
                        <p>Try a higher daily budget, fewer passengers, or different trip dates.</p>
                        <a class="cr-button" href="./cars.php">See all available cars</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include './footer.php'; ?>
