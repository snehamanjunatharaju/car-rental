<?php
require_once './src/car-rental-view.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$return = isset($_SESSION['cancelled_checkout_return']) && is_array($_SESSION['cancelled_checkout_return'])
    ? $_SESSION['cancelled_checkout_return']
    : null;
unset($_SESSION['cancelled_checkout_return']);

if ($return !== null) {
    $carId = isset($return['car_id']) && is_int($return['car_id']) ? $return['car_id'] : 0;
    $start = isset($return['start']) && is_string($return['start']) ? $return['start'] : '';
    $end = isset($return['end']) && is_string($return['end']) ? $return['end'] : '';
    $city = isset($return['city']) && is_string($return['city'])
        ? app_supported_rental_city($return['city'])
        : null;

    if (
        $carId > 0
        && app_parse_datetime_input($start) !== null
        && app_parse_datetime_input($end) !== null
        && $city !== null
    ) {
        $_SESSION['payment_notice'] = 'Your payment was cancelled. No booking was created. Review this car and choose another payment method if you still want to book.';
        header('Location: ./car-details.php?' . http_build_query([
            'id' => $carId,
            'start' => $start,
            'end' => $end,
            'city' => $city,
        ]));
        exit();
    }
}

include './header.php';
?>
<main id="main" class="cr-account-page">
    <section class="section-bg cr-profile">
        <div class="container cr-profile__container">
            <p class="cr-profile__eyebrow">Checkout</p>
            <h1 class="cr-profile__title">Payment cancelled</h1>
            <p>Your payment was cancelled and no booking was created.</p>
            <a class="btn btn-primary" href="./cars.php">Browse available cars</a>
        </div>
    </section>
</main>
<?php include './footer.php'; ?>
