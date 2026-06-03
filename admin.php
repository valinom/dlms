<?php
require_once __DIR__ . '/includes/config.php';

/* Already logged in → go to dashboard */
if (!empty($_SESSION['admin_id'])) redirect('admin/dashboard.php');
if (!empty($_SESSION['user_id'])) redirect('student/dashboard.php');

$error = '';

if (isset($_POST['admin_login'])) {

    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } else {

        $stmt = $pdo->prepare("SELECT id, username, fullname, password FROM admin WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']       = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_fullname'] = $admin['fullname'];
            redirect('admin/dashboard.php');
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Admin Login | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/variable.css">
    <link rel="stylesheet" href="assets/css/alert.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="assets/css/toast.css">
    <script src="assets/js/dlms-dialogs.js"></script>
</head>
<body class="auth-body">

    <div class="auth-card">
        <div class="auth-logo">
            <i class="fa-solid fa-book-open-reader"></i> DLMS | ADMIN
        </div>
        <p class="auth-subtitle">Admin Portal — Log in to continue</p>

        <?php if ($error): ?>
        <div class="toast toast-error" style="position:static;margin-bottom:.8rem;pointer-events:all;">
            <i class="fa-solid fa-circle-xmark"></i>
            <span><?= e($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" id="logForm">

            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                placeholder="Username"
                value="<?= e($_POST['username'] ?? '') ?>"
                autocomplete="username"
                required
                autofocus
            >

            <label for="password">Password</label>
            <div style="position:relative">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Password"
                    autocomplete="current-password"
                    required
                    style="padding-right:2.6rem"
                >
                <button type="button"
                    onclick="togglePass()"
                    style="position:absolute;right:.7rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:0"
                    tabindex="-1"
                    id="eyeBtn">
                    <i class="fa-solid fa-eye" id="eyeIcon"></i>
                </button>
            </div>

            <button type="submit" onclick="validateAndSubmit(event)" name="admin_login" class="btn-submit">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </button>

            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        </form>

        <div class="auth-links">
            <a href="index.php"><i class="fa-solid fa-arrow-left"></i> Back to Student Portal</a>
        </div>
    </div>

    <script>
        function togglePass() {
            const input = document.getElementById('password');
            const icon  = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type  = 'text';
                icon.className = 'fa-solid fa-eye-slash';
            } else {
                input.type  = 'password';
                icon.className = 'fa-solid fa-eye';
            }
        }
        function validateAndSubmit(event) {
            const form     = document.getElementById('logForm');
            const username = form.querySelector('[name="username"]').value.trim();
            const password = form.querySelector('[name="password"]').value.trim();
            if (!username) {
                event.preventDefault();
                showAlert('Please enter your username.', 'warning');
                return false;
            }

            if (!password) {
                event.preventDefault();
                showAlert('Please enter your password.', 'warning');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
