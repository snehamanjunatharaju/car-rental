<?php
require_once './src/Database.php';
require_once './src/car-rental-view.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['pickup_address_csrf_token']) || !is_string($_SESSION['pickup_address_csrf_token'])) {
    $_SESSION['pickup_address_csrf_token'] = bin2hex(random_bytes(32));
}
$pickupAddressCsrfToken = $_SESSION['pickup_address_csrf_token'];
$pickupAddress = isset($_SESSION['pickup_address']) && is_string($_SESSION['pickup_address'])
    && isset($_SESSION['pickup_city']) && $_SESSION['pickup_city'] === 'Bengaluru'
    ? $_SESSION['pickup_address']
    : '';

$defaultStart = (new DateTimeImmutable('tomorrow'))->setTime(10, 0);
$defaultEnd = $defaultStart->modify('+1 day');
$minimumPickup = (new DateTimeImmutable('now'))->format('Y-m-d\TH:i');

$startRaw = isset($_GET['start']) && is_string($_GET['start']) ? $_GET['start'] : '';
$endRaw = isset($_GET['end']) && is_string($_GET['end']) ? $_GET['end'] : '';
$cityRaw = array_key_exists('city', $_GET)
    ? (is_string($_GET['city']) ? $_GET['city'] : '')
    : 'Bengaluru';
$city = app_supported_rental_city($cityRaw);
$hasDateInput = array_key_exists('start', $_GET) || array_key_exists('end', $_GET);
$startValue = $startRaw !== '' ? $startRaw : $defaultStart->format('Y-m-d\TH:i');
$endValue = $endRaw !== '' ? $endRaw : $defaultEnd->format('Y-m-d\TH:i');
$errors = [];
$pickup = app_parse_datetime_input($startValue);
$dropoff = app_parse_datetime_input($endValue);

if ($city === null) {
    $errors[] = 'We currently serve Bengaluru only. Choose Bengaluru to explore the demo fleet.';
    $city = 'Bengaluru';
}

if ($hasDateInput && ($startRaw === '' || $endRaw === '')) {
    $errors[] = 'Choose both a pick-up and return time.';
} elseif ($pickup === null || $dropoff === null) {
    $errors[] = 'Enter a valid pick-up and return date and time.';
} elseif ($pickup < new DateTimeImmutable('now')) {
    $errors[] = 'Pick-up must be in the future.';
} elseif ($dropoff <= $pickup) {
    $errors[] = 'Return time must be after pick-up.';
}

$brandResult = $db->query("SELECT id, brand_name FROM brands WHERE status = 'Enable' ORDER BY brand_name");
$brands = $brandResult->fetch_all(MYSQLI_ASSOC);

$brandRaw = isset($_GET['brand']) && is_string($_GET['brand']) ? $_GET['brand'] : '';
$brandId = 0;
if ($brandRaw !== '') {
    if (!ctype_digit($brandRaw) || !in_array((int) $brandRaw, array_column($brands, 'id'), false)) {
        $errors[] = 'Choose a brand from the list.';
    } else {
        $brandId = (int) $brandRaw;
    }
}

$bodyResult = $db->query(
    "SELECT DISTINCT car_body_type
     FROM cars
     WHERE status = 'Active' AND car_body_type IS NOT NULL AND car_body_type <> ''
     ORDER BY car_body_type"
);
$bodyTypes = array_column($bodyResult->fetch_all(MYSQLI_ASSOC), 'car_body_type');

$transmissionResult = $db->query(
    "SELECT DISTINCT transmission
     FROM cars
     WHERE status = 'Active' AND transmission <> ''
     ORDER BY transmission"
);
$transmissions = array_column($transmissionResult->fetch_all(MYSQLI_ASSOC), 'transmission');

$bodyType = isset($_GET['body']) && is_string($_GET['body']) ? $_GET['body'] : '';
if ($bodyType !== '' && !in_array($bodyType, $bodyTypes, true)) {
    $errors[] = 'Choose a vehicle style from the list.';
    $bodyType = '';
}

$transmission = isset($_GET['transmission']) && is_string($_GET['transmission'])
    ? $_GET['transmission']
    : '';
if ($transmission !== '' && !in_array($transmission, $transmissions, true)) {
    $errors[] = 'Choose a transmission type from the list.';
    $transmission = '';
}

