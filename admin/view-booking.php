<?php
require_once '../src/Database.php';
require_once '../src/car-rental-view.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || !isset($_SESSION['name'])) {
    $_SESSION['error'] = 'You must login first';
    header('Location: ./index.php');
    exit();
}

$id = isset($_GET['id']) && is_string($_GET['id']) ? $_GET['id'] : '';
if (!ctype_digit($id) || (int) $id < 1) {
    http_response_code(400);
    $booking = null;
} else {
    $bookingId = (int) $id;
    $statement = $db->prepare(
        'SELECT b.*, c.name AS customer_name, ca.car_name, ca.car_body_type,
                ca.price_per_day, ca.km_driven, ca.reg_no
         FROM booking b
         LEFT JOIN customers c ON b.customer = c.id
         LEFT JOIN cars ca ON b.car = ca.id
         WHERE b.id = ?'
    );
    $statement->bind_param('i', $bookingId);
    $statement->execute();
    $booking = $statement->get_result()->fetch_object();

    if (!$booking) {
        http_response_code(404);
    }
}

$allowedStatuses = ['Booked', 'Live', 'Completed'];
$deliveryStatuses = app_delivery_status_steps();
$message = '';

if (!isset($_SESSION['admin_booking_csrf_token']) || !is_string($_SESSION['admin_booking_csrf_token'])) {
    $_SESSION['admin_booking_csrf_token'] = bin2hex(random_bytes(32));
}

