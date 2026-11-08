<?php
require_once '../src/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user'], $_SESSION['name'])) {
    header('Location: ./dashboard.php');
    exit();
}

if (!isset($_SESSION['admin_login_csrf_token']) || !is_string($_SESSION['admin_login_csrf_token'])) {
    $_SESSION['admin_login_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_login_csrf_token'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) && is_string($_POST['username'])
        ? trim($_POST['username'])
        : '';
    $password = isset($_POST['password']) && is_string($_POST['password'])
        ? $_POST['password']
        : '';
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';

    if (!hash_equals($csrfToken, $submittedToken)) {
        $error = 'Your sign-in session expired. Please try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Enter your username and password.';
    } else {
        $statement = $db->prepare('SELECT username, password, name FROM users WHERE username = ? LIMIT 1');
        $statement->bind_param('s', $username);
        $statement->execute();
        $user = $statement->get_result()->fetch_object();

        if ($user && password_verify($password, $user->password)) {
            session_regenerate_id(true);
            $_SESSION['user'] = $user->username;
            $_SESSION['name'] = $user->name;
            unset($_SESSION['admin_login_csrf_token']);
            header('Location: ./dashboard.php');
            exit();
        }

        $error = 'Username or password is incorrect.';
    }
}

if (isset($_SESSION['error']) && is_string($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin - Login</title>
    <link href="./resources/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="./resources/css/sb-admin.css" rel="stylesheet">
</head>

<body class="bg-dark">
    <div class="container">
        <div class="card card-login mx-auto" style="margin-top: 25vh">
            <div class="card-header text-center font-weight-bold">Admin Panel Login</div>
            <div class="card-body">
                <form id="formLogin" method="post" action="./index.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" id="username" class="form-control"
                            placeholder="Enter username" autocomplete="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control"
                            placeholder="Enter password" autocomplete="current-password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Sign in</button>
                </form>
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger mt-3" role="alert">
                        <?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="./resources/vendor/jquery/jquery.min.js"></script>
    <script src="./resources/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>
