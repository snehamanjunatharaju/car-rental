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
    $customer = null;
} else {
    $customerId = (int) $id;
    $statement = $db->prepare('SELECT * FROM customers WHERE id = ?');
    $statement->bind_param('i', $customerId);
    $statement->execute();
    $customer = $statement->get_result()->fetch_object();
    if (!$customer) {
        http_response_code(404);
    }
}

$message = '';
$error = '';
if (!isset($_SESSION['admin_customer_csrf_token']) || !is_string($_SESSION['admin_customer_csrf_token'])) {
    $_SESSION['admin_customer_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_customer_csrf_token'];

if ($customer && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';
    $status = isset($_POST['status']) && is_string($_POST['status']) ? $_POST['status'] : '';

    if (!hash_equals($csrfToken, $submittedToken)) {
        $error = 'Your form expired. Reload the page and try again.';
    } elseif (!in_array($status, ['Verified', 'Pending'], true)) {
        $error = 'Choose a valid document status.';
    } elseif ($status === 'Verified') {
        $requiredDocuments = [
            'driving_license_image1' => 'DL',
            'driving_license_image2' => 'DL',
            'address_proof_image' => 'address-proof',
        ];
        foreach ($requiredDocuments as $column => $folder) {
            $fileName = basename((string) $customer->$column);
            $filePath = __DIR__ . '/uploaded-files/customer-doc/' . $folder . '/' . $fileName;
            if ($fileName === '' || !is_file($filePath)) {
                $error = 'Upload all required verification documents before marking this customer Verified.';
                break;
            }
        }

        if ($error === '') {
            $update = $db->prepare('UPDATE customers SET is_verified = ? WHERE id = ?');
            $update->bind_param('si', $status, $customerId);
            $update->execute();
            $customer->is_verified = $status;
            $message = 'Document status updated.';
        }
    } else {
        $update = $db->prepare('UPDATE customers SET is_verified = ? WHERE id = ?');
        $update->bind_param('si', $status, $customerId);
        $update->execute();
        $customer->is_verified = $status;
        $message = 'Document status updated.';
    }
}

$frontImage = $customer ? basename((string) $customer->driving_license_image1) : '';
$backImage = $customer ? basename((string) $customer->driving_license_image2) : '';
$addressProofImage = $customer ? basename((string) $customer->address_proof_image) : '';
include './header.php';
?>

<div class="container-fluid">
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="./dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="./customers.php">Users</a></li>
        <li class="breadcrumb-item active">Customer verification</li>
    </ol>

    <?php if (!$customer): ?>
        <div class="alert alert-warning" role="status">That customer could not be found.</div>
        <a class="btn btn-primary" href="./customers.php">Back to users</a>
    <?php else: ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><?php echo app_escape($error); ?></div>
        <?php elseif ($message !== ''): ?>
            <div class="alert alert-success" role="status"><?php echo app_escape($message); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <tbody>
                        <tr>
                            <th>Status</th>
                            <td><?php echo app_escape($customer->is_verified); ?></td>
                            <th>Name</th>
                            <td><?php echo app_escape($customer->name); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo app_escape($customer->email); ?></td>
                            <th>Phone</th>
                            <td><?php echo app_escape($customer->phone); ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td colspan="3"><?php echo app_escape($customer->address); ?></td>
                        </tr>
                        <tr>
                            <th>Driving licence number</th>
                            <td colspan="3"><?php echo app_escape($customer->driving_license_no); ?></td>
                        </tr>
                        <tr>
                            <th>Driving licence images</th>
                            <td colspan="3" class="image-box">
                                <?php if ($frontImage !== ''): ?>
                                    <a href="./uploaded-files/customer-doc/DL/<?php echo rawurlencode($frontImage); ?>">
                                        <?php if (app_profile_document_is_image($frontImage)): ?>
                                            <img src="./uploaded-files/customer-doc/DL/<?php echo rawurlencode($frontImage); ?>" alt="Driving licence front" class="image">
                                        <?php else: ?>
                                            View driving licence front document
                                        <?php endif; ?>
                                    </a>
                                <?php else: ?>
                                    <span>No front image uploaded</span>
                                <?php endif; ?>
                                <?php if ($backImage !== ''): ?>
                                    <a href="./uploaded-files/customer-doc/DL/<?php echo rawurlencode($backImage); ?>">
                                        <?php if (app_profile_document_is_image($backImage)): ?>
                                            <img src="./uploaded-files/customer-doc/DL/<?php echo rawurlencode($backImage); ?>" alt="Driving licence back" class="image">
                                        <?php else: ?>
                                            View driving licence back document
                                        <?php endif; ?>
                                    </a>
                                <?php else: ?>
                                    <span>No back image uploaded</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Address proof number</th>
                            <td colspan="3">
                                <?php echo app_escape($customer->address_proof_type ?? ''); ?>
                                <?php echo app_escape($customer->address_proof_no); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Address proof image</th>
                            <td colspan="3">
                                <?php if ($addressProofImage !== ''): ?>
                                    <a href="./uploaded-files/customer-doc/address-proof/<?php echo rawurlencode($addressProofImage); ?>">
                                        <?php if (app_profile_document_is_image($addressProofImage)): ?>
                                            <img src="./uploaded-files/customer-doc/address-proof/<?php echo rawurlencode($addressProofImage); ?>" alt="Address proof" class="image">
                                        <?php else: ?>
                                            View address proof document
                                        <?php endif; ?>
                                    </a>
                                <?php else: ?>
                                    <span>No address proof image uploaded</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <form class="mt-4" method="post" action="./view-customer.php?id=<?php echo $customerId; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo app_escape($csrfToken); ?>">
            <div class="form-group">
                <label for="customer-status">Document status</label>
                <select id="customer-status" name="status" class="form-control">
                    <option value="Pending" <?php echo $customer->is_verified === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Verified" <?php echo $customer->is_verified === 'Verified' ? 'selected' : ''; ?>>
                        Verified
                    </option>
                </select>
            </div>
            <p class="form-text text-muted">
                All required documents must be uploaded before verification can be approved.
            </p>
            <button type="submit" class="btn btn-primary btn-sm">Save document status</button>
        </form>
    <?php endif; ?>
</div>

<?php include './footer.php'; ?>
