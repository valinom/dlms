<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/config.php';

/* ─────────────────────────────────────────────
   SUPERUSER — Admin Management
   Access: /superuser/   (URL-only, no nav link)
   Protected by a hardcoded superuser password.
   ───────────────────────────────────────────── */

define('SU_PASSWORD', 'DLMS@su2025$d@pduam#780');//su password

$authed  = !empty($_SESSION['su_authed']);
$error   = '';
$success = '';

/* Read and clear flash */
$flash   = $_SESSION['su_flash'] ?? null;
unset($_SESSION['su_flash']);
if ($flash) {
    if ($flash['type'] === 'success') $success = $flash['msg'];
    else                              $error   = $flash['msg'];
}

/* ── Logout ── */
if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, 
            $params["path"], $params["domain"], true, $params["httponly"]
        );
    }
    session_destroy();
    redirect('../index.php');
}

/* ── Login ── */
if (!$authed && isset($_POST['su_login'])) {
    $pass = $_POST['su_pass'] ?? '';
    if (hash_equals(SU_PASSWORD, $pass)) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            session_id(), 
            0,
            $params["path"], 
            $params["domain"], 
            true, 
            $params["httponly"]
        );

        $_SESSION['su_authed'] = true;
        redirect('index.php');
    } else {
        $error = 'Incorrect superuser password.';
        sleep(1);
    }
}


