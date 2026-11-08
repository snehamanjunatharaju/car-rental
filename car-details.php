<?php
require_once './src/Database.php';
require_once './src/car-rental-view.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$paymentNotice = isset($_SESSION['payment_notice']) && is_string($_SESSION['payment_notice'])
    ? $_SESSION['payment_notice']
    : '';
unset($_SESSION['payment_notice']);

$carIdRaw = isset($_GET['id']) && is_string($_GET['id']) ? $_GET['id'] : '';
$startValue = isset($_GET['start']) && is_string($_GET['start']) ? $_GET['start'] : '';
$endValue = isset($_GET['end']) && is_string($_GET['end']) ? $_GET['end'] : '';
$cityRaw = array_key_exists('city', $_GET)
    ? (is_string($_GET['city']) ? $_GET['city'] : '')
    : 'Bengaluru';
$city = app_supported_rental_city($cityRaw);
$pickup = app_parse_datetime_input($startValue);
$dropoff = app_parse_datetime_input($endValue);
$pickupAddress = isset($_SESSION['pickup_address']) && is_string($_SESSION['pickup_address'])
    && isset($_SESSION['pickup_city']) && $_SESSION['pickup_city'] === $city
    ? $_SESSION['pickup_address']
    : '';
$problem = '';
$statusCode = 200;
$car = null;

if (!ctype_digit($carIdRaw) || (int) $carIdRaw < 1) {
    $problem = 'That car could not be found.';
    $statusCode = 404;
} elseif ($city === null) {
    $problem = 'We currently serve Bengaluru only. Search again with Bengaluru as your pickup city.';
    $statusCode = 422;
} elseif ($pickup === null || $dropoff === null || $dropoff <= $pickup) {
    $problem = 'Choose a valid pick-up and return time before viewing a booking estimate.';
    $statusCode = 422;
} elseif ($pickup < new DateTimeImmutable('now')) {
    $problem = 'Pick-up must be in the future. Please search again with new dates.';
    $statusCode = 422;
} else {
    $carId = (int) $carIdRaw;
    $carStatement = $db->prepare(
        "SELECT c.*, b.brand_name
         FROM cars c
         LEFT JOIN brands b ON b.id = c.brand
         WHERE c.id = ? AND c.status = 'Active'"
    );
    $carStatement->bind_param('i', $carId);
    $carStatement->execute();
    $car = $carStatement->get_result()->fetch_assoc();

    if (!$car) {
        $problem = 'That car is no longer available.';
        $statusCode = 404;
    } else {
        $availabilityStatement = $db->prepare(
            "SELECT COUNT(*) AS conflicts
             FROM booking
             WHERE car = ?
               AND booking_status IN ('Booked', 'Live')
               AND start_date < ?
               AND end_date > ?"
        );
        $endSql = $dropoff->format('Y-m-d H:i:s');
        $startSql = $pickup->format('Y-m-d H:i:s');
        $availabilityStatement->bind_param('iss', $carId, $endSql, $startSql);
        $availabilityStatement->execute();
        $isUnavailable = (int) $availabilityStatement->get_result()->fetch_assoc()['conflicts'] > 0;

        if ($isUnavailable) {
            $problem = 'This car was just booked for those dates. Search again to see what is available.';
            $statusCode = 409;
        }
    }
}

if ($problem !== '') {
    http_response_code($statusCode);
} else {
    $quote = app_calculate_rental_quote($car['price_per_day'], $pickup, $dropoff);
    $rentalDays = $quote['days'];
    $dailyRate = $quote['daily_rate'];
    $subtotal = $quote['subtotal'];
    $totalPrice = $quote['total'];
    $description = trim((string) $car['description']);
    if (stripos($description, 'lorem ipsum') !== false) {
        $description = '';
    }
    $startFormatted = $pickup->format('d-m-Y h:i A');
    $endFormatted = $dropoff->format('d-m-Y h:i A');
    $bookingUrl = './pay.php?' . http_build_query([
        'car_id' => (int) $car['id'],
        'amount' => number_format($totalPrice, 2, '.', ''),
        'start_date' => $startFormatted,
        'end_date' => $endFormatted,
        'city' => $city,
    ]);
    if (!isset($_SESSION['isLogged']) || $_SESSION['isLogged'] !== true) {
        $_SESSION['redirect_url'] = './car-details.php?' . http_build_query([
            'id' => (int) $car['id'],
            'start' => $startValue,
            'end' => $endValue,
            'city' => $city,
        ]);
    }

    $images = array_values(array_filter([
        $car['image1'],
        $car['image2'],
        $car['image3'],
    ], function ($image) {
        return basename((string) $image) !== '';
    }));

    if (!$images) {
        $images[] = '';
    }
}

include './header.php';
?>

