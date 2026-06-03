<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/mailer.php';

if (!empty($_SESSION['user_id'])) redirect('student/dashboard.php');
if (!empty($_SESSION['admin_id'])) redirect('admin/dashboard.php');

$step  = $_SESSION['reg_step'] ?? 1;
$error = '';

/* ── STEP 1: Register ── */
if (isset($_POST['register'])) {
    verify_csrf();
    $fullname  = ucwords(strtolower(trim($_POST['fullname']  ?? '')));
    $email     = strtolower(preg_replace('/\s+/', '', trim($_POST['email'] ?? '')));
    $mobile    = preg_replace('/[^0-9]/', '', trim($_POST['mobile'] ?? ''));
    $dept_id   = (int)($_POST['dept_id'] ?? 0);
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (!$fullname || !$email || !$mobile || !$dept_id || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = 'Please enter a valid mobile number (10 digits).';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
    } else {
        $chk = $pdo->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'Email is already registered.';
        } else {
            $chk2 = $pdo->prepare("SELECT id FROM students WHERE mobile = ? LIMIT 1");
            $chk2->execute([$mobile]);
            if ($chk2->fetch()) {
                $error = 'Mobile number is already registered.';
            } else {
                $hash  = password_hash($password, PASSWORD_DEFAULT);
                $otp   = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $pdo->prepare("INSERT INTO students (fullname, email, mobile, department_id, password, email_verified, status) VALUES (?, ?, ?, ?, ?, 0, 1)")
                    ->execute([$fullname, $email, $mobile, $dept_id, $hash]);
                $userId = (int)$pdo->lastInsertId();
                $sid    = 'STU' . date('Y') . str_pad($userId, 4, '0', STR_PAD_LEFT);
                $pdo->prepare("UPDATE students SET user_id = ? WHERE id = ?")
                    ->execute([$sid, $userId]);
                $pdo->prepare("DELETE FROM email_otps WHERE student_id = ?")->execute([$userId]);
                $pdo->prepare("INSERT INTO email_otps (student_id, otp, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))")
                    ->execute([$userId, $otp]);
                $_SESSION['reg_user_id'] = $userId;
                $_SESSION['reg_email']   = $email;
                $_SESSION['reg_otp']     = (string)$otp;
                $_SESSION['reg_step']    = 2;
                mail_otp($email, $fullname, (string)$otp);
                redirect('register.php');
            }
        }
    }
}

/* ── STEP 2: Verify OTP ── */
if (isset($_POST['verify_otp'])) {
    verify_csrf();
    $enteredOtp = trim($_POST['otp'] ?? '');
    $userId     = (int)($_SESSION['reg_user_id'] ?? 0);
    $sessionOtp = (string)($_SESSION['reg_otp']  ?? '');

    if (!$userId || !$sessionOtp) redirect('register.php');

    if ($enteredOtp === $sessionOtp) {
        $pdo->prepare("UPDATE students SET email_verified = 1 WHERE id = ?")->execute([$userId]);
        $pdo->prepare("DELETE FROM email_otps WHERE student_id = ?")->execute([$userId]);
        unset($_SESSION['reg_step'], $_SESSION['reg_user_id'], $_SESSION['reg_email'], $_SESSION['reg_otp']);
        redirect('index.php?registered=1');
    } else {
        $error = 'Invalid OTP. Please check your email and try again.';
    }
}

/* ── Resend OTP ── */
if (isset($_POST['resend_otp'])) {
    verify_csrf();
    $userId = (int)($_SESSION['reg_user_id'] ?? 0);
    if ($userId) {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $pdo->prepare("DELETE FROM email_otps WHERE student_id = ?")->execute([$userId]);
        $pdo->prepare("INSERT INTO email_otps (student_id, otp, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))")
            ->execute([$userId, $otp]);
        $_SESSION['reg_otp'] = (string)$otp;
        $result = mail_otp($_SESSION['reg_email'] ?? '', 'Student', (string)$otp);
        $_SESSION['reg_msg'] = $result === true
            ? ['type' => 'success', 'text' => 'A new OTP has been sent to your email.']
            : ['type' => 'error',   'text' => 'Failed to send email. Please try again.'];
        redirect('register.php');
    }
}

