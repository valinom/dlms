<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';

/* ================= FLASH ================= */
$old = flash('old') ?? [];

/* ================= FETCH DEPARTMENTS ================= */
$departments = $pdo->query(
    "SELECT id, name FROM departments ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

/* ================= ADD USER ================= */
if (isset($_POST['add_user'])) {

    verify_csrf();

    $fullname = ucwords(strtolower(trim($_POST['fullname'] ?? '')));
    $email = strtolower(trim($_POST['email'] ?? ''));
    $mobile = trim($_POST['mobile'] ?? '');
    $dept_id = (int) ($_POST['department'] ?? 0);

    /* ===== VALIDATION ===== */
    $errors = [];

    if ($fullname === '')                                          $errors[] = 'Full name is required.';
    if ($email === '')                                             $errors[] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))            $errors[] = 'Invalid email address.';
    if ($mobile === '')                                            $errors[] = 'Mobile number is required.';
    elseif (!preg_match('/^[0-9]{10}$/', preg_replace('/[^0-9]/', '', $mobile))) $errors[] = 'Please enter a valid mobile number (10 digits).';
    if ($dept_id <= 0)                                             $errors[] = 'Please select a department.';

    if ($errors) {
        flash('error', $errors[0]);
        flash('old', ['fullname' => $fullname, 'email' => $email, 'mobile' => $mobile, 'department' => $dept_id]);
        header("Location: add-user.php");
        exit;
    }

    /* ===== CHECK EMAIL ===== */
    $check = $pdo->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
    $check->execute([$email]);

    if ($check->fetch()) {
        flash('error', 'Email is already registered.');
        flash('old', ['fullname' => $fullname, 'email' => $email, 'mobile' => $mobile, 'department' => $dept_id]);
        header("Location: add-user.php");
        exit;
    }

    /* ===== CHECK MOBILE (handled client-side) ===== */

    /* ===== HASH PASSWORD ===== */
    $password = password_hash('123456', PASSWORD_DEFAULT);

    /* ===== INSERT USER (WITHOUT student_id first) ===== */
    $stmt = $pdo->prepare("
        INSERT INTO students
        (fullname, email, mobile, department_id, password, status, email_verified)
        VALUES (?, ?, ?, ?, ?, 1, 1)
    ");

    $stmt->execute([
        $fullname,
        $email,
        $mobile,
        $dept_id,
        $password
    ]);

    /* ===== SAFE STUDENT ID GENERATION ===== */
    $lastId    = $pdo->lastInsertId();
    $studentId = 'STU' . date('Y') . str_pad($lastId, 4, '0', STR_PAD_LEFT);

    $update = $pdo->prepare("
        UPDATE students SET user_id = ? WHERE id = ?
    ");
    $update->execute([$studentId, $lastId]);

    flash('success', 'User added successfully.');
    header("Location: add-user.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Add User | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="assets/css/book.css">
    <link rel="stylesheet" href="assets/css/adminui.css">
</head>

<body>

    <?php include('includes/admin-header.php'); ?>

    <main>
        <main class="main">
            <div class="page-container">

                <h2 class="page-title">Add User</h2>

                <?php include('../includes/_toast.php'); ?>
                
                <form class="form-card" method="POST">

                    <label>Full Name</label>
                    <input type="text" name="fullname" required autocomplete="off" style="text-transform: capitalize;"
                        value="<?= e($old['fullname'] ?? '') ?>">

                    <label>Mobile Number</label>
                    <input type="tel" name="mobile" pattern="[0-9]{10}" required autocomplete="off"
                        value="<?= e($old['mobile'] ?? '') ?>">

                    <label>Email</label>
                    <input type="email" autocomplete="off" name="email" required autocomplete="off"
                        style="text-transform: lowercase;" value="<?= e($old['email'] ?? '') ?>">

                    <label>Department</label>
                    <div class="custom-select">
                        <div class="select-trigger">
                            <?php
                            if (!empty($old['department'])) {
                                foreach ($departments as $d) {
                                    if ($d['id'] == $old['department']) {
                                        echo e($d['name']);
                                    }
                                }
                            } else {
                                echo "Select Department";
                            }
                            ?>
                        </div>
                        <ul class="select-options">
                            <?php foreach ($departments as $d): ?>
                                <li data-value="<?= $d['id'] ?>">
                                    <?= e($d['name']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <input type="hidden" name="department" required value="<?= (int) ($old['department'] ?? '') ?>">
                    </div>


                    <button type="button" onclick="validateAndSubmit()">
                        <i class="fa-solid fa-user-plus"></i> Add User
                    </button>

                    <p class="form-note">
                        Default password will be <strong>123456</strong>
                    </p>
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                </form>

            </div>
        </main>

    </main>
    <script src="../assets/js/dlms-dialogs.js"></script>
    <script src="../assets/js/select.js"></script>
    <script>
        function validateAndSubmit() {
            const form     = document.querySelector('form');
            const name     = form.querySelector('[name="fullname"]').value.trim();
            const email    = form.querySelector('[name="email"]').value.trim();
            const mobile   = form.querySelector('[name="mobile"]').value.trim();
            const dept     = form.querySelector('[name="department"]').value;
            const emailPat = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!name) {
                return showAlert('Full name is required.', 'warning');
            }
            if (!email || !emailPat.test(email)) {
                return showAlert('Please enter a valid email address.', 'error');
            }
            if (!/^[0-9]{10}$/.test(mobile.replace(/\D/g, ''))) {
                return showAlert('Please enter a valid mobile number (10 digits).', 'error');
            }
            if (!dept) {
                return showAlert('Please select a department.', 'warning');
            }

            function doSubmit() {
                const hidden = document.createElement('input');
                hidden.type  = 'hidden';
                hidden.name  = 'add_user';
                hidden.value = '1';
                form.appendChild(hidden);
                form.submit();
            }

            fetch(`ajax/check-mobile.php?mobile=${encodeURIComponent(mobile)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.exists) {
                        showConfirm({
                            title: 'Mobile Already in Use',
                            message: `This mobile number is already registered to another student. Add <strong>${name}</strong> anyway?`,
                            type: 'warning',
                            confirmText: 'Yes, Add Anyway',
                            onConfirm: doSubmit
                        });
                    } else {
                        doSubmit();
                    }
                })
                .catch(() => doSubmit());
        }
    </script>
    <?php include '../includes/_footer.php'; ?>
</body>

</html>