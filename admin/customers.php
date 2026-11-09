<?php
include './header.php';
require_once '../src/Database.php';
require_once '../src/car-rental-view.php';

if (!isset($_SESSION['admin_customer_list_csrf_token']) || !is_string($_SESSION['admin_customer_list_csrf_token'])) {
    $_SESSION['admin_customer_list_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_customer_list_csrf_token'];
$message = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['delete_customer'])) {
    $customerId = is_string($_POST['delete_customer']) && ctype_digit($_POST['delete_customer'])
        ? (int) $_POST['delete_customer']
        : 0;
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';

    if ($customerId < 1 || !hash_equals($csrfToken, $submittedToken)) {
        $error = 'The request expired or the customer selection was invalid. Reload and try again.';
    } else {
        $bookingCheck = $db->prepare('SELECT id FROM booking WHERE customer = ? LIMIT 1');
        $bookingCheck->bind_param('i', $customerId);
        $bookingCheck->execute();

        if ($bookingCheck->get_result()->fetch_assoc()) {
            $error = 'This customer has booking records and cannot be deleted.';
        } else {
            $customerLookup = $db->prepare(
                'SELECT driving_license_image1, driving_license_image2, address_proof_image
                 FROM customers WHERE id = ? LIMIT 1'
            );
            $customerLookup->bind_param('i', $customerId);
            $customerLookup->execute();
            $customerFiles = $customerLookup->get_result()->fetch_assoc();

            if (!$customerFiles) {
                $error = 'That customer could not be found.';
            } else {
                $delete = $db->prepare('DELETE FROM customers WHERE id = ?');
                $delete->bind_param('i', $customerId);
                $delete->execute();

                if ($delete->affected_rows !== 1) {
                    $error = 'The customer could not be deleted. Please try again.';
                } else {
                    foreach ([
                        'driving_license_image1' => 'DL',
                        'driving_license_image2' => 'DL',
                        'address_proof_image' => 'address-proof',
                    ] as $column => $folder) {
                        $fileName = basename((string) $customerFiles[$column]);
                        $filePath = __DIR__ . '/uploaded-files/customer-doc/' . $folder . '/' . $fileName;
                        if ($fileName !== '' && is_file($filePath)) {
                            if (!unlink($filePath)) {
                                error_log('Could not remove customer document after customer deletion: ' . $filePath);
                            }
                        }
                    }
                    $message = 'Customer deleted.';
                }
            }
        }
    }
}

$result = $db->query('SELECT id, name, email, phone, is_verified, created_at FROM customers ORDER BY id DESC');
$customers = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid">
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="./dashboard.php">Dashboard</a> / Users</li>
    </ol>
    <?php if ($message !== ''): ?>
        <div class="alert alert-success" role="status"><?php echo app_escape($message); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert"><?php echo app_escape($error); ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-table"></i> User Table</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <?php $statusClass = $customer['is_verified'] === 'Verified' ? 'success' : 'danger'; ?>
                            <tr>
                                <td><?php echo (int) $customer['id']; ?></td>
                                <td><?php echo app_escape($customer['name']); ?></td>
                                <td><?php echo app_escape($customer['email']); ?></td>
                                <td><?php echo app_escape($customer['phone']); ?></td>
                                <td><span class="badge badge-<?php echo $statusClass; ?>"><?php echo app_escape($customer['is_verified']); ?></span></td>
                                <td>
                                    <a href="./view-customer.php?id=<?php echo (int) $customer['id']; ?>"
                                        class="btn btn-info btn-sm" aria-label="View customer">View</a>
                                    <form method="post" action="./customers.php" class="d-inline"
                                        onsubmit="return confirm('Delete this customer and their uploaded documents? Customers with bookings cannot be deleted.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo app_escape($csrfToken); ?>">
                                        <button type="submit" name="delete_customer" value="<?php echo (int) $customer['id']; ?>"
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
