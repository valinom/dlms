<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-student.php';

$studentId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT r.*, d.name AS department
    FROM students r
    LEFT JOIN departments d ON d.id = r.department_id
    WHERE r.id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

/* Change password */
if (isset($_POST['change_password'])) {
    verify_csrf();
    $old  = $_POST['old_password']  ?? '';
    $new  = $_POST['new_password']  ?? '';
    $conf = $_POST['confirm_password'] ?? '';

    if (strlen($new) < 6) {
        flash('error', 'Password must be at least 6 characters.');
    } elseif ($new !== $conf) {
        flash('error', 'New passwords do not match.');
    } elseif (!password_verify($old, $student['password'])) {
        flash('error', 'Current password is incorrect.');
    } else {
        $pdo->prepare("UPDATE students SET password = ? WHERE id = ?")
            ->execute([password_hash($new, PASSWORD_DEFAULT), $studentId]);
        flash('success', 'Password changed successfully.');
    }
    redirect('profile.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Profile | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
</head>
<body>
    <?php include 'includes/student-header.php'; ?>
    <?php include '../includes/_toast.php'; ?>

    <main class="main">
        <div class="page-container">
            <h2 class="page-title">My Profile</h2>

            <div class="profile-card">
                <div class="profile-row"><span>Student ID</span><strong><?=
                e($student['user_id']) ?></strong></div>
                <div class="profile-row"><span>Full Name</span><strong><?= e($student['fullname']) ?></strong></div>
                <div class="profile-row"><span>Email</span><strong><?= e($student['email']) ?></strong></div>
                <div class="profile-row"><span>Mobile</span><strong><?= e($student['mobile']) ?></strong></div>
                <div class="profile-row"><span>Department</span><strong><?= e($student['department']) ?></strong></div>
                <div class="profile-row"><span>Joined</span><strong><?= date('d M Y', strtotime($student['created_at'])) ?></strong></div>
            </div>

            <h2 class="page-title" style="margin-top:1.8rem">Change Password</h2>
            <form class="form-card" method="POST">
                <label>Current Password</label>
                <input type="password" name="old_password" required>

                <label>New Password</label>
                <input type="password" name="new_password" required>

                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required>

                <button type="submit" name="change_password">
                    <i class="fa-solid fa-key"></i> Update Password
                </button>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            </form>
        </div>
    </main>
    <?php include '../includes/_footer.php'; ?>
</body>
</html>