/* ── Admin actions (only when authed) ── */
if ($authed) {

    /* Add admin */
    if (isset($_POST['add_admin'])) {
        $username = trim($_POST['username'] ?? '');
        $fullname = trim($_POST['fullname'] ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (!$username || !$fullname || !$email || !$password) {
            $error = 'All fields are required.';
        } elseif (!preg_match('/^[a-z0-9_]{3,30}$/i', $username)) {
            $error = 'Username must be 3–30 characters (letters, numbers, underscore only).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $chk = $pdo->prepare("SELECT id FROM admin WHERE username = ? OR email = ?");
            $chk->execute([$username, $email]);
            if ($chk->fetch()) {
                $error = 'Username or email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO admin (username, fullname, email, password) VALUES (?, ?, ?, ?)")
                    ->execute([$username, $fullname, $email, $hash]);
                $_SESSION['su_flash'] = ['type' => 'success', 'msg' => "Admin <strong>" . htmlspecialchars($username) . "</strong> created successfully."];
                redirect('index.php');
            }
        }
    }

    /* Delete admin */
    if (isset($_POST['delete_admin'])) {
        $id    = (int)($_POST['admin_id'] ?? 0);
        $count = (int)$pdo->query("SELECT COUNT(*) FROM admin")->fetchColumn();
        if ($count <= 1) {
            $_SESSION['su_flash'] = ['type' => 'error', 'msg' => 'Cannot delete the last admin account.'];
        } elseif ($id > 0) {
            $pdo->prepare("DELETE FROM admin WHERE id = ?")->execute([$id]);
            $_SESSION['su_flash'] = ['type' => 'success', 'msg' => 'Admin deleted.'];
        }
        redirect('index.php');
    }

    /* Reset password */
    if (isset($_POST['reset_password'])) {
        $id      = (int)($_POST['admin_id'] ?? 0);
        $newpass = $_POST['new_password'] ?? '';
        if (strlen($newpass) < 6) {
            $_SESSION['su_flash'] = ['type' => 'error', 'msg' => 'New password must be at least 6 characters.'];
        } elseif ($id > 0) {
            $hash = password_hash($newpass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?")->execute([$hash, $id]);
            $_SESSION['su_flash'] = ['type' => 'success', 'msg' => 'Password reset successfully.'];
        }
        redirect('index.php');
    }

    /* Fetch all admins */
    $admins = $pdo->query("SELECT id, username, fullname, email, created_at FROM admin ORDER BY id ASC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Superuser | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Inter, system-ui, sans-serif;
            background: var(--bg-main); color: var(--text-main);
            min-height: 100vh; padding: 2rem 1rem 3rem;
        }
        .wrap { max-width: 680px; margin: 0 auto; }

        /* Header */
        .su-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.8rem; flex-wrap: wrap; gap: .75rem;
        }
        .su-logo {
            display: flex; align-items: center; gap: .6rem;
            font-size: 1.1rem; font-weight: 900; color: var(--text-main);
        }
        .su-logo .icon {
            width: 36px; height: 36px; border-radius: 10px;
            background: var(--text-main); color: #fff;
            display: flex; align-items: center; justify-content: center; font-size: .9rem;
        }
        .su-logo .sub { font-size: .72rem; font-weight: 600; color: var(--text-muted); display: block; letter-spacing: .03em; }
        .btn-logout {
            display: inline-flex; align-items: center; gap: .35rem;
            padding: .45rem .9rem; background: var(--bg-card);
            border: 1px solid var(--border-soft); border-radius: 8px;
            font-size: .82rem; font-weight: 600; color: var(--text-muted);
            cursor: pointer; text-decoration: none; font-family: inherit;
            transition: background .15s, color .15s;
        }
        .btn-logout:hover { background: var(--danger-bg); color: var(--danger); border-color: hsl(0,50%,82%); }

        /* Card */
        .card {
            background: var(--bg-card); border: 1px solid var(--border-soft);
            border-radius: var(--radius); padding: 1.6rem 1.8rem;
            box-shadow: var(--shadow-sm); margin-bottom: 1.2rem;
        }
        .card-title {
            font-size: .8rem; font-weight: 800; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: .06em;
            margin-bottom: 1.1rem; display: flex; align-items: center; gap: .5rem;
        }
        .card-title i { color: var(--accent); }

        /* Login card */
        .login-wrap { max-width: 380px; margin: 6vh auto 0; }
        .login-logo {
            text-align: center; margin-bottom: 1.6rem;
        }
        .login-logo .icon {
            width: 56px; height: 56px; border-radius: 14px;
            background: var(--text-main); color: #fff;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.4rem; margin-bottom: .6rem;
        }
        .login-logo h1 { font-size: 1.1rem; font-weight: 900; }
        .login-logo p  { font-size: .82rem; color: var(--text-muted); margin-top: .2rem; }

        /* Inputs */
        label {
            display: block; font-size: .75rem; font-weight: 700;
            color: var(--text-muted); text-transform: uppercase;
            letter-spacing: .04em; margin-bottom: .3rem; margin-top: .85rem;
        }
        label:first-of-type { margin-top: 0; }
        input[type=text], input[type=email], input[type=password] {
            width: 100%; padding: .6rem .85rem;
            border: 1px solid var(--border-soft); border-radius: 8px;
            background: var(--bg-input); color: var(--text-main);
            font-size: .9rem; font-family: inherit; outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        input:focus {
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px hsl(225,70%,55%,.1);
        }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
        @media (max-width: 500px) { .two-col { grid-template-columns: 1fr; } }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            gap: .4rem; padding: .6rem 1.1rem; border-radius: 8px;
            font-size: .85rem; font-weight: 700; cursor: pointer;
            border: 1.5px solid; font-family: inherit;
            transition: background .15s, color .15s; text-decoration: none;
        }
        .btn-primary { background: var(--accent); color: #fff; border-color: var(--accent); }
        .btn-primary:hover { background: var(--accent-hover); border-color: var(--accent-hover); }
        .btn-full { width: 100%; margin-top: 1.1rem; padding: .7rem; font-size: .92rem; }
        .btn-sm { padding: .35rem .75rem; font-size: .76rem; }
        .btn-danger { background: var(--danger-bg); color: var(--danger); border-color: hsl(0,50%,82%); }
        .btn-danger:hover { background: var(--danger); color: #fff; border-color: var(--danger); }
        .btn-warning { background: var(--warning-bg); color: hsl(38,80%,28%); border-color: hsl(38,60%,75%); }
        .btn-warning:hover { background: var(--warning); color: #fff; border-color: var(--warning); }

        /* Alerts */
        .alert {
            display: flex; align-items: flex-start; gap: .55rem;
            padding: .7rem .9rem; border-radius: 8px; font-size: .84rem;
            margin-bottom: 1rem;
        }
        .alert.error   { background: var(--danger-bg);  color: var(--danger);  border: 1px solid hsl(0,50%,82%); }
        .alert.success { background: var(--success-bg); color: var(--success); border: 1px solid hsl(145,45%,72%); }

        /* Admin table */
        .admin-list { display: flex; flex-direction: column; gap: .6rem; }
        .admin-row {
            display: flex; align-items: center; gap: .75rem;
            padding: .75rem .9rem; background: var(--bg-hover);
            border: 1px solid var(--border-soft); border-radius: 10px;
            flex-wrap: wrap;
        }
        .admin-avatar {
            width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
            background: var(--accent-light); color: var(--accent);
            display: flex; align-items: center; justify-content: center;
            font-weight: 900; font-size: .95rem; text-transform: uppercase;
        }
        .admin-info { flex: 1; min-width: 0; }
        .admin-name { font-weight: 700; font-size: .88rem; color: var(--text-main); }
        .admin-sub  { font-size: .74rem; color: var(--text-muted); margin-top: .1rem; }
        .admin-actions { display: flex; gap: .4rem; flex-shrink: 0; }

        /* Reset pw modal */
        .modal-backdrop {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.45); z-index: 100;
            align-items: center; justify-content: center; padding: 1rem;
        }
        .modal-backdrop.show { display: flex; }
        .modal {
            background: var(--bg-card); border-radius: var(--radius);
            padding: 1.6rem 1.8rem; width: 100%; max-width: 380px;
            box-shadow: var(--shadow-lg);
        }
        .modal h3 { font-size: .95rem; font-weight: 800; margin-bottom: 1rem; }
        .modal-actions { display: flex; gap: .6rem; margin-top: 1.1rem; }

        /* Divider */
        hr { border: none; border-top: 1px solid var(--border-soft); margin: 1.2rem 0; }
    </style>
</head>
<body>

<?php if (!$authed): ?>
<!-- ═══════════════════════════════════════
     LOGIN
═══════════════════════════════════════ -->
<div class="login-wrap">
    <div class="login-logo">
        <div class="icon"><i class="fa-solid fa-shield-halved"></i></div>
        <h1>Superuser Access</h1>
        <p>DLMS — Admin Management Panel</p>
    </div>

    <div class="card">
        <?php if ($error): ?>
        <div class="alert error"><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Superuser Password</label>
            <input type="password" name="su_pass" autofocus autocomplete="current-password" placeholder="••••••••••••">
            <button type="submit" name="su_login" class="btn btn-primary btn-full">
                <i class="fa-solid fa-unlock-keyhole"></i> Enter
            </button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ═══════════════════════════════════════
     DASHBOARD
═══════════════════════════════════════ -->
<div class="wrap">

    <div class="su-header">
        <div class="su-logo">
            <div class="icon"><i class="fa-solid fa-shield-halved"></i></div>
            <div>
                DLMS Superuser
                <span class="sub">Admin Management</span>
            </div>
        </div>
        <a href="?logout=1" class="btn-logout">
            <i class="fa-solid fa-right-from-bracket"></i> Sign Out
        </a>
    </div>

    <?php if ($success): ?>
    <div class="alert success"><i class="fa-solid fa-circle-check"></i> <span><?= $success ?></span></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert error"><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error) ?>  </div>
    <?php endif; ?>

    <!-- ── Admin list ── -->
    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-users-gear"></i>
            Admins (<?= count($admins) ?>)
        </div>

        <?php if (empty($admins)): ?>
        <p style="color:var(--text-muted);font-size:.85rem">No admins found.</p>
        <?php else: ?>
        <div class="admin-list">
            <?php foreach ($admins as $a): ?>
            <div class="admin-row">
                <div class="admin-avatar"><?= htmlspecialchars(substr($a['fullname'], 0, 1)) ?></div>
                <div class="admin-info">
                    <div class="admin-name">
                        <?= htmlspecialchars($a['fullname']) ?>
                        <span style="font-size:.7rem;font-weight:600;color:var(--text-light);margin-left:.3rem">@<?= htmlspecialchars($a['username']) ?></span>
                    </div>
                    <div class="admin-sub">
                        <?= htmlspecialchars($a['email']) ?>
                        &nbsp;·&nbsp; Added <?= date('d M Y', strtotime($a['created_at'])) ?>
                    </div>
                </div>
                <div class="admin-actions">
                    <button class="btn btn-sm btn-warning"
                            onclick="openReset(<?= $a['id'] ?>, '<?= htmlspecialchars($a['username'], ENT_QUOTES) ?>')">
                        <i class="fa-solid fa-key"></i> Reset PW
                    </button>
                    <button class="btn btn-sm btn-danger"
                            onclick="openDelete(<?= $a['id'] ?>, '<?= htmlspecialchars($a['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($a['fullname'], ENT_QUOTES) ?>')">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Add admin ── -->
    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-user-plus"></i> Add New Admin
        </div>

        <form method="POST">
            <div class="two-col">
                <div>
                    <label>Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="e.g. john_doe" autocomplete="off">
                </div>
                <div>
                    <label>Full Name</label>
                    <input type="text" name="fullname" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>"
                           placeholder="e.g. John Doe">
                </div>
            </div>
            <div class="two-col" style="margin-top:.85rem">
                <div>
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="admin@example.com">
                </div>
                <div>
                    <label>Password</label>
                    <input type="password" name="password" placeholder="min 6 characters">
                </div>
            </div>
            <button type="submit" name="add_admin" class="btn btn-primary btn-full" style="margin-top:1.1rem">
                <i class="fa-solid fa-user-plus"></i> Create Admin
            </button>
        </form>
    </div>

</div><!-- /wrap -->

<!-- ── Delete Admin Modal ── -->
<div class="modal-backdrop" id="deleteModal">
    <div class="modal">
        <div style="text-align:center;margin-bottom:1rem">
            <div style="width:52px;height:52px;border-radius:50%;background:var(--danger-bg);color:var(--danger);display:inline-flex;align-items:center;justify-content:center;font-size:1.3rem;border:2px solid hsl(0,50%,82%)">
                <i class="fa-solid fa-trash"></i>
            </div>
        </div>
        <h3 style="text-align:center;margin-bottom:.35rem">Delete Admin?</h3>
        <p style="font-size:.83rem;color:var(--text-muted);text-align:center;margin-bottom:1.2rem">
            You are about to delete <strong id="deleteFullname"></strong><br>
            <span style="font-size:.76rem;color:var(--text-light)">@<span id="deleteUsername"></span></span><br><br>
            <span style="color:var(--danger);font-weight:600">This action cannot be undone.</span>
        </p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="admin_id" id="deleteAdminId">
            <div class="modal-actions">
                <button type="submit" name="delete_admin" class="btn btn-danger" style="flex:1">
                    <i class="fa-solid fa-trash"></i> Yes, Delete
                </button>
                <button type="button" class="btn" style="flex:1;background:var(--bg-hover);color:var(--text-muted);border-color:var(--border-soft)" onclick="closeDelete()">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── Reset Password Modal ── -->
<div class="modal-backdrop" id="resetModal">
    <div class="modal">
        <h3><i class="fa-solid fa-key" style="color:var(--warning);margin-right:.4rem"></i> Reset Password</h3>
        <p style="font-size:.83rem;color:var(--text-muted);margin-bottom:.8rem">
            Setting new password for <strong id="resetUsername"></strong>
        </p>
        <form method="POST" id="resetForm">
            <input type="hidden" name="admin_id" id="resetAdminId">
            <label>New Password</label>
            <input type="password" name="new_password" id="resetPwInput" placeholder="min 6 characters" autocomplete="new-password">
            <div class="modal-actions">
                <button type="submit" name="reset_password" class="btn btn-warning" style="flex:1">
                    <i class="fa-solid fa-floppy-disk"></i> Save Password
                </button>
                <button type="button" class="btn btn-danger" onclick="closeReset()" style="flex:1">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openReset(id, username) {
    document.getElementById('resetAdminId').value  = id;
    document.getElementById('resetUsername').textContent = '@' + username;
    document.getElementById('resetPwInput').value  = '';
    document.getElementById('resetModal').classList.add('show');
    setTimeout(() => document.getElementById('resetPwInput').focus(), 50);
}
function closeReset() {
    document.getElementById('resetModal').classList.remove('show');
}
document.getElementById('resetModal').addEventListener('click', function(e) {
    if (e.target === this) closeReset();
});

function openDelete(id, username, fullname) {
    document.getElementById('deleteAdminId').value       = id;
    document.getElementById('deleteUsername').textContent  = username;
    document.getElementById('deleteFullname').textContent  = fullname;
    document.getElementById('deleteModal').classList.add('show');
}
function closeDelete() {
    document.getElementById('deleteModal').classList.remove('show');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDelete();
});
</script>

<?php endif; ?>
</body>
</html>
