<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';

/* Ensure admin session */
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$adminId = $_SESSION['admin_id'];

/* Fetch admin data */
$stmt = $pdo->prepare("
    SELECT id, username, fullname, email, created_at, updated_at
    FROM admin
    WHERE id = ?
");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die('Admin record not found');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>My Profile | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <link rel="stylesheet" href="assets/css/adminui.css">
    <link rel="stylesheet" href="assets/css/book.css">
</head>

<body>

    <?php include 'includes/admin-header.php'; ?>
    <?php include '../includes/_toast.php'; ?>

    <main class="main">
        <div class="page-container">

            <h2 class="page-title">My Profile</h2>

            <!-- PROFILE CARD -->
            <div class="profile-card">

                <div class="profile-row">
                    <span>Username</span>
                    <strong><?= htmlspecialchars($admin['username']) ?></strong>
                </div>

                <div class="profile-row">
                    <span>Full Name</span>
                    <strong><?= htmlspecialchars($admin['fullname']) ?></strong>
                </div>

                <div class="profile-row">
                    <span>Email</span>
                    <strong><?= htmlspecialchars($admin['email']) ?></strong>
                </div>

                <div class="profile-row">
                    <span>Account Created</span>
                    <strong><?= date('d M Y, h:i A', strtotime($admin['created_at'])) ?></strong>
                </div>

                <div class="profile-row">
                    <span>Last Updated</span>
                    <strong>
                        <?= $admin['updated_at']
                            ? date('d M Y, h:i A', strtotime($admin['updated_at']))
                            : '—'
                            ?>
                    </strong>
                </div>

                <div class="profile-actions">
                    <a href="edit-profile.php" class="alert info"><i class="fa-solid fa-pen"></i> Edit Profile</a>
                    <a href="change-password.php" class="alert warning"><i class="fa-solid fa-key"></i> Change Password</a>
                </div>


            </div>

        </div>
    </main>
    <?php include '../includes/_footer.php'; ?>
</body>

</html>