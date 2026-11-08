<?php
require_once './src/Database.php';
require_once './src/Session.php';
require_once './src/car-rental-view.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['isLogged']) && $_SESSION['isLogged'] === true) {
    header('Location: ./index.php');
    exit();
}

if (!isset($_SESSION['register_csrf_token']) || !is_string($_SESSION['register_csrf_token'])) {
    $_SESSION['register_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['register_csrf_token'];

$errors = [];
$message = '';
$name = '';
$email = '';
$phone = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $name = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) && is_string($_POST['phone']) ? trim($_POST['phone']) : '';
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirmPassword']) && is_string($_POST['confirmPassword'])
        ? $_POST['confirmPassword']
        : '';
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';

    if (!hash_equals($csrfToken, $submittedToken)) {
        $errors[] = 'Your registration form expired. Reload the page and try again.';
    } elseif ($name === '' || strlen($name) > 150) {
        $errors[] = 'Enter your name using 1 to 150 characters.';
    } elseif (strlen($email) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address of 50 characters or fewer.';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = 'Enter a valid 10-digit phone number.';
    } elseif (strlen($password) < 8 || strlen($password) > 72) {
        $errors[] = 'Choose a password between 8 and 72 characters.';
    } elseif (!hash_equals($password, $confirmPassword)) {
        $errors[] = 'The passwords do not match.';
    } else {
        $emailCheck = $db->prepare('SELECT id FROM customers WHERE email = ? LIMIT 1');
        $emailCheck->bind_param('s', $email);
        $emailCheck->execute();

        if ($emailCheck->get_result()->fetch_assoc()) {
            $errors[] = 'An account already uses this email address. Sign in or use another email.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $insert = $db->prepare('INSERT INTO customers (name, email, phone, password) VALUES (?, ?, ?, ?)');
                $insert->bind_param('ssss', $name, $email, $phone, $passwordHash);
                $insert->execute();
                unset($_SESSION['register_csrf_token']);
                $message = 'Registration was successful. Please sign in.';
            } catch (mysqli_sql_exception $exception) {
                if ($exception->getCode() === 1062) {
                    $errors[] = 'An account already uses this email address. Sign in or use another email.';
                } else {
                    error_log('Customer registration failed: ' . $exception->getMessage());
                    $errors[] = 'Registration could not be completed. Please try again.';
                }
            }
        }
    }
}

include './header.php';
?>

<main id="main" class="cr-auth">
    <div class="cr-wrap">
        <section class="cr-auth__card" aria-labelledby="register-title">
            <p class="cr-eyebrow">Get started</p>
            <h1 id="register-title">Create your account</h1>
            <p class="cr-auth__intro">Save your details and keep your trip bookings together.</p>

            <?php if ($message !== ''): ?>
                <p class="cr-alert" role="status"><?php echo app_escape($message); ?></p>
            <?php endif; ?>
            <?php foreach ($errors as $error): ?>
                <p class="cr-alert" role="alert"><?php echo app_escape($error); ?></p>
            <?php endforeach; ?>

            <form method="post" action="./register.php" class="cr-auth__form">
                <input type="hidden" name="csrf_token" value="<?php echo app_escape($csrfToken); ?>">
                <div class="cr-field">
                    <label for="register-name">Name</label>
                    <input type="text" name="name" id="register-name" maxlength="150"
                        autocomplete="name" value="<?php echo app_escape($name); ?>" required>
                </div>
                <div class="cr-field">
                    <label for="register-email">Email address</label>
                    <input type="email" name="email" id="register-email" maxlength="50"
                        autocomplete="email" value="<?php echo app_escape($email); ?>" required>
                </div>
                <div class="cr-field">
                    <label for="register-phone">Phone number</label>
                    <input type="tel" name="phone" id="register-phone" inputmode="numeric"
                        pattern="[0-9]{10}" maxlength="10" autocomplete="tel-national"
                        value="<?php echo app_escape($phone); ?>" required>
                </div>
                <div class="cr-field">
                    <label for="register-password">Password</label>
                    <input type="password" name="password" id="register-password" minlength="8"
                        maxlength="72" autocomplete="new-password" required>
                </div>
                <div class="cr-field">
                    <label for="register-confirm-password">Confirm password</label>
                    <input type="password" name="confirmPassword" id="register-confirm-password"
                        minlength="8" maxlength="72" autocomplete="new-password" required>
                </div>
                <button class="cr-button" type="submit">Create account</button>
            </form>

            <p class="cr-auth__register">
                Already registered? <a href="./login.php">Sign in</a>
            </p>
        </section>
    </div>
</main>

<?php include './footer.php'; ?>