$searchTerm = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
if (strlen($searchTerm) > 100) {
    $errors[] = 'Search terms must be 100 characters or fewer.';
    $searchTerm = substr($searchTerm, 0, 100);
}

$sortOptions = [
    'featured' => 'Budget-friendly first',
    'price_low' => 'Price: low to high',
    'price_high' => 'Price: high to low',
    'name' => 'Name: A to Z',
];
$sort = isset($_GET['sort']) && is_string($_GET['sort']) ? $_GET['sort'] : 'featured';
if (!isset($sortOptions[$sort])) {
    $errors[] = 'Choose a valid sort order.';
    $sort = 'featured';
}

$cars = [];
if (!$errors) {
    $startSql = $pickup->format('Y-m-d H:i:s');
    $endSql = $dropoff->format('Y-m-d H:i:s');
    $orderBy = [
        'featured' => 'CAST(c.price_per_day AS DECIMAL(10, 2)) ASC, c.id ASC',
        'price_low' => 'CAST(c.price_per_day AS DECIMAL(10, 2)) ASC, c.id ASC',
        'price_high' => 'CAST(c.price_per_day AS DECIMAL(10, 2)) DESC, c.id ASC',
        'name' => 'c.car_name ASC, c.id ASC',
    ][$sort];

    $sql = "SELECT c.*, b.brand_name
            FROM cars c
            LEFT JOIN brands b ON b.id = c.brand
            WHERE c.status = 'Active'
              AND (? = 0 OR c.brand = ?)
              AND (? = '' OR c.car_name LIKE CONCAT('%', ?, '%')
                   OR COALESCE(b.brand_name, '') LIKE CONCAT('%', ?, '%'))
              AND (? = '' OR c.car_body_type = ?)
              AND (? = '' OR c.transmission = ?)
              AND NOT EXISTS (
                  SELECT 1
                  FROM booking booking
                  WHERE booking.car = c.id
                    AND booking.booking_status IN ('Booked', 'Live')
                    AND booking.start_date < ?
                    AND booking.end_date > ?
              )
            ORDER BY $orderBy";
    $statement = $db->prepare($sql);
    $statement->bind_param(
        'iisssssssss',
        $brandId,
        $brandId,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $bodyType,
        $bodyType,
        $transmission,
        $transmission,
        $endSql,
        $startSql
    );
    $statement->execute();
    $cars = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
}

$budgetCarId = 0;
if ($cars) {
    $budgetCar = $cars[0];
    foreach ($cars as $candidate) {
        if ((float) $candidate['price_per_day'] < (float) $budgetCar['price_per_day']) {
            $budgetCar = $candidate;
        }
    }
    $budgetCarId = (int) $budgetCar['id'];
}

if ($errors) {
    http_response_code(422);
}

include './header.php';
?>

