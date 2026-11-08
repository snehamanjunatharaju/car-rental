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
$startValue = $defaultStart->format('Y-m-d\TH:i');
$endValue = $defaultEnd->format('Y-m-d\TH:i');
$city = 'Bengaluru';
$minimumPickup = (new DateTimeImmutable('now'))->format('Y-m-d\TH:i');

$featuredResult = $db->query(
    "SELECT c.*, b.brand_name
     FROM cars c
     LEFT JOIN brands b ON b.id = c.brand
     WHERE c.status = 'Active'
     ORDER BY CAST(c.price_per_day AS DECIMAL(10, 2)) ASC, c.id ASC
     LIMIT 3"
);
$featuredCars = $featuredResult->fetch_all(MYSQLI_ASSOC);
$budgetCarId = $featuredCars ? (int) $featuredCars[0]['id'] : 0;

$fleetResult = $db->query("SELECT COUNT(*) AS car_count FROM cars WHERE status = 'Active'");
$fleetCount = (int) $fleetResult->fetch_assoc()['car_count'];

include './header.php';
?>

<main id="main" class="cr-home">
    <section class="cr-hero" aria-labelledby="hero-title">
        <div class="cr-wrap cr-hero__grid">
            <div class="cr-hero__content">
                <p class="cr-eyebrow cr-eyebrow--light">Good cars. Great getaways.</p>
                <h1 id="hero-title">A better way to <span>get there.</span></h1>
                <p class="cr-hero__copy">
                    Find a self-drive car for your Bengaluru trip. Compare the sample fleet and indicative rates, then
                    choose the dates that work for you.
                </p>
            </div>
            <div class="cr-hero__art">
                <img src="img/car3.png" alt="">
                <div class="cr-hero__note">
                    <strong><?php echo number_format($fleetCount); ?> cars to explore</strong>
                    A little more freedom, right on your doorstep.
                </div>
            </div>
        </div>
    </section>

    <div class="cr-wrap cr-search-wrap">
        <form class="cr-search-panel" action="./cars.php" method="get" data-pickup-address-form>
            <input type="hidden" data-pickup-address-csrf value="<?php echo app_escape($pickupAddressCsrfToken); ?>">
            <div class="cr-search-panel__location">
                <p class="cr-eyebrow">Start your search</p>
                <label for="home-city">Pickup city</label>
                <select id="home-city" name="city" aria-describedby="home-location-status home-location-privacy" required>
                    <option value="Bengaluru" selected>Bengaluru, Karnataka</option>
                </select>
                <button class="cr-location-button" type="button" data-location-button="home-city" data-address-target="home-pickup-address">
                    <span aria-hidden="true">◎</span> Use my location
                </button>
                <span id="home-location-status" data-location-status="home-city" role="status" aria-live="polite">
                    Pickup is currently available in Bengaluru only.
                </span>
                <span id="home-location-privacy">
                    Only on your request, coordinates are sent to <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> to look up an address. The address stays in this app session and is not sent to a rental provider.
                </span>
            </div>
            <div class="cr-field cr-search-panel__location-address cr-pickup-address">
                <label for="home-pickup-address" data-address-label>
                    <?php echo $pickupAddress !== '' ? 'Pickup address (saved for this session — check or edit)' : 'Pickup address (optional)'; ?>
                </label>
                <input id="home-pickup-address" type="text" data-pickup-address maxlength="250"
                    autocomplete="street-address" value="<?php echo app_escape($pickupAddress); ?>"
                    placeholder="Use your location or enter an address">
                <small data-location-accuracy>Use my location to request your device's current location, or enter an address yourself. This demo cannot confirm delivery or a nearby pickup point.</small>
            </div>
            <div class="cr-field">
                <label for="home-start">Pick-up</label>
                <input id="home-start" type="datetime-local" name="start"
                    value="<?php echo app_escape($startValue); ?>"
                    min="<?php echo app_escape($minimumPickup); ?>" required>
            </div>
            <div class="cr-field">
                <label for="home-end">Return</label>
                <input id="home-end" type="datetime-local" name="end"
                    value="<?php echo app_escape($endValue); ?>"
                    min="<?php echo app_escape($minimumPickup); ?>" required>
            </div>
            <button class="cr-button" type="submit">Find a car <span aria-hidden="true">&nbsp;→</span></button>
        </form>
        <p class="cr-pricing-disclaimer">
            Sample Bengaluru fleet and indicative daily estimates only, not live provider availability or quotes. Taxes, fuel, tolls, parking, mileage limits, deposits and pickup terms need provider confirmation.
        </p>
    </div>

        <section class="cr-match-promo" aria-labelledby="match-promo-title">
            <div class="cr-wrap cr-match-promo__inner">
                <div>
                    <p class="cr-eyebrow">A more personal shortlist</p>
                    <h2 id="match-promo-title">Not sure which car fits?</h2>
                    <p>Match your passenger count, budget and trip style to the cars available for your dates—with a clear explanation for every result.</p>
                </div>
                <a class="cr-button" href="./trip-match.php">Find my best fit <span aria-hidden="true">&nbsp;→</span></a>
            </div>
        </section>

        <section class="cr-section cr-section--compact" aria-labelledby="featured-title">
        <div class="cr-wrap">
            <div class="cr-section__head">
                <div>
                    <p class="cr-eyebrow">The fleet</p>
                    <h2 id="featured-title">Find your kind of drive</h2>
                    <p>From easy city runs to room for the whole crew, start with a car that feels right.</p>
                </div>
                <a class="cr-text-link" href="./cars.php?start=<?php echo rawurlencode($startValue); ?>&amp;end=<?php echo rawurlencode($endValue); ?>">
                    Explore all cars <span aria-hidden="true">→</span>
                </a>
            </div>
            <?php if ($featuredCars): ?>
                <div class="cr-car-grid">
                    <?php foreach ($featuredCars as $car): ?>
                        <?php include './partials/car-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="cr-empty">
                    <h2>Our next car is on its way</h2>
                    <p>Check back soon to explore the fleet.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="cr-section cr-story" aria-labelledby="steps-title">
        <div class="cr-wrap">
            <div class="cr-section__head">
                <div>
                    <p class="cr-eyebrow cr-eyebrow--light">Simple by design</p>
                    <h2 id="steps-title">Three steps to your next trip</h2>
                    <p>Less time figuring things out. More time looking forward to the drive.</p>
                </div>
            </div>
            <div class="cr-steps">
                <article class="cr-step">
                    <span class="cr-step__number">01</span>
                    <h3>Choose your dates</h3>
                    <p>Set the pickup and return times that fit your plans.</p>
                </article>
                <article class="cr-step">
                    <span class="cr-step__number">02</span>
                    <h3>Pick your car</h3>
                    <p>Compare the sample fleet, car details and clear per-day estimates.</p>
                </article>
                <article class="cr-step">
                    <span class="cr-step__number">03</span>
                    <h3>Make it a booking</h3>
                    <p>Review your trip details and continue when you are ready.</p>
                </article>
            </div>
        </div>
    </section>
</main>

<?php include './footer.php'; ?>
