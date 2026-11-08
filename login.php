<?php
require_once './src/Database.php';
require_once './src/Session.php';
require_once './src/car-rental-view.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
if (isset($_SESSION['login_message']) && is_string($_SESSION['login_message'])) {
    $message = $_SESSION['login_message'];
    unset($_SESSION['login_message']);
}

$error = '';
$email = '';
if (!isset($_SESSION['login_csrf_token']) || !is_string($_SESSION['login_csrf_token'])) {
    $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['login_csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';

    if (!hash_equals($csrfToken, $submittedToken)) {
        $error = 'Your sign-in session expired. Please try again.';
    } elseif ($email === '') {
        $error = 'Enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif ($password === '') {
        $error = 'Enter your password.';
    } else {
        $statement = $db->prepare('SELECT * FROM customers WHERE email = ? LIMIT 1');
        $statement->bind_param('s', $email);
        $statement->execute();
        $customer = $statement->get_result()->fetch_object();

        if ($customer && password_verify($password, $customer->password)) {
            session_regenerate_id(true);
            Session::set('isLogged', true);
            Session::set('user', $customer);

            $redirectUrl = isset($_SESSION['redirect_url']) && is_string($_SESSION['redirect_url'])
                ? $_SESSION['redirect_url']
                : '';
            unset($_SESSION['redirect_url'], $_SESSION['login_csrf_token']);

            if (
                strpos($redirectUrl, './') === 0
                && strpos($redirectUrl, "\r") === false
                && strpos($redirectUrl, "\n") === false
            ) {
                header('Location: ' . $redirectUrl);
            } else {
                header('Location: ./index.php');
            }
            exit();
        }

        $error = 'Email or password is incorrect.';
    }
}

include './header.php';
?>

<main id="main" class="cr-auth">
    <div class="cr-wrap">
        <section class="cr-auth__card" aria-labelledby="login-title">
            <p class="cr-eyebrow">Welcome back</p>
            <h1 id="login-title">Sign in to continue</h1>
            <p class="cr-auth__intro">Pick up where you left off and get back to planning your drive.</p>

            <?php if ($message !== ''): ?>
                <p class="cr-alert" role="status"><?php echo app_escape($message); ?></p>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <p class="cr-alert" role="alert"><?php echo app_escape($error); ?></p>
            <?php endif; ?>

            <form method="post" action="./login.php" class="cr-auth__form">
                <input type="hidden" name="csrf_token" value="<?php echo app_escape($csrfToken); ?>">
                <div class="cr-field">
                    <label for="login-email">Email address</label>
                    <input type="email" name="email" id="login-email" autocomplete="email"
                        value="<?php echo app_escape($email); ?>" required>
                </div>
                <div class="cr-field">
                    <label for="login-password">Password</label>
                    <input type="password" name="password" id="login-password"
                        autocomplete="current-password" required>
                </div>
                <button class="cr-button" type="submit">Sign in</button>
            </form>

            <p class="cr-auth__register">
                New here? <a href="./register.php">Create an account</a>
            </p>
        </section>
    </div>
</main>

<?php include './footer.php'; ?>