$step     = $_SESSION['reg_step'] ?? 1;
$regEmail = $_SESSION['reg_email'] ?? '';
$regMsg   = $_SESSION['reg_msg']   ?? null;
unset($_SESSION['reg_msg']);
$depts   = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();
$oldDept = (int)($_POST['dept_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Register | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/variable.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <!-- dialogs MUST be loaded before any inline script that calls showAlert -->
    <script src="assets/js/dlms-dialogs.js"></script>
    <script src="assets/js/select.js" defer></script>
    <style>
        .auth-body { align-items: flex-start; padding-top: 2.5rem; padding-bottom: 3rem; }
        .auth-card { max-width: 460px; }
        .otp-inputs { display:flex; gap:.5rem; justify-content:center; margin:1.2rem 0; }
        .otp-digit {
            width:48px; height:56px; text-align:center;
            font-size:1.5rem; font-weight:800; font-family:inherit;
            border:1.5px solid var(--border-soft); border-radius:10px;
            background:var(--bg-input); color:var(--text-main); outline:none;
            transition:border-color .15s, box-shadow .15s, background .15s;
        }
        .otp-digit:focus { border-color:var(--border-focus); background:#fff; box-shadow:0 0 0 3px hsl(225,70%,55%,.12); }
        .otp-digit.filled { border-color:var(--accent); background:var(--accent-light); }
        .btn-resend {
            width:100%; margin-top:.7rem; padding:.65rem;
            background:var(--bg-hover); color:var(--text-muted);
            border:1px solid var(--border-soft); border-radius:var(--radius-sm);
            font-weight:600; font-size:.88rem; cursor:pointer; font-family:inherit;
            display:flex; align-items:center; justify-content:center; gap:.5rem;
            transition:background .15s, color .15s, border-color .15s;
        }
        .btn-resend:disabled { cursor:not-allowed; color:var(--text-light); }
        .btn-resend:not(:disabled):hover { background:var(--accent-light); color:var(--accent); border-color:var(--border-focus); }
        .msg-banner {
            display:flex; align-items:center; gap:.6rem;
            padding:.65rem .9rem; border-radius:var(--radius-sm);
            font-size:.85rem; margin-bottom:.5rem;
        }
        .msg-banner.success { background:var(--success-bg); border:1px solid hsl(145,45%,72%); color:hsl(145,50%,28%); }
        .msg-banner.error   { background:var(--danger-bg);  border:1px solid hsl(0,50%,85%);   color:var(--danger); }
        @media(max-width:500px) {
            .auth-body { padding:1.5rem 1rem; }
            .auth-card { padding:1.8rem 1.3rem; }
            .otp-digit { width:40px; height:48px; font-size:1.2rem; }
        }
    </style>
</head>
<body class="auth-body">

<?php if ($step === 1): ?>
<!-- ════════ STEP 1: Register ════════ -->
<div class="auth-card">
    <div class="auth-logo"><i class="fa-solid fa-book-open-reader"></i> DLMS</div>
    <p class="auth-subtitle">Create your student account</p>

    <form method="POST" id="regForm">
        <label>Full Name</label>
        <input type="text" name="fullname"
               value="<?= e($_POST['fullname'] ?? '') ?>"
               autocomplete="name" placeholder="e.g. Maria Santos"
               style="text-transform:capitalize">

        <label>Email Address</label>
        <input type="email" name="email"
               value="<?= e($_POST['email'] ?? '') ?>"
               autocomplete="email" placeholder="you@email.com">

        <label>Mobile Number</label>
        <input type="tel" name="mobile"
               value="<?= e($_POST['mobile'] ?? '') ?>"
               autocomplete="tel" placeholder="987xxxxxxx">

        <label>Department</label>
        <div class="custom-select" data-name="dept_id">
            <div class="select-trigger">-- Select Department --</div>
            <ul class="select-options">
                <?php foreach ($depts as $d): ?>
                <li data-value="<?= $d['id'] ?>"><?= e($d['name']) ?></li>
                <?php endforeach; ?>
            </ul>
            <input type="hidden" name="dept_id" value="<?= $oldDept ?: '' ?>">
        </div>

        <label>Password <span style="font-weight:400;font-size:.78rem;color:var(--text-light)">— min 6 characters</span></label>
        <input type="password" name="password" autocomplete="new-password" placeholder="Password">

        <label>Confirm Password</label>
        <input type="password" name="password2" autocomplete="new-password"
        placeholder="Confirm Password">

        <button type="submit" name="register" onclick="validateReg(event)" class="btn-submit">
            <i class="fa-solid fa-user-plus"></i> Create Account
        </button>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    </form>

    <div class="auth-links">
        Already have an account? <a href="index.php">Sign In</a>
        &nbsp;&bull;&nbsp;
        <a href="admin.php" style="color:var(--text-light);font-weight:500">Admin</a>
    </div>
</div>

<!-- Validation + server error — runs AFTER dlms-dialogs.js in <head> -->
<script>
<?php if ($error): ?>
window.addEventListener('load', () => showAlert(<?= json_encode($error) ?>, 'error'));
<?php endif; ?>

function validateReg(e) {
    const form     = document.getElementById('regForm');
    const fullname = form.querySelector('[name="fullname"]').value.trim();
    const emailEl  = form.querySelector('[name="email"]');
    emailEl.value  = emailEl.value.replace(/\s/g, '');
    const email    = emailEl.value.toLowerCase();
    const mobileEl = form.querySelector('[name="mobile"]');
    mobileEl.value = mobileEl.value.replace(/\D/g, '');
    const mobile   = mobileEl.value;
    const dept     = form.querySelector('[name="dept_id"]').value;
    const pass     = form.querySelector('[name="password"]').value;
    const pass2    = form.querySelector('[name="password2"]').value;
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!fullname) {
        e.preventDefault(); return showAlert('Please enter your full name.', 'warning');
    }
    if (!email || !emailPattern.test(email)) {
        e.preventDefault(); return showAlert('Please enter a valid email address.', 'error');
    }
    if (!/^[0-9]{10}$/.test(mobile.replace(/\D/g, ''))) {
        e.preventDefault(); return showAlert('Please enter a valid mobile number (10 digits).', 'error');
    }
    if (!dept) {
        e.preventDefault(); return showAlert('Please select your department.', 'warning');
    }
    if (pass.length < 6) {
        e.preventDefault(); return showAlert('Password must be at least 6 characters.', 'warning');
    }
    if (pass !== pass2) {
        e.preventDefault(); return showAlert('Passwords do not match.', 'error');
    }
}
</script>

<?php else: ?>
<!-- ════════ STEP 2: OTP ════════ -->
<div class="auth-card" style="max-width:420px">
    <div class="auth-logo"><i class="fa-solid fa-envelope-open-text"></i> Verify Email</div>
    <p class="auth-subtitle">
        Enter the 6-digit code sent to<br>
        <strong style="color:var(--text-main)"><?= e($regEmail) ?></strong>
    </p>

    <?php if ($regMsg): ?>
    <div class="msg-banner <?= $regMsg['type'] ?>">
        <i class="fa-solid <?= $regMsg['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
        <span><?= e($regMsg['text']) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" id="otpForm">
        <div class="otp-inputs">
            <?php for ($i = 0; $i < 6; $i++): ?>
            <input type="text" maxlength="1" class="otp-digit"
                   inputmode="numeric" pattern="[0-9]" autocomplete="off">
            <?php endfor; ?>
        </div>
        <input type="hidden" name="otp" id="otpHidden">
        <button type="submit" name="verify_otp" class="btn-submit">
            <i class="fa-solid fa-check-circle"></i> Verify OTP
        </button>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    </form>

    <form method="POST" id="resendForm">
        <button type="submit" name="resend_otp" id="resendBtn" class="btn-resend" disabled>
            <i class="fa-solid fa-clock" id="resendIcon"></i>
            <span id="resendLabel">Resend in <strong id="countdown">60</strong>s</span>
        </button>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    </form>

    <div class="auth-links" style="margin-top:1rem">
        <a href="index.php"><i class="fa-solid fa-arrow-left fa-xs"></i> Back to Sign In</a>
    </div>
</div>

<script>
<?php if ($error): ?>
window.addEventListener('load', () => showAlert(<?= json_encode($error) ?>, 'error'));
<?php endif; ?>

/* OTP digit boxes */
const digits = document.querySelectorAll('.otp-digit');
const hidden = document.getElementById('otpHidden');

digits.forEach((inp, idx) => {
    inp.addEventListener('input', () => {
        inp.value = inp.value.replace(/\D/g, '');
        inp.classList.toggle('filled', inp.value !== '');
        if (inp.value && idx < 5) digits[idx + 1].focus();
        hidden.value = [...digits].map(d => d.value).join('');
    });
    inp.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !inp.value && idx > 0) {
            digits[idx - 1].value = '';
            digits[idx - 1].classList.remove('filled');
            digits[idx - 1].focus();
        }
    });
    inp.addEventListener('paste', e => {
        e.preventDefault();
        const p = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
        p.split('').forEach((c, i) => { if (digits[i]) { digits[i].value = c; digits[i].classList.add('filled'); } });
        hidden.value = [...digits].map(d => d.value).join('');
        if (p.length) digits[Math.min(p.length, 5)].focus();
    });
});
digits[0]?.focus();

document.getElementById('otpForm').addEventListener('submit', function(e) {
    if (hidden.value.length < 6) {
        e.preventDefault();
        return showAlert('Please enter all 6 digits of your OTP.', 'warning');
    }
});

/* Resend countdown */
(function() {
    const btn = document.getElementById('resendBtn');
    const cd  = document.getElementById('countdown');
    const lbl = document.getElementById('resendLabel');
    const ico = document.getElementById('resendIcon');
    let s = 60;
    const t = setInterval(() => {
        s--;
        if (s <= 0) {
            clearInterval(t);
            btn.disabled = false;
            ico.className = 'fa-solid fa-rotate-right';
            lbl.innerHTML = 'Resend OTP';
        } else {
            cd.textContent = s;
        }
    }, 1000);
})();
</script>

<?php endif; ?>

</body>
</html>
