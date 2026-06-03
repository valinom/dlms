<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';

$success = '';
$error = '';

if (isset($_POST['change_password'])) {

    verify_csrf(); // ✅ your existing CSRF function

    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($new) < 6) {
        flash('error', 'Password must be at least 6 characters long.');
    } elseif ($new !== $confirm) {
        flash('error', 'New passwords do not match.');
    } else {

        // fetch current admin password
        $stmt = $pdo->prepare("
            SELECT password 
            FROM admin 
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $_SESSION['admin_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin || !password_verify($old, $admin['password'])) {
            flash('error', 'Old password is incorrect.');
        } else {

            $hash = password_hash($new, PASSWORD_DEFAULT);

            $update = $pdo->prepare("
                UPDATE admin 
                SET password = :pass 
                WHERE id = :id
            ");
            $update->execute([
                'pass' => $hash,
                'id' => $_SESSION['admin_id']
            ]);
            // flash('success', 'Password updated successfully.');
            header('Location: my-profile.php');
            exit;
        }
        
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Change Password | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <link rel="stylesheet" href="assets/css/adminui.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="assets/css/book.css">
</head>

<body>

    <?php include 'includes/admin-header.php'; ?>

    <main class="main">
        <div class="page-container">

            <h2 class="page-title">Change Password</h2>

            <?php include('../includes/_toast.php'); ?>
            <form class="form-card" method="POST">

                <label>Old Password</label>
                <input type="password" name="old_password" required>

                <label>New Password</label>
                <input type="password" name="new_password" required>

                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required>

                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div style="margin-top:1rem; display:flex; gap:.6rem;">
                    <button type="submit" name="change_password" class="alert success">
                        <i class="fa-solid fa-key"></i> Update Password
                    </button>
                </div>

            </form>

        </div>
    </main>
    <?php include '../includes/_footer.php'; ?>

</body>

</html>