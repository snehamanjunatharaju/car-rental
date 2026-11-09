<?php
require_once '../src/Database.php';
require_once '../src/car-rental-view.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'], $_SESSION['name'])) {
    $_SESSION['error'] = 'You must login first';
    header('Location: ./index.php');
    exit();
}

$id = isset($_GET['id']) && is_string($_GET['id']) ? $_GET['id'] : '';
if (!ctype_digit($id) || (int) $id < 1) {
    http_response_code(400);
    $car = null;
} else {
    $carId = (int) $id;
    $statement = $db->prepare(
        'SELECT c.*, b.brand_name
         FROM cars c
         LEFT JOIN brands b ON b.id = c.brand
         WHERE c.id = ?'
    );
    $statement->bind_param('i', $carId);
    $statement->execute();
    $car = $statement->get_result()->fetch_object();
    if (!$car) {
        http_response_code(404);
    }
}

include './header.php';
?>

<div class="container-fluid">
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="./dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="./cars.php">Cars</a></li>
        <li class="breadcrumb-item active">View car</li>
    </ol>
    <?php if (!$car): ?>
        <div class="alert alert-warning" role="status">That car could not be found.</div>
        <a class="btn btn-primary" href="./cars.php">Back to cars</a>
    <?php else: ?>
        <div class="card">
            <div class="card-header">Car details</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th>Car name</th>
                                <td><?php echo app_escape($car->car_name); ?></td>
                                <th>Brand</th>
                                <td><?php echo app_escape($car->brand_name ?? 'Brand unavailable'); ?></td>
                            </tr>
                            <tr>
                                <th>Vehicle style</th>
                                <td><?php echo app_escape($car->car_body_type); ?></td>
                                <th>Transmission</th>
                                <td><?php echo app_escape($car->transmission); ?></td>
                            </tr>
                            <tr>
                                <th>Fuel type</th>
                                <td><?php echo app_escape($car->fuel_type); ?></td>
                                <th>Model</th>
                                <td><?php echo app_escape($car->model); ?></td>
                            </tr>
                            <tr>
                                <th>Seating capacity</th>
                                <td><?php echo app_escape($car->seating_capacity); ?></td>
                                <th>Indicative price per day</th>
                                <td><?php echo app_escape($car->price_per_day); ?></td>
                            </tr>
                            <tr>
                                <th>Kilometres driven</th>
                                <td><?php echo app_escape($car->km_driven); ?></td>
                                <th>Registration number</th>
                                <td><?php echo app_escape($car->reg_no); ?></td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td colspan="3"><?php echo app_escape($car->description); ?></td>
                            </tr>
                            <?php foreach (['image1', 'image2', 'image3'] as $index => $imageColumn): ?>
                                <?php $imageName = basename((string) $car->$imageColumn); ?>
                                <tr>
                                    <th>Car image <?php echo $index + 1; ?></th>
                                    <td colspan="3">
                                        <?php if ($imageName !== ''): ?>
                                            <a target="_blank" rel="noopener noreferrer"
                                                href="./uploaded-files/cars/<?php echo rawurlencode($imageName); ?>">
                                                View image <?php echo $index + 1; ?>
                                            </a>
                                        <?php else: ?>
                                            <span>Not provided</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include './footer.php'; ?>
