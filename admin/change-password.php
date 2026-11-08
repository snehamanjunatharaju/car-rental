<?php
require_once '../src/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'], $_SESSION['name']) || !is_string($_SESSION['user'])) {
    $_SESSION['error'] = 'You must login first';
    header('Location: ./index.php');
    exit();
}

if (!isset($_SESSION['admin_password_csrf_token']) || !is_string($_SESSION['admin_password_csrf_token'])) {
    $_SESSION['admin_password_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_password_csrf_token'];
$error = '';
$message = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $oldPassword = isset($_POST['old-pass']) && is_string($_POST['old-pass']) ? $_POST['old-pass'] : '';
    $newPassword = isset($_POST['new-pass']) && is_string($_POST['new-pass']) ? $_POST['new-pass'] : '';
    $confirmPassword = isset($_POST['confirm-pass']) && is_string($_POST['confirm-pass'])
        ? $_POST['confirm-pass']
        : '';
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';

    if (!hash_equals($csrfToken, $submittedToken)) {
        $error = 'Your form expired. Reload the page and try again.';
    } elseif ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = 'Enter your current password and both new password fields.';
    } elseif (strlen($newPassword) < 8 || strlen($newPassword) > 72) {
        $error = 'Choose a new password between 8 and 72 characters.';
    } elseif (!hash_equals($newPassword, $confirmPassword)) {
        $error = 'The new password and confirmation do not match.';
    } elseif (hash_equals($oldPassword, $newPassword)) {
        $error = 'Choose a new password that is different from your current password.';
    } else {
        $username = $_SESSION['user'];
        $lookup = $db->prepare('SELECT password FROM users WHERE username = ? LIMIT 1');
        $lookup->bind_param('s', $username);
        $lookup->execute();
        $admin = $lookup->get_result()->fetch_object();

        if (!$admin || !password_verify($oldPassword, $admin->password)) {
            $error = 'The current password is incorrect.';
        } else {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $db->prepare('UPDATE users SET password = ? WHERE username = ?');
            $update->bind_param('ss', $passwordHash, $username);
            $update->execute();
            $message = 'Your password was changed successfully.';
        }
    }
}

include './header.php';
?>

<ol class="breadcrumb" style="margin-top:-16px">
    <li class="breadcrumb-item"><a href="./dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">Change Admin Password</li>
</ol>

<div class="container-fluid">
    <div class="col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($message !== ''): ?>
            <div class="alert alert-success" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="post" action="./change-password.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
            <div class="form-group">
                <label for="old-pass">Current password</label>
                <input id="old-pass" type="password" name="old-pass" class="form-control"
                    autocomplete="current-password" required>
            </div>
            <div class="form-group">
                <label for="new-pass">New password</label>
                <input id="new-pass" type="password" name="new-pass" class="form-control"
                    minlength="8" maxlength="72" autocomplete="new-password" required>
            </div>
            <div class="form-group">
                <label for="confirm-pass">Confirm new password</label>
                <input id="confirm-pass" type="password" name="confirm-pass" class="form-control"
                    minlength="8" maxlength="72" autocomplete="new-password" required>
            </div>
            <button type="submit" name="submit" value="1" class="btn btn-primary float-right">Change password</button>
        </form>
    </div>
</div>

<?php include './footer.php'; ?>