<main id="main" class="cr-listing-page">
    <section class="cr-listing-intro">
        <div class="cr-wrap">
            <p class="cr-eyebrow">Explore the fleet</p>
            <h1>Find the right drive.</h1>
            <p>Pick your dates, narrow the list and compare the cars available for your trip.</p>
            <p class="cr-pricing-disclaimer">
                Sample Bengaluru fleet and indicative daily estimates only, not live availability or provider quotes. Taxes, fuel, tolls, parking, mileage limits, deposits and pickup terms need provider confirmation.
            </p>
        </div>
    </section>

    <section class="cr-listing" aria-labelledby="results-title">
        <div class="cr-wrap">
            <?php foreach ($errors as $error): ?>
                <p class="cr-alert" role="alert"><?php echo app_escape($error); ?></p>
            <?php endforeach; ?>

            <form class="cr-filter-panel" action="./cars.php" method="get" data-pickup-address-form>
                <input type="hidden" data-pickup-address-csrf value="<?php echo app_escape($pickupAddressCsrfToken); ?>">
                <div class="cr-field cr-filter-panel__search">
                    <label for="listing-city">Pickup city</label>
                    <select id="listing-city" name="city" aria-describedby="listing-location-status listing-location-privacy" required>
                        <option value="Bengaluru" selected>Bengaluru, Karnataka</option>
                    </select>
                    <button class="cr-location-button" type="button" data-location-button="listing-city" data-address-target="listing-pickup-address">
                        <span aria-hidden="true">◎</span> Use my location
                    </button>
                    <small id="listing-location-status" data-location-status="listing-city" role="status" aria-live="polite">
                        Pickup is currently available in Bengaluru only.
                    </small>
                    <small id="listing-location-privacy">
                        Only on your request, coordinates are sent to <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> to look up an address. The address stays in this app session and is not sent to a rental provider.
                    </small>
                </div>
                <div class="cr-field cr-filter-panel__search cr-pickup-address">
                    <label for="listing-pickup-address" data-address-label>
                        <?php echo $pickupAddress !== '' ? 'Pickup address (saved for this session — check or edit)' : 'Pickup address (optional)'; ?>
                    </label>
                    <input id="listing-pickup-address" type="text" data-pickup-address maxlength="250"
                        autocomplete="street-address" value="<?php echo app_escape($pickupAddress); ?>"
                        placeholder="Use your location or enter an address">
                    <small data-location-accuracy>Use my location to request your device's current location, or enter an address yourself. This demo cannot confirm delivery or a nearby pickup point.</small>
                </div>
                <div class="cr-field">
                    <label for="listing-start">Pick-up</label>
                    <input id="listing-start" type="datetime-local" name="start"
                        value="<?php echo app_escape($startValue); ?>"
                        min="<?php echo app_escape($minimumPickup); ?>" required>
                </div>
                <div class="cr-field">
                    <label for="listing-end">Return</label>
                    <input id="listing-end" type="datetime-local" name="end"
                        value="<?php echo app_escape($endValue); ?>"
                        min="<?php echo app_escape($minimumPickup); ?>" required>
                </div>
                <div class="cr-field cr-filter-panel__search">
                    <label for="listing-search">Search cars</label>
                    <input id="listing-search" type="search" name="q" maxlength="100"
                        placeholder="Try Toyota, SUV, or another make"
                        value="<?php echo app_escape($searchTerm); ?>">
                </div>
                <div class="cr-field">
                    <label for="listing-brand">Brand</label>
                    <select id="listing-brand" name="brand">
                        <option value="">All brands</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo (int) $brand['id']; ?>"
                                <?php echo $brandId === (int) $brand['id'] ? 'selected' : ''; ?>>
                                <?php echo app_escape($brand['brand_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cr-field">
                    <label for="listing-body">Vehicle style</label>
                    <select id="listing-body" name="body">
                        <option value="">All styles</option>
                        <?php foreach ($bodyTypes as $option): ?>
                            <option value="<?php echo app_escape($option); ?>"
                                <?php echo $bodyType === $option ? 'selected' : ''; ?>>
                                <?php echo app_escape($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cr-field">
                    <label for="listing-transmission">Transmission</label>
                    <select id="listing-transmission" name="transmission">
                        <option value="">Any transmission</option>
                        <?php foreach ($transmissions as $option): ?>
                            <option value="<?php echo app_escape($option); ?>"
                                <?php echo $transmission === $option ? 'selected' : ''; ?>>
                                <?php echo app_escape($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cr-field">
                    <label for="listing-sort">Sort by</label>
                    <select id="listing-sort" name="sort">
                        <?php foreach ($sortOptions as $value => $label): ?>
                            <option value="<?php echo app_escape($value); ?>"
                                <?php echo $sort === $value ? 'selected' : ''; ?>>
                                <?php echo app_escape($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cr-filter-panel__actions">
                    <button class="cr-button" type="submit">Show available cars</button>
                    <a class="cr-clear-link" href="./cars.php">Clear filters</a>
                </div>
            </form>

            <div class="cr-results-head">
                <h2 id="results-title">Cars for your trip</h2>
                <?php if (!$errors): ?>
                    <p><?php echo count($cars); ?> <?php echo count($cars) === 1 ? 'car' : 'cars'; ?> available
                        &middot;
                        <?php echo app_escape($pickup->format('M j, g:i A')); ?>
                        &ndash; <?php echo app_escape($dropoff->format('M j, g:i A')); ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if (!$errors && $cars): ?>
                <div class="cr-car-grid">
                    <?php foreach ($cars as $car): ?>
                        <?php include './partials/car-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php elseif (!$errors): ?>
                <div class="cr-empty">
                    <h2>No cars match those trip details</h2>
                    <p>Try different dates or remove a filter to see more of the fleet.</p>
                    <a class="cr-button" href="./cars.php">Clear filters</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include './footer.php'; ?>
