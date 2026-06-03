<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT * FROM students WHERE id = ?
");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    redirect('manage-user.php');
}

/* departments */
$departments = $pdo->query(
    "SELECT id, name FROM departments ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['update_user'])) {

    verify_csrf();

    $fullname = ucwords(strtolower(trim($_POST['fullname'] ?? '')));
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $mobile   = trim($_POST['mobile'] ?? '');
    $dept_id  = (int)($_POST['department'] ?? 0);

    /* ===== VALIDATION ===== */
    $errors = [];

    if ($fullname === '')                                                               $errors[] = 'Full name is required.';
    if ($email === '')                                                                  $errors[] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))                                $errors[] = 'Invalid email address.';
    if ($mobile === '')                                                                 $errors[] = 'Mobile number is required.';
    elseif (!preg_match('/^[0-9]{10}$/', preg_replace('/[^0-9]/', '', $mobile)))       $errors[] = 'Please enter a valid mobile number (10 digits).';
    if ($dept_id <= 0)                                                                 $errors[] = 'Please select a department.';

    if ($errors) {
        flash('error', $errors[0]);
        header("Location: edit-user.php?id=$id");
        exit;
    }

    /* ===== CHECK EMAIL (exclude current user) ===== */
    $check = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ? LIMIT 1");
    $check->execute([$email, $id]);

    if ($check->fetch()) {
        flash('error', 'Email is already registered to another user.');
        header("Location: edit-user.php?id=$id");
        exit;
    }

    /* ===== CHECK MOBILE (handled client-side) ===== */

    $pdo->prepare("
        UPDATE students
        SET fullname = ?, email = ?, mobile = ?, department_id = ?
        WHERE id = ?
    ")->execute([$fullname, $email, $mobile, $dept_id, $id]);

    flash('success', 'User updated successfully.');
    redirect('manage-user.php');
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Edit User | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="assets/css/adminui.css">
    <link rel="stylesheet" href="assets/css/book.css">
</head>

<body>
    <?php include 'includes/admin-header.php'; ?>

    <main class="main">
        <div class="page-container">

            <h2 class="page-title">Edit Student</h2>

            <?php include('../includes/_toast.php'); ?>
            <form class="form-card" method="POST">

                <label>Student ID</label>
                <input type="text" value="<?= e($user['user_id']) ?>" disabled style="color: var(--text-muted);">

                <label>Full Name</label>
                <input type="text" name="fullname" value="<?= e($user['fullname']) ?>" required autocomplete="off"
                    style="text-transform: capitalize;">

                <label>Email</label>
                <input type="email" name="email" value="<?= e($user['email']) ?>" required autocomplete="off"
                    style="text-transform: lowercase;">

                <label>Mobile</label>
                <input type="tel" name="mobile" value="<?= e($user['mobile']) ?>" pattern="[0-9]{10}" required
                    autocomplete="off">

                <label>Department</label>
                <div class="custom-select">
                    <div class="select-trigger">
                        <?php
                        foreach ($departments as $d) {
                            if ($d['id'] == $user['department_id']) {
                                echo e($d['name']);
                                break;
                            }
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

                    <input type="hidden" name="department" value="<?= (int) $user['department_id'] ?>" required>
                </div>


                <div class="form-actions">
                    <button type="button" onclick="validateAndSubmit()" class="alert success">
                        <i class="fa-solid fa-check"></i> Update User
                    </button>

                    <a href="manage-user.php" class="alert info">
                        <i class="fa-solid fa-xmark"></i> Cancel
                    </a>
                </div>

                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="update_user" value="">
            </form>

        </div>
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
                form.querySelector('[name="update_user"]').value = '1';
                form.submit();
            }

            fetch(`ajax/check-mobile.php?mobile=${encodeURIComponent(mobile)}&exclude_id=<?= $id ?>`)
                .then(r => r.json())
                .then(data => {
                    if (data.exists) {
                        showConfirm({
                            title: 'Mobile Already in Use',
                            message: `This mobile number is already registered to another student. Update <strong>${name}</strong> anyway?`,
                            type: 'warning',
                            confirmText: 'Yes, Update Anyway',
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