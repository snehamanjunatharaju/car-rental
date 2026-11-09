<?php
include './header.php';
require_once '../src/Database.php';
require_once '../src/car-rental-view.php';

if (!isset($_SESSION['admin_car_list_csrf_token']) || !is_string($_SESSION['admin_car_list_csrf_token'])) {
    $_SESSION['admin_car_list_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_car_list_csrf_token'];
$message = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['delete_car'])) {
    $carId = is_string($_POST['delete_car']) && ctype_digit($_POST['delete_car'])
        ? (int) $_POST['delete_car']
        : 0;
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';

    if ($carId < 1 || !hash_equals($csrfToken, $submittedToken)) {
        $error = 'The request expired or the car selection was invalid. Reload and try again.';
    } else {
        $bookingCheck = $db->prepare('SELECT id FROM booking WHERE car = ? LIMIT 1');
        $bookingCheck->bind_param('i', $carId);
        $bookingCheck->execute();

        if ($bookingCheck->get_result()->fetch_assoc()) {
            $error = 'This car has booking records and cannot be deleted.';
        } else {
            $carLookup = $db->prepare('SELECT image1, image2, image3 FROM cars WHERE id = ? LIMIT 1');
            $carLookup->bind_param('i', $carId);
            $carLookup->execute();
            $carFiles = $carLookup->get_result()->fetch_assoc();

            if (!$carFiles) {
                $error = 'That car could not be found.';
            } else {
                $delete = $db->prepare('DELETE FROM cars WHERE id = ?');
                $delete->bind_param('i', $carId);
                $delete->execute();

                if ($delete->affected_rows !== 1) {
                    $error = 'The car could not be deleted. Please try again.';
                } else {
                    foreach (['image1', 'image2', 'image3'] as $column) {
                        $fileName = basename((string) $carFiles[$column]);
                        $filePath = __DIR__ . '/uploaded-files/cars/' . $fileName;
                        if ($fileName !== '' && is_file($filePath)) {
                            if (!unlink($filePath)) {
                                error_log('Could not remove car image after car deletion: ' . $filePath);
                            }
                        }
                    }
                    $message = 'Car deleted.';
                }
            }
        }
    }
}

$result = $db->query(
    'SELECT cars.id, cars.car_name, cars.model, cars.transmission, brands.brand_name
     FROM cars
     LEFT JOIN brands ON brands.id = cars.brand
     ORDER BY cars.id DESC'
);
$cars = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid">
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="./dashboard.php">Dashboard</a> / Cars</li>
    </ol>
    <?php if ($message !== ''): ?>
        <div class="alert alert-success" role="status"><?php echo app_escape($message); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert"><?php echo app_escape($error); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-12 text-right mb-2">
            <a class="btn btn-primary font-weight-bold" href="./add-car.php"><i class="fas fa-plus"></i> Add car</a>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-table"></i> Cars</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Car ID</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Transmission</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cars as $car): ?>
                            <tr>
                                <td><?php echo (int) $car['id']; ?></td>
                                <td><?php echo app_escape($car['car_name']); ?></td>
                                <td><?php echo app_escape($car['brand_name'] ?? 'Brand unavailable'); ?></td>
                                <td><?php echo app_escape($car['model']); ?></td>
                                <td><?php echo app_escape($car['transmission']); ?></td>
                                <td>
                                    <a href="./view-car.php?id=<?php echo (int) $car['id']; ?>"
                                        class="btn btn-info btn-sm" aria-label="View car">View</a>
                                    <form method="post" action="./cars.php" class="d-inline"
                                        onsubmit="return confirm('Delete this car and its uploaded images? Cars with booking records cannot be deleted.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo app_escape($csrfToken); ?>">
                                        <button type="submit" name="delete_car" value="<?php echo (int) $car['id']; ?>"
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
