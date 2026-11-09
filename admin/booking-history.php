<?php
include './header.php';
require_once '../src/Database.php';
require_once '../src/car-rental-view.php';

$result = $db->query(
    "SELECT b.id, b.booking_id, b.start_date, b.booking_status,
            c.name AS customer_name, ca.car_name
     FROM booking b
     LEFT JOIN customers c ON c.id = b.customer
     LEFT JOIN cars ca ON ca.id = b.car
     WHERE b.booking_status IN ('Completed', 'Cancelled')
     ORDER BY b.id DESC"
);
$bookings = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid">
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="./dashboard.php">Dashboard</a> / Booking History</li>
    </ol>
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-table"></i> Completed and cancelled bookings</div>
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
                            <?php $statusClass = $booking['booking_status'] === 'Completed' ? 'success' : 'secondary'; ?>
                            <tr>
                                <td><?php echo app_escape($booking['booking_id']); ?></td>
                                <td><?php echo app_escape($booking['customer_name'] ?? 'Customer unavailable'); ?></td>
                                <td><?php echo app_escape($booking['car_name'] ?? 'Car unavailable'); ?></td>
                                <td><?php echo app_escape($booking['start_date']); ?></td>
                                <td><span class="badge badge-<?php echo $statusClass; ?>"><?php echo app_escape($booking['booking_status']); ?></span></td>
                                <td>
                                    <a href="./view-booking.php?id=<?php echo (int) $booking['id']; ?>"
                                        class="btn btn-info btn-sm" aria-label="View booking">View</a>
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
