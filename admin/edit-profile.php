<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';

/* Get logged-in admin */
$adminId = $_SESSION['admin_id'] ?? 0;
if (!$adminId) {
    header('Location: ../admin.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, username, fullname, email
    FROM admin
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    header('Location: dashboard.php');
    exit;
}

/* Handle update */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = strtolower(trim($_POST['username']));
    $fullname = ucwords(strtolower(trim($_POST['fullname'])));
    $email = strtolower(trim($_POST['email']));

    if ($username && $fullname && $email) {

        /* Check username/email uniqueness (excluding self) */
        $check = $pdo->prepare("
            SELECT id FROM admin
            WHERE (username = :u OR email = :e) AND id != :id
        ");
        $check->execute([
            'u' => $username,
            'e' => $email,
            'id' => $adminId
        ]);

        if ($check->rowCount()) {
            flash('error', 'Username or email already exists.');
        } else {
            $update = $pdo->prepare("
                UPDATE admin
                SET username = ?, fullname = ?, email = ?
                WHERE id = ?
            ");
            $update->execute([
                $username,
                $fullname,
                $email,
                $adminId
            ]);

            $_SESSION['user_name'] = $fullname;
            $_SESSION['user_email'] = $email;

            flash('success', 'Profile updated successfully.');
            header('Location: my-profile.php');
            exit;
        }

    } else {
        flash('error', 'All fields are required.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Edit Profile | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="assets/css/adminui.css">
    <link rel="stylesheet" href="assets/css/book.css">
</head>

<body>
    <?php include 'includes/admin-header.php'; ?>
    <?php include '../includes/_toast.php'; ?>

    <main class="main">
        <div class="page-container">

            <h2 class="page-title">Edit Admin Profile</h2>

            <form class="form-card" method="POST">

                <label>Username</label>
                <input type="text" name="username" value="<?= e($admin['username']) ?>" required autocomplete="off"  style="text-transform: lowercase;">

                <label>Full Name</label>
                <input type="text" name="fullname" value="<?= e($admin['fullname']) ?>" required autocomplete="off" style="text-transform: capitalize;">

                <label>Email</label>
                <input type="email" name="email" value="<?= e($admin['email']) ?>" required autocomplete="off" style="text-transform: lowercase;">

                <div class="form-actions">
                    <button type="submit" class="alert success">
                        <i class="fa-solid fa-check"></i> Save Changes
                    </button>

                    <a href="my-profile.php" class="alert info">
                        <i class="fa-solid fa-xmark"></i> Cancel
                    </a>
                </div>

                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            </form>

        </div>
    </main>
    <?php include '../includes/_footer.php'; ?>
</body>

</html>