if ($booking && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_cash_received'])) {
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';

    if (!hash_equals($_SESSION['admin_booking_csrf_token'], $submittedToken)) {
        $message = 'Your session expired. Reload the page and try again.';
    } elseif (
        strtolower((string) $booking->payment_mode) !== 'cash_on_delivery'
        || strtolower((string) $booking->payment_status) === 'success'
        || $booking->booking_status !== 'Booked'
    ) {
        $message = 'Cash can only be confirmed for an unpaid, confirmed cash-on-delivery booking.';
    } else {
        $paymentDate = (new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata')))
            ->format('Y-m-d H:i:s');
        $paidStatus = 'success';
        $update = $db->prepare(
            "UPDATE booking
             SET payment_status = ?, payment_date = ?
             WHERE id = ? AND payment_mode = 'cash_on_delivery'
               AND booking_status = 'Booked' AND LOWER(payment_status) <> 'success'"
        );
        $update->bind_param('ssi', $paidStatus, $paymentDate, $bookingId);
        $update->execute();

        if ($update->affected_rows !== 1) {
            $message = 'Payment was not changed. Reload the booking and check its current status.';
        } else {
            header('Location: ./view-booking.php?id=' . $bookingId . '&payment_updated=1');
            exit();
        }
    }
}

if ($booking && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_status'])) {
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';
    $newStatus = is_string($_POST['booking_status']) ? $_POST['booking_status'] : '';

    if (!hash_equals($_SESSION['admin_booking_csrf_token'], $submittedToken)) {
        $message = 'Your session expired. Reload the page and try again.';
    } elseif (!in_array($newStatus, $allowedStatuses, true)) {
        $message = 'Choose a valid booking status.';
    } elseif ($newStatus === 'Completed' && strtolower((string) $booking->payment_status) !== 'success') {
        $message = 'A booking cannot be completed until its payment is confirmed.';
    } else {
        $update = $db->prepare('UPDATE booking SET booking_status = ? WHERE id = ?');
        $update->bind_param('si', $newStatus, $bookingId);
        $update->execute();
        header('Location: ./view-booking.php?id=' . $bookingId . '&updated=1');
        exit();
    }
}

if ($booking && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delivery_status'])) {
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';
    $newDeliveryStatus = is_string($_POST['delivery_status']) ? $_POST['delivery_status'] : '';

    if (!hash_equals($_SESSION['admin_booking_csrf_token'], $submittedToken)) {
        $message = 'Your session expired. Reload the page and try again.';
    } elseif (!in_array($newDeliveryStatus, $deliveryStatuses, true)) {
        $message = 'Choose a valid delivery progress status.';
    } elseif ($booking->booking_status !== 'Booked' || strtolower((string) $booking->payment_status) !== 'success') {
        $message = 'Delivery progress can only be updated for a paid, confirmed booking.';
    } else {
        $update = $db->prepare('UPDATE booking SET delivery_status = ? WHERE id = ?');
        $update->bind_param('si', $newDeliveryStatus, $bookingId);
        $update->execute();
        header('Location: ./view-booking.php?id=' . $bookingId . '&delivery_updated=1');
        exit();
    }
}

if (!isset($_SESSION['admin_booking_csrf_token']) || !is_string($_SESSION['admin_booking_csrf_token'])) {
    $_SESSION['admin_booking_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_booking_csrf_token'];

include './header.php';
?>

<div class="container-fluid">
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="./dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="./bookings.php">Bookings</a></li>
        <li class="breadcrumb-item active">View booking</li>
    </ol>

    <?php if (!$booking): ?>
        <div class="alert alert-warning" role="status">That booking could not be found.</div>
        <a class="btn btn-primary" href="./bookings.php">Back to bookings</a>
    <?php else: ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success" role="status">Booking lifecycle status updated.</div>
        <?php endif; ?>
        <?php if (isset($_GET['delivery_updated'])): ?>
            <div class="alert alert-success" role="status">Customer delivery progress updated.</div>
        <?php endif; ?>
        <?php if (isset($_GET['payment_updated'])): ?>
            <div class="alert alert-success" role="status">Cash receipt recorded. Delivery progress can now be updated.</div>
        <?php endif; ?>
        <?php if ($message !== ''): ?>
            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">Booking details</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th>Booking status</th>
                                <td><?php echo htmlspecialchars((string) $booking->booking_status, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                <th>Delivery progress</th>
                                <td><?php echo htmlspecialchars((string) $booking->delivery_status, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            </tr>
                            <tr>
                                <th>Booking ID</th>
                                <td><?php echo htmlspecialchars((string) $booking->booking_id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                <th>Payment method</th>
                                <td><?php echo htmlspecialchars(app_payment_method_label($booking->payment_mode), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            </tr>
                            <tr>
                                <th>Payment status</th>
                                <td><?php echo htmlspecialchars(app_payment_status_label($booking->payment_mode, $booking->payment_status), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                <th>Payment ID</th>
                                <td><?php echo htmlspecialchars((string) ($booking->payment_id ?? 'Not provided'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            </tr>
                            <tr>
                                <th>Customer</th>
                                <td><?php echo htmlspecialchars((string) ($booking->customer_name ?? 'Customer unavailable'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                <th>Car</th>
                                <td><?php echo htmlspecialchars((string) ($booking->car_name ?? 'Car unavailable'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            </tr>
                            <tr>
                                <th>Trip starts</th>
                                <td><?php echo htmlspecialchars((string) $booking->start_date, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                <th>Trip ends</th>
                                <td><?php echo htmlspecialchars((string) $booking->end_date, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            </tr>
                            <tr>
                                <th>Total price</th>
                                <td><?php echo htmlspecialchars((string) $booking->total_price, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                <th>Payment date</th>
                                <td><?php echo htmlspecialchars((string) ($booking->payment_date ?: 'Not received'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (strtolower((string) $booking->payment_mode) === 'cash_on_delivery' && strtolower((string) $booking->payment_status) !== 'success' && $booking->booking_status === 'Booked'): ?>
            <div class="card mt-3">
                <div class="card-header">Cash on delivery</div>
                <div class="card-body">
                    <p>Only confirm this after the rental team has physically received the cash from the customer.</p>
                    <form method="post" action="./view-booking.php?id=<?php echo $bookingId; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-success" name="confirm_cash_received" value="1">
                            Confirm cash received
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="card mt-3">
            <div class="card-header">Car details</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th>Vehicle style</th>
                                <td><?php echo htmlspecialchars((string) ($booking->car_body_type ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                <th>Price per day</th>
                                <td><?php echo htmlspecialchars((string) ($booking->price_per_day ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            </tr>
                            <tr>
                                <th>Kilometres driven</th>
                                <td><?php echo htmlspecialchars((string) ($booking->km_driven ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                <th>Registration</th>
                                <td><?php echo htmlspecialchars((string) ($booking->reg_no ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">Update customer delivery progress</div>
            <div class="card-body">
                <p>These are manual updates, not live driver GPS. Only mark “On the way” when the vehicle has actually departed.</p>
                <form method="post" action="./view-booking.php?id=<?php echo $bookingId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-8">
                            <label for="delivery-status">Delivery progress</label>
                            <select class="form-control" id="delivery-status" name="delivery_status"
                                <?php echo $booking->booking_status !== 'Booked' || strtolower((string) $booking->payment_status) !== 'success' ? 'disabled' : ''; ?>>
                                <?php foreach ($deliveryStatuses as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php echo $booking->delivery_status === $status ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($booking->booking_status !== 'Booked' || strtolower((string) $booking->payment_status) !== 'success'): ?>
                                <small class="form-text text-muted">Progress is available only after payment succeeds and the booking is confirmed.</small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group col-md-4">
                            <button type="submit" class="btn btn-primary"
                                <?php echo $booking->booking_status !== 'Booked' || strtolower((string) $booking->payment_status) !== 'success' ? 'disabled' : ''; ?>>
                                Save delivery progress
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">Update booking status</div>
            <div class="card-body">
                <form method="post" action="./view-booking.php?id=<?php echo $bookingId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-8">
                            <label for="booking-status">Status</label>
                            <select class="form-control" id="booking-status" name="booking_status">
                                <?php foreach ($allowedStatuses as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php echo $booking->booking_status === $status ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <button type="submit" class="btn btn-primary">Save status</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include './footer.php'; ?>