<main id="main" class="cr-detail">
    <div class="cr-wrap">
        <?php if ($problem !== ''): ?>
            <section class="cr-empty cr-detail__error" aria-labelledby="detail-error-title">
                <p class="cr-eyebrow">Trip details</p>
                <h1 id="detail-error-title"><?php echo app_escape($problem); ?></h1>
                <p>Update your dates or pick another car from the fleet.</p>
                <a class="cr-button" href="./cars.php">Back to available cars</a>
            </section>
        <?php else: ?>
            <?php if ($paymentNotice !== ''): ?>
                <p class="cr-alert" role="status"><?php echo app_escape($paymentNotice); ?></p>
            <?php endif; ?>
            <nav class="cr-detail__breadcrumbs" aria-label="Breadcrumb">
                <a href="./cars.php?city=<?php echo rawurlencode($city); ?>&amp;start=<?php echo rawurlencode($startValue); ?>&amp;end=<?php echo rawurlencode($endValue); ?>">
                    Available cars
                </a>
                <span aria-hidden="true">/</span>
                <span><?php echo app_escape($car['car_name']); ?></span>
            </nav>

            <header class="cr-detail__heading">
                <div>
                    <p class="cr-eyebrow"><?php echo app_escape($car['brand_name'] ?: 'Your next drive'); ?></p>
                    <h1><?php echo app_escape($car['car_name']); ?></h1>
                </div>
                <p class="cr-detail__rate">
                    <strong>₹<?php echo number_format($dailyRate); ?></strong>
                    <span>approx. per day</span>
                </p>
            </header>

            <div class="cr-detail__grid">
                <div>
                    <div class="cr-detail__gallery">
                        <?php foreach ($images as $image): ?>
                            <div class="cr-detail__photo">
                                <img src="<?php echo app_escape(app_car_image_url($image)); ?>"
                                    alt="<?php echo app_escape($car['car_name']); ?>"
                                    loading="lazy"
                                    onerror="this.onerror=null;this.src='img/car3.png';">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <section class="cr-detail__section" aria-labelledby="specs-title">
                        <p class="cr-eyebrow">The details</p>
                        <h2 id="specs-title">A little more about this car</h2>
                        <dl class="cr-spec-grid">
                            <div><dt>Vehicle style</dt><dd><?php echo app_escape($car['car_body_type'] ?: '—'); ?></dd></div>
                            <div><dt>Transmission</dt><dd><?php echo app_escape($car['transmission']); ?></dd></div>
                            <div><dt>Fuel</dt><dd><?php echo app_escape($car['fuel_type']); ?></dd></div>
                            <div><dt>Seats</dt><dd><?php echo app_escape($car['seating_capacity']); ?></dd></div>
                            <div><dt>Air conditioning</dt><dd><?php echo (int) $car['ac'] ? 'Yes' : 'No'; ?></dd></div>
                            <div><dt>Air bags</dt><dd><?php echo (int) $car['air_bags'] ? 'Yes' : 'No'; ?></dd></div>
                        </dl>
                        <?php if ($description !== ''): ?>
                            <p class="cr-detail__description"><?php echo nl2br(app_escape($description)); ?></p>
                        <?php endif; ?>
                    </section>
                </div>

                <aside class="cr-trip-card" aria-labelledby="trip-title">
                    <p class="cr-eyebrow">Your trip</p>
                    <h2 id="trip-title">Review your estimate</h2>

                    <dl class="cr-trip-card__dates">
                        <div>
                            <dt>Pickup city</dt>
                            <dd><?php echo app_escape($city); ?>, Karnataka</dd>
                        </div>
                        <?php if ($pickupAddress !== ''): ?>
                            <div>
                                <dt>Pickup address</dt>
                                <dd><?php echo app_escape($pickupAddress); ?></dd>
                            </div>
                        <?php endif; ?>
                        <div>
                            <dt>Pick-up</dt>
                            <dd><?php echo app_escape($pickup->format('D, M j, Y')); ?></dd>
                            <dd><?php echo app_escape($pickup->format('g:i A')); ?></dd>
                        </div>
                        <div>
                            <dt>Return</dt>
                            <dd><?php echo app_escape($dropoff->format('D, M j, Y')); ?></dd>
                            <dd><?php echo app_escape($dropoff->format('g:i A')); ?></dd>
                        </div>
                    </dl>

                    <div class="cr-trip-card__line">
                        <span>₹<?php echo number_format($dailyRate); ?> &times; <?php echo $rentalDays; ?> <?php echo $rentalDays === 1 ? 'day' : 'days'; ?></span>
                        <strong>₹<?php echo number_format($subtotal, 2); ?></strong>
                    </div>
                    <div class="cr-trip-card__total">
                        <span>Estimated total</span>
                        <strong>₹<?php echo number_format($totalPrice, 2); ?></strong>
                    </div>

                    <a class="cr-button cr-trip-card__button" href="<?php echo app_escape($bookingUrl); ?>">
                        Continue to booking <span aria-hidden="true">&nbsp;→</span>
                    </a>
                    <p class="cr-trip-card__note">
                        Daily rates apply to each 24-hour block; partial blocks round up. This is a sample estimate, not a live reservation or quote. The pickup address is kept in this demo session and is not sent to a provider. Taxes, fuel, tolls, parking, mileage limits and deposits are not included. Confirm the provider's final price and terms before payment.
                    </p>
                    <a class="cr-trip-card__back" href="./cars.php?city=<?php echo rawurlencode($city); ?>&amp;start=<?php echo rawurlencode($startValue); ?>&amp;end=<?php echo rawurlencode($endValue); ?>">
                        Change car, pickup address or trip dates
                    </a>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include './footer.php'; ?>
