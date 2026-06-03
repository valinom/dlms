<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';

/* ADD CATEGORY */
if (isset($_POST['add_category'])) {

    verify_csrf();

    $category =  ucwords(strtolower(trim($_POST['category_name'])));

    if ($category === '') {
        flash('error', 'Book added successfully.');
    } else {

        // check duplicate
        $check = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $check->execute([$category]);

        if ($check->rowCount() > 0) {
            flash('error', 'Category already exists.');
        } else {
            $insert = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $insert->execute([$category]);
            flash('success', 'Category added successfully.');
        }
    }
}

/* DELETE CATEGORY */
if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    $delete = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $delete->execute([$id]);
    flash('success', 'Category deleted.');
    redirect('category.php');
}

/*  FETCH CATEGORIES */
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category | DLMS</title>
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

    <main class="main">
        <div class="page-container">

            <h2 class="page-title">Manage Categories</h2>
            <?php include('../includes/_toast.php'); ?>
            <!-- Add Category -->
            <form class="form-card" method="POST">
                <label>Category Name</label>
                <input type="text" name="category_name" autocomplete="off"
                style="text-transform: capitalize;" autofocus required>

                <button type="submit" onclick="validateAndSubmit()" name="add_category">
                    <i class="fa-solid fa-plus"></i> Add Category
                </button>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            </form>

            <!-- Category List -->
            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($categories) === 0): ?>
                            <tr>
                                <td colspan="3">No categories found.</td>
                            </tr>
                        <?php else: ?>
                            <?php $i = 1;
                            foreach ($categories as $cat): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= e($cat['name']) ?></td>
                                    <td>
                                        <a href="category.php?delete=<?= $cat['id'] ?>" class="alert danger"
                                            onclick="dlmsDeleteLink(event, this)">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
        title: 'Delete Category?',
        message: 'This action cannot be undone.',
        confirmText: 'Delete',
        onConfirm: () => { window.location.href = href; }
    });
}
function validateAndSubmit(){
    const form = document.querySelector('.form-card');
    const category= form.querySelector('[name="category_name"]').value.trim();
    if (!category) {
        showAlert('Category name is required.');
        form.querySelector('[name="category_name"]');
        return;
    }
}
</script>
    <?php include '../includes/_footer.php'; ?>
</body>

</html>