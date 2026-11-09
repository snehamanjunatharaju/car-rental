<?php
include './header.php';
require_once '../src/Database.php';
require_once '../src/car-rental-view.php';

if (!isset($_SESSION['admin_booking_list_csrf_token']) || !is_string($_SESSION['admin_booking_list_csrf_token'])) {
    $_SESSION['admin_booking_list_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_booking_list_csrf_token'];
$message = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['delete_booking'])) {
    $bookingId = is_string($_POST['delete_booking']) && ctype_digit($_POST['delete_booking'])
        ? (int) $_POST['delete_booking']
        : 0;
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';

    if ($bookingId < 1 || !hash_equals($csrfToken, $submittedToken)) {
        $error = 'The request expired or the booking selection was invalid. Reload and try again.';
    } else {
        $delete = $db->prepare('DELETE FROM booking WHERE id = ?');
        $delete->bind_param('i', $bookingId);
        $delete->execute();
        $message = $delete->affected_rows === 1
            ? 'Booking deleted.'
            : 'That booking could not be found.';
    }
}

$result = $db->query(
    "SELECT b.id, b.booking_id, b.start_date, b.booking_status,
            c.name AS customer_name, ca.car_name
     FROM booking b
     LEFT JOIN customers c ON c.id = b.customer
     LEFT JOIN cars ca ON ca.id = b.car
     WHERE b.booking_status NOT IN ('Completed', 'Cancelled')
     ORDER BY b.id DESC"
);
$bookings = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid">
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="./dashboard.php">Dashboard</a> / Bookings</li>
    </ol>
    <?php if ($message !== ''): ?>
        <div class="alert alert-success" role="status"><?php echo app_escape($message); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert"><?php echo app_escape($error); ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-table"></i> Active and pending bookings</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Customer Name</th>
                            <th>Car</th>
                            <th>Start Trip Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <?php $statusClass = $booking['booking_status'] === 'Booked' ? 'success' : 'info'; ?>
                            <tr>
                                <td><?php echo app_escape($booking['booking_id']); ?></td>
                                <td><?php echo app_escape($booking['customer_name'] ?? 'Customer unavailable'); ?></td>
                                <td><?php echo app_escape($booking['car_name'] ?? 'Car unavailable'); ?></td>
                                <td><?php echo app_escape($booking['start_date']); ?></td>
                                <td><span class="badge badge-<?php echo $statusClass; ?>"><?php echo app_escape($booking['booking_status']); ?></span></td>
                                <td>
                                    <a href="./view-booking.php?id=<?php echo (int) $booking['id']; ?>"
                                        class="btn btn-info btn-sm" aria-label="View booking">View</a>
                                    <form method="post" action="./bookings.php" class="d-inline"
                                        onsubmit="return confirm('Permanently delete this booking?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo app_escape($csrfToken); ?>">
                                        <button type="submit" name="delete_booking" value="<?php echo (int) $booking['id']; ?>"
                                            class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include './footer.php'; ?>
