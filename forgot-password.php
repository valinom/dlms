<?php
require_once 'includes/config.php';
require_once 'includes/mailer.php';

/*Expired OTP Cleanup*/
$pdo->query("DELETE FROM password_resets WHERE expires_at < NOW()");

if (!empty($_SESSION['user_id'])) redirect('student/dashboard.php');

/* STEPS: email → code → reset → done */
$step  = $_SESSION['fp_step'] ?? 'email';
$error = '';

/* ── Restart ── */
if (isset($_GET['restart'])) {
    unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_uid'], $_SESSION['fp_code_id']);
    redirect('forgot-password.php');
}

/* ── STEP 1: Send code ── */
if (isset($_POST['send_code'])) {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT id, fullname, status FROM students WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && (int)$user['status'] === 1) {
            $pdo->prepare("DELETE FROM password_resets WHERE student_id = ?")->execute([$user['id']]);
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $pdo->prepare("INSERT INTO password_resets (student_id, token, expires_at)
                           VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))")
                ->execute([$user['id'], $code]);

            $result = mail_reset_code($email, $user['fullname'], $code);
            if ($result !== true) {
                $error = 'Could not send email. Please try again. (' . $result . ')';
            } else {
                $_SESSION['fp_step']  = 'code';
                $_SESSION['fp_email'] = $email;
                $_SESSION['fp_uid']   = (int)$user['id'];
                $step = 'code';
            }
        } else {
            $_SESSION['fp_step']  = 'code';
            $_SESSION['fp_email'] = $email;
            $_SESSION['fp_uid']   = 0;
            $step = 'code';
        }
    }
}

/* ── STEP 2: Verify code ── */
if (isset($_POST['verify_code'])) {
    verify_csrf();
    $entered = trim($_POST['code'] ?? '');
    $uid     = (int)($_SESSION['fp_uid'] ?? 0);

    if (!preg_match('/^\d{6}$/', $entered)) {
        $error = 'Please enter the 6-digit code.';
        $step  = 'code';
    } elseif ($uid === 0) {
        $error = 'Invalid or expired code. Please try again.';
        $step  = 'code';
    } else {
        $stmt = $pdo->prepare("
            SELECT id FROM password_resets
            WHERE student_id = ? AND token = ? AND used = 0 AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$uid, $entered]);
        $row = $stmt->fetch();

        if (!$row) {
            $error = 'Invalid or expired code. Please try again.';
            $step  = 'code';
        } else {
            $_SESSION['fp_step']    = 'reset';
            $_SESSION['fp_code_id'] = (int)$row['id'];
            $step = 'reset';
        }
    }
}

/* ── STEP 3: Save password ── */
if (isset($_POST['save_password'])) {
    verify_csrf();
    $pass1  = $_POST['password']  ?? '';
    $pass2  = $_POST['password2'] ?? '';
    $uid    = (int)($_SESSION['fp_uid']     ?? 0);
    $codeId = (int)($_SESSION['fp_code_id'] ?? 0);

    if (strlen($pass1) < 6) {
        $error = 'Password must be at least 6 characters.';
        $step  = 'reset';
    } elseif ($pass1 !== $pass2) {
        $error = 'Passwords do not match.';
        $step  = 'reset';
    } elseif ($uid === 0 || $codeId === 0) {
        $error = 'Session expired. Please start again.';
        unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_uid'], $_SESSION['fp_code_id']);
        $step = 'email';
    } else {
        $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?")->execute([$codeId]);
        $pdo->prepare("UPDATE students SET password = ? WHERE id = ?")->execute([password_hash($pass1, PASSWORD_DEFAULT), $uid]);
        unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_uid'], $_SESSION['fp_code_id']);
        $step = 'done';
    }
}

