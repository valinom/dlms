<?php
require_once __DIR__ . '/includes/config.php';

if (!empty($_SESSION['user_id'])) redirect('student/dashboard.php');
if (!empty($_SESSION['admin_id'])) redirect('admin/dashboard.php');

$error = '';

if (isset($_POST['student_login'])) {

    verify_csrf();

    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {

        $stmt = $pdo->prepare("
            SELECT id, user_id, fullname, email, password, status, email_verified
            FROM students
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $student = $stmt->fetch();

        if (!$student) {
            $error = 'No account found with that email.';
        } elseif (!password_verify($password, $student['password'])) {
            $error = 'Incorrect password.';
        } elseif (!(int)$student['email_verified']) {
            $error = 'Your email is not verified. Please check your inbox for the OTP or <a href="register.php">re-register</a>.';
        } elseif (!(int)$student['status']) {
            $error = 'Your account has been blocked. Please contact admin.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id']         = $student['id'];
            $_SESSION['student_id']      = $student['user_id'];
            $_SESSION['student_name']    = $student['fullname'];
            $_SESSION['student_email']   = $student['email'];
            redirect('student/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>DLMS — Digital Library Management System</title>
    
    <meta property="og:title" content="DLMS - Digital Library Management System">
    <meta property="og:description" content="Official library portal for PDUAM, Dalgaon.">
    <meta property="og:image" content="https://iili.io/qvZWKqG.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:url" content="https://dlms.rf.gd">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/variable.css">
    <link rel="stylesheet" href="assets/css/alert.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="assets/css/toast.css">
</head>
<body class="auth-body">

    <div class="auth-card">
        <div class="auth-logo">
            <i class="fa-solid fa-book-open-reader"></i> DLMS
        </div>
        <p class="auth-subtitle">Student Portal — Sign in to access the library</p>

        <?php if (isset($_GET['registered'])): ?>
        <div class="toast toast-success" style="position:static;margin-bottom:.8rem;pointer-events:all;">
            <i class="fa-solid fa-circle-check"></i>
            <span>Registration complete! You can now sign in.</span>
        </div>
        <?php elseif ($success = flash('success')): ?>
        <div class="toast toast-success" style="position:static;margin-bottom:.8rem;pointer-events:all;">
            <i class="fa-solid fa-circle-check"></i>
            <span><?= e($success) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="toast toast-error" style="position:static;margin-bottom:.8rem;pointer-events:all;">
            <i class="fa-solid fa-circle-xmark"></i>
            <span><?= $error ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" id="logForm">

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="you@email.com"
                value="<?= e($_POST['email'] ?? '') ?>"
                autocomplete="email"
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
                    tabindex="-1">
                    <i class="fa-solid fa-eye" id="eyeIcon"></i>
                </button>
            </div>

            <button type="submit" onclick="validateAndSubmit(event)" name="student_login" class="btn-submit">
                <i class="fa-solid fa-right-to-bracket"></i> Log In
            </button>

            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        </form>

        <div class="auth-links" style="margin-top:.8rem">
            <a href="forgot-password.php" style="color:var(--text-muted);font-weight:500">
                <i class="fa-solid fa-lock-open fa-xs"></i> Forgot password?
            </a>
        </div>
        <div class="auth-links">
            Don't have an account? <a href="register.php">Register</a>
            &nbsp;|&nbsp;
            <a href="admin.php"><i class="fa-solid fa-shield-halved"></i> Admin</a>
            <div>
                <a href="about.php">About Us</a>
                &nbsp;|&nbsp;
                <a href="privacy.php">Privacy</a>
            </div>
        </div>
    </div>

    <script src="assets/js/dlms-dialogs.js"></script>
    <script>
        function togglePass() {
            const input = document.getElementById('password');
            const icon  = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fa-solid fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fa-solid fa-eye';
            }
        }

        function validateAndSubmit(event) {
            const form     = document.getElementById('logForm');
            const email    = form.querySelector('[name="email"]').value.trim();
            const password = form.querySelector('[name="password"]').value.trim();
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email) {
                event.preventDefault();
                showAlert('Please enter your email address.', 'warning');
                return false;
            }

            if (!emailPattern.test(email)) {
                event.preventDefault();
                showAlert('Please enter a valid email address.', 'error');
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
