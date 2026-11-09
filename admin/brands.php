<?php
include './header.php';
require_once '../src/Database.php';
require_once '../src/car-rental-view.php';

if (!isset($_SESSION['admin_brand_list_csrf_token']) || !is_string($_SESSION['admin_brand_list_csrf_token'])) {
    $_SESSION['admin_brand_list_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_brand_list_csrf_token'];
$message = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['delete_brand'])) {
    $brandId = is_string($_POST['delete_brand']) && ctype_digit($_POST['delete_brand'])
        ? (int) $_POST['delete_brand']
        : 0;
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';

    if ($brandId < 1 || !hash_equals($csrfToken, $submittedToken)) {
        $error = 'The request expired or the brand selection was invalid. Reload and try again.';
    } else {
        $carCheck = $db->prepare('SELECT id FROM cars WHERE brand = ? LIMIT 1');
        $carCheck->bind_param('i', $brandId);
        $carCheck->execute();

        if ($carCheck->get_result()->fetch_assoc()) {
            $error = 'This brand is assigned to one or more cars and cannot be deleted.';
        } else {
            $brandLookup = $db->prepare('SELECT image FROM brands WHERE id = ? LIMIT 1');
            $brandLookup->bind_param('i', $brandId);
            $brandLookup->execute();
            $brand = $brandLookup->get_result()->fetch_assoc();

            if (!$brand) {
                $error = 'That brand could not be found.';
            } else {
                $delete = $db->prepare('DELETE FROM brands WHERE id = ?');
                $delete->bind_param('i', $brandId);
                $delete->execute();

                if ($delete->affected_rows !== 1) {
                    $error = 'The brand could not be deleted. Please try again.';
                } else {
                    $imageName = basename((string) $brand['image']);
                    $imagePath = __DIR__ . '/uploaded-files/brands/' . $imageName;
                    if ($imageName !== '' && is_file($imagePath)) {
                        if (!unlink($imagePath)) {
                            error_log('Could not remove brand image after brand deletion: ' . $imagePath);
                        }
                    }
                    $message = 'Brand deleted.';
                }
            }
        }
    }
}

$result = $db->query('SELECT id, brand_name, status FROM brands ORDER BY id DESC');
$brands = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid">
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="./dashboard.php">Dashboard</a> / Brands</li>
    </ol>
    <?php if ($message !== ''): ?>
        <div class="alert alert-success" role="status"><?php echo app_escape($message); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert"><?php echo app_escape($error); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-12 text-right mb-2">
            <a class="btn btn-primary font-weight-bold" href="./add-brand.php"><i class="fas fa-plus"></i> Add brand</a>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-table"></i> Brands</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Brand ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($brands as $brand): ?>
                            <tr>
                                <td><?php echo (int) $brand['id']; ?></td>
                                <td><?php echo app_escape($brand['brand_name']); ?></td>
                                <td><?php echo app_escape($brand['status']); ?></td>
                                <td>
                                    <form method="post" action="./brands.php" class="d-inline"
                                        onsubmit="return confirm('Delete this brand? Brands used by cars cannot be deleted.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo app_escape($csrfToken); ?>">
                                        <button type="submit" name="delete_brand" value="<?php echo (int) $brand['id']; ?>"
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