/* ── Resend code ── */
if (isset($_POST['resend_code'])) {
    verify_csrf();
    $uid   = (int)($_SESSION['fp_uid']   ?? 0);
    $email = $_SESSION['fp_email'] ?? '';

    if ($uid > 0 && $email) {
        $pdo->prepare("DELETE FROM password_resets WHERE student_id = ?")->execute([$uid]);
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $pdo->prepare("INSERT INTO password_resets (student_id, token, expires_at)
                       VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))")
            ->execute([$uid, $code]);
        $stmt = $pdo->prepare("SELECT fullname FROM students WHERE id = ?");
        $stmt->execute([$uid]);
        mail_reset_code($email, $stmt->fetchColumn() ?: 'User', $code);
    }
    $_SESSION['fp_resent'] = true;
    redirect('forgot-password.php');
}

$resent = !empty($_SESSION['fp_resent']);
unset($_SESSION['fp_resent']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Forgot Password | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/variable.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <style>
        .auth-body { padding: 2rem 1rem; align-items: flex-start; padding-top: 5vh; }
        .auth-card { max-width: 420px; }

        .banner {
            display: flex; align-items: center; gap: .6rem;
            padding: .65rem .9rem; border-radius: var(--radius-sm);
            font-size: .85rem; margin-bottom: .9rem;
        }
        .banner.error   { background: var(--danger-bg);  border: 1px solid hsl(0,50%,85%);   color: var(--danger); }
        .banner.success { background: var(--success-bg); border: 1px solid hsl(145,45%,72%); color: var(--success); }

        /* Steps */
        .steps { display:flex; align-items:center; justify-content:center; gap:0; margin-bottom:1.6rem; }
        .step-dot {
            width:28px; height:28px; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:.72rem; font-weight:800;
            background:var(--bg-hover); color:var(--text-muted);
            border:2px solid var(--border-soft); transition:all .2s;
        }
        .step-dot.active { background:var(--accent); color:#fff; border-color:var(--accent); }
        .step-dot.done   { background:var(--success-bg); color:var(--success); border-color:hsl(145,45%,72%); }
        .step-line { width:36px; height:2px; background:var(--border-soft); }
        .step-line.done { background:var(--success); }

        /* Code boxes */
        .code-inputs { display:flex; gap:.5rem; justify-content:center; margin:1.2rem 0; }
        .code-inputs input {
            width:48px; height:56px; text-align:center;
            font-size:1.5rem; font-weight:800; font-family:inherit;
            border:1.5px solid var(--border-soft); border-radius:10px;
            background:var(--bg-input); color:var(--text-main);
            outline:none;
            /*caret-color:transparent;*/
            transition: border-color .15s, box-shadow .15s background .15s;
        }
        .code-inputs input:focus { border-color:var(--accent); box-shadow:0 0 0 3px hsl(225,70%,55%,.12); }
        .code-inputs input.filled { border-color:var(--accent); background:var(--accent-light); }

        /* Password strength */
        .pw-strength { margin-top:.4rem; height:4px; border-radius:99px; background:var(--border-soft); overflow:hidden; }
        .pw-strength-bar { height:100%; width:0; border-radius:99px; transition:width .3s, background .3s; }

        /* Done */
        .done-icon { text-align:center; margin-bottom:1.2rem; }
        .done-icon .circle {
            display:inline-flex; align-items:center; justify-content:center;
            width:68px; height:68px; border-radius:50%;
            background:var(--success-bg); color:var(--success);
            font-size:1.8rem; border:2px solid hsl(145,45%,72%);
        }

        .hint { font-size:.8rem; color:var(--text-muted); margin-top:.3rem; }
        .back-link { text-align:center; margin-top:1.2rem; font-size:.85rem; color:var(--text-muted); }
        .back-link a { color:var(--accent); font-weight:600; text-decoration:none; }
        .back-link a:hover { text-decoration:underline; }
        .resend-row { text-align:center; margin-top:.8rem; }
        .resend-btn {
            background:none; border:none; color:var(--accent);
            font-size:.83rem; font-weight:600; cursor:pointer;
            font-family:inherit; padding:0;
        }
        .resend-btn:disabled { color:var(--text-muted); cursor:default; }
    </style>
</head>
<body class="auth-body">
<div class="auth-card">

<?php
$active = ['email'=>1,'code'=>2,'reset'=>3,'done'=>3][$step] ?? 1;
function sdot(int $n, int $a): string {
    if ($n < $a)  return 'done';
    if ($n === $a) return 'active';
    return '';
}
?>

<?php if ($step !== 'done'): ?>
<div class="steps">
    <div class="step-dot <?= sdot(1,$active) ?>"><?= $active>1?'<i class="fa-solid fa-check" style="font-size:.65rem"></i>':'1' ?></div>
    <div class="step-line <?= $active>1?'done':'' ?>"></div>
    <div class="step-dot <?= sdot(2,$active) ?>"><?= $active>2?'<i class="fa-solid fa-check" style="font-size:.65rem"></i>':'2' ?></div>
    <div class="step-line <?= $active>2?'done':'' ?>"></div>
    <div class="step-dot <?= sdot(3,$active) ?>">3</div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="banner error"><i class="fa-solid fa-circle-xmark"></i> <?= e($error) ?></div>
<?php endif; ?>
<?php if ($resent): ?>
<div class="banner success"><i class="fa-solid fa-circle-check"></i> A new code has been sent to your email.</div>
<?php endif; ?>


<!-- ═══════ STEP 1: Email ═══════ -->
<?php if ($step === 'email'): ?>
<div class="auth-logo"><i class="fa-solid fa-lock-open"></i> Forgot Password</div>
<p class="auth-subtitle">Enter your registered email and we'll send you a 6-digit reset code.</p>
<form method="POST">
    <label>Email Address</label>
    <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>"
           required autofocus autocomplete="email" placeholder="you@email.com">
    <button type="submit" name="send_code" class="btn-submit" style="margin-top:1.2rem">
        <i class="fa-solid fa-paper-plane"></i> Send Code
    </button>
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
</form>
<div class="back-link"><a href="index.php"><i class="fa-solid fa-arrow-left fa-xs"></i> Back to Sign In</a></div>


<!-- ═══════ STEP 2: Code ═══════ -->
<?php elseif ($step === 'code'): ?>
<div class="auth-logo"><i class="fa-solid fa-envelope-open-text"></i> Enter Code</div>
<p class="auth-subtitle">
    We sent a 6-digit code to <strong><?= e($_SESSION['fp_email'] ?? '') ?></strong>.
    It expires in <strong>5 minutes</strong>.
</p>
<form method="POST" id="codeForm">
    <div class="code-inputs">
        <input type="text" inputmode="numeric" maxlength="1" class="ci" autofocus>
        <input type="text" inputmode="numeric" maxlength="1" class="ci">
        <input type="text" inputmode="numeric" maxlength="1" class="ci">
        <input type="text" inputmode="numeric" maxlength="1" class="ci">
        <input type="text" inputmode="numeric" maxlength="1" class="ci">
        <input type="text" inputmode="numeric" maxlength="1" class="ci">
    </div>
    <input type="hidden" name="code" id="codeHidden">
    <button type="submit" name="verify_code" id="verifyBtn" class="btn-submit" disabled>
        <i class="fa-solid fa-check"></i> Verify Code
    </button>
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
</form>
<div class="resend-row">
    <form method="POST" style="display:inline">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <button type="submit" name="resend_code" class="resend-btn" id="resendBtn" disabled>
            Resend code <span id="resendTimer"></span>
        </button>
    </form>
</div>
<div class="back-link" style="margin-top:.6rem">
    <a href="forgot-password.php?restart=1"><i class="fa-solid fa-arrow-left fa-xs"></i> Use different email</a>
</div>


<!-- ═══════ STEP 3: New password ═══════ -->
<?php elseif ($step === 'reset'): ?>
<div class="auth-logo"><i class="fa-solid fa-key"></i> New Password</div>
<p class="auth-subtitle">Choose a strong new password for your account.</p>
<form method="POST">
    <label>New Password</label>
    <input type="password" name="password" id="pw1"
           required autofocus autocomplete="new-password"
           placeholder="min 6 characters"
           oninput="checkStrength(this.value)">
    <div class="pw-strength"><div class="pw-strength-bar" id="pwBar"></div></div>
    <p class="hint" id="pwHint"></p>

    <label style="margin-top:.9rem">Confirm Password</label>
    <input type="password" name="password2" required autocomplete="new-password" placeholder="repeat password">

    <button type="submit" name="save_password" class="btn-submit" style="margin-top:1.2rem">
        <i class="fa-solid fa-floppy-disk"></i> Save Password
    </button>
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
</form>


<!-- ═══════ DONE ═══════ -->
<?php elseif ($step === 'done'): ?>
<div class="done-icon"><div class="circle"><i class="fa-solid fa-check"></i></div></div>
<div class="auth-logo" style="text-align:center;margin-bottom:.4rem">Password Reset!</div>
<p class="auth-subtitle" style="text-align:center;margin-bottom:1.4rem">
    Your password has been updated. You can now sign in.
</p>
<a href="index.php" class="btn-submit" style="text-align:center;display:block">
    <i class="fa-solid fa-right-to-bracket"></i> Go to Sign In
</a>
<?php endif; ?>

</div>

<script>
/* Code boxes */
(function(){
    const boxes  = [...document.querySelectorAll('.ci')];
    const hidden = document.getElementById('codeHidden');
    const btn    = document.getElementById('verifyBtn');
    if (!boxes.length) return;

    function sync(){
        const val = boxes.map(b=>b.value).join('');
        if(hidden) hidden.value = val;
        if(btn) btn.disabled = val.length < 6;
        boxes.forEach(b=>b.classList.toggle('filled', b.value!==''));
    }

    boxes.forEach((box,i)=>{
        box.addEventListener('input', ()=>{
            box.value = box.value.replace(/\D/g,'').slice(0,1);
            if(box.value && i<5) boxes[i+1].focus();
            sync();
        });
        box.addEventListener('keydown', e=>{
            if(e.key==='Backspace' && !box.value && i>0){
                boxes[i-1].value=''; boxes[i-1].focus(); sync();
            }
        });
        box.addEventListener('paste', e=>{
            e.preventDefault();
            const digits=(e.clipboardData.getData('text')||'').replace(/\D/g,'').slice(0,6);
            digits.split('').forEach((d,j)=>{ if(boxes[j]) boxes[j].value=d; });
            boxes[Math.min(digits.length,5)].focus();
            sync();
        });
    });
})();

/* Resend countdown */
(function(){
    const btn=document.getElementById('resendBtn');
    const timer=document.getElementById('resendTimer');
    if(!btn) return;
    let s=60;
    const t=setInterval(()=>{
        s--;
        if(timer) timer.textContent='('+s+'s)';
        if(s<=0){ clearInterval(t); btn.disabled=false; if(timer) timer.textContent=''; }
    },1000);
})();

/* Password strength */
function checkStrength(val){
    const bar=document.getElementById('pwBar');
    const hint=document.getElementById('pwHint');
    if(!bar) return;
    let s=0;
    if(val.length>=6) s++;
    if(val.length>=10) s++;
    if(/[A-Z]/.test(val)&&/[a-z]/.test(val)) s++;
    if(/\d/.test(val)) s++;
    if(/[^A-Za-z0-9]/.test(val)) s++;
    const L=[
        {w:'20%',bg:'var(--danger)', l:'Too short'},
        {w:'40%',bg:'var(--danger)', l:'Weak'},
        {w:'60%',bg:'var(--warning)',l:'Fair'},
        {w:'80%',bg:'var(--warning)',l:'Good'},
        {w:'100%',bg:'var(--success)',l:'Strong'},
    ];
    const lv=L[Math.max(0,s-1)]||L[0];
    bar.style.width=val?lv.w:'0';
    bar.style.background=val?lv.bg:'';
    if(hint) hint.textContent=val?lv.l:'';
}
</script>
</body>
</html>
