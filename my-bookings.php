<?php
require_once './src/Database.php';
require_once './src/Session.php';
require_once './src/car-rental-view.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['isLogged']) || $_SESSION['isLogged'] !== true) {
    header('Location: ./login.php');
    exit();
}

$user = Session::get('user');
$statement = $db->prepare(
    'SELECT b.id AS booking_record_id, b.booking_id, b.start_date, b.end_date, b.booking_status,
            b.delivery_status, b.payment_mode, b.payment_status, c.car_name, c.image1
     FROM booking b
     JOIN cars c ON b.car = c.id
     WHERE b.customer = ?
     ORDER BY b.booking_date DESC, b.id DESC'
);
$customerId = (int) $user->id;
$statement->bind_param('i', $customerId);
$statement->execute();
$res = $statement->get_result();

$cars = [];
while ($row = $res->fetch_object()) {
    $cars[] = $row;
}

include './header.php';
?>
<main id="main" class="cr-account-page">
    <section class="section-bg cr-profile">
        <div class="container cr-profile__container">
            <div class="row cr-profile__row">
                <div class="col-lg-12 mx-auto">
                    <p class="cr-profile__eyebrow">Your account</p>
                    <h1 class="cr-profile__title">My bookings</h1>
                    <?php if (isset($_GET['booking']) && $_GET['booking'] === 'cod'): ?>
                        <div class="alert alert-success" role="status">
                            Your booking is confirmed. Payment is due in cash when the vehicle is handed over.
                        </div>
                    <?php endif; ?>
                    <?php if (count($cars) > 0): ?>
                        <?php foreach ($cars as $car): ?>
                            <div class="cr-profile__booking-item">
                                <div class="card mb-3">
                                    <div class="row g-1">
                                        <div class="col-md-4">
                                            <img src="./<?php echo app_escape(app_car_image_url($car->image1)); ?>"
                                                class="img-fluid rounded-start" alt="">
                                        </div>
                                        <div class="col-md-8">
                                            <div class="card-body">
                                                <a href="./view-my-booking.php?id=<?php echo (int) $car->booking_record_id; ?>">
                                                    <h5 class="card-title"><?php echo app_escape($car->car_name); ?></h5>
                                                </a>
                                                <span class="card-text">
                                                    Booking Id: <?php echo app_escape($car->booking_id); ?>
                                                </span><br>
                                                <span class="card-text">
                                                    Start Date: <?php echo app_escape($car->start_date); ?>
                                                </span><br>
                                                <span class="card-text">
                                                    End Date: <?php echo app_escape($car->end_date); ?>
                                                </span>
                                                <p class="card-text">
                                                    <small class="text-muted">Status:</small>
                                                    <span class="badge badge-pill <?php echo $car->booking_status === 'Booked' ? 'badge-success' : 'badge-secondary'; ?>">
                                                        <?php echo app_escape($car->booking_status); ?>
                                                    </span>
                                                </p>
                                                <p class="card-text">
                                                    <small class="text-muted">Payment method:</small>
                                                    <strong><?php echo app_escape(app_payment_method_label($car->payment_mode)); ?></strong>
                                                </p>
                                                <p class="card-text">
                                                    <small class="text-muted">Payment status:</small>
                                                    <strong><?php echo app_escape(app_payment_status_label($car->payment_mode, $car->payment_status)); ?></strong>
                                                </p>
                                                <?php if (strtolower((string) $car->payment_mode) === 'cash_on_delivery' && strtolower((string) $car->payment_status) !== 'success'): ?>
                                                    <p class="card-text">Pay in cash when the vehicle is handed over.</p>
                                                <?php endif; ?>
                                                <?php if ($car->booking_status === 'Booked' && strtolower((string) $car->payment_status) === 'success'): ?>
                                                    <p class="card-text">
                                                        <small class="text-muted">Delivery progress:</small>
                                                        <strong><?php echo app_escape($car->delivery_status); ?></strong>
                                                        <small class="text-muted">(manual update; not live GPS)</small>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <h5 class="text-center">No Booking Yet</h5>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>
<?php include './footer.php'; ?>
