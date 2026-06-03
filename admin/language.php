<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';

/* ================= ADD LANGUAGE ================= */
if (isset($_POST['add_language'])) {

    verify_csrf();

    $name = ucwords(strtolower(trim($_POST['name'])));

    if ($name === '') {
        flash('error', 'Language name is required.');
        header("Location: language.php");
        exit;
    }

    $check = $pdo->prepare("SELECT id FROM languages WHERE name = ?");
    $check->execute([$name]);

    if ($check->rowCount()) {
        flash('error', 'Language already exists.');
        header("Location: language.php");
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO languages (name) VALUES (?)");
    $stmt->execute([$name]);

    flash('success', 'Language added successfully.');
    header("Location: language.php");
    exit;
}

/* ================= DELETE LANGUAGE ================= */
if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    $check = $pdo->prepare("
        SELECT COUNT(*) 
        FROM digital_books 
        WHERE language_id = ?
    ");
    $check->execute([$id]);

    if ($check->fetchColumn() > 0) {
        flash('error', 'Language is in use.');
        header("Location: language.php");
        exit;
    }

    $pdo->prepare("DELETE FROM languages WHERE id = ?")->execute([$id]);

    flash('success', 'Language deleted.');
    header("Location: language.php");
    exit;
}

/* ================= FETCH LANGUAGES ================= */
$stmt = $pdo->query("
    SELECT l.id, l.name,
           COUNT(db.id) AS total_books
    FROM languages l
    LEFT JOIN digital_books db ON db.language_id = l.id
    GROUP BY l.id
    ORDER BY l.name ASC
");
$languages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="color-scheme" content="light">
<title>Manage Languages | DLMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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

<?php include 'includes/admin-header.php'; ?>
<?php include '../includes/_toast.php'; ?>

<main class="main">
<div class="page-container">

<h2 class="page-title">Manage Languages</h2>

<!-- ADD FORM -->
<form class="form-card" method="POST">
    <label>Language Name</label>
    <input type="text" autofocus name="name" required autocomplete="off" style="text-transform: capitalize;">

    <button type="submit" onclick="validateAndSubmit()" name="add_language" class="alert success">
        <i class="fa-solid fa-plus"></i> Add Language
    </button>

    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
</form>

<!-- TABLE -->
<div class="table-card">
<table class="data-table">
<thead>
<tr>
    <th>#</th>
    <th>Language</th>
    <th>Total Books</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php foreach ($languages as $index => $lang): ?>
<tr>
    <td><?= $index + 1 ?></td>
    <td><?= e($lang['name']) ?></td>
    <td><?= $lang['total_books'] ?></td>
    <td>
        <?php if ($lang['total_books'] > 0): ?>
            <span class="badge info">In Use</span>
        <?php else: ?>
            <a href="?delete=<?= $lang['id'] ?>"
               class="alert danger"
               onclick="dlmsDeleteLink(event, this)">
               <i class="fa-solid fa-trash"></i>
            </a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>

</table>
</div>

</div>
</main>

<script src="../assets/js/dlms-dialogs.js"></script>
<script>
function dlmsDeleteLink(e, el) {
    e.preventDefault();
    const href = el.href;
    showConfirm({
        title: 'Delete Language?',
        message: 'This action cannot be undone.',
        confirmText: 'Delete',
        onConfirm: () => { window.location.href = href; }
    });
}
function validateAndSubmit(){
    const form = document.querySelector('.form-card');
    const category= form.querySelector('[name="name"]').value.trim();
    if (!category) {
        showAlert('Category name is required.');
        form.querySelector('[name="name"]');
        return;
    }
}
</script>
    <?php include '../includes/_footer.php'; ?>
</body>
</html>