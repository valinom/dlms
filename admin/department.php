<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';

/* ================= ADD DEPARTMENT ================= */
if (isset($_POST['add_department'])) {

    verify_csrf();

    $department = ucwords(strtolower(trim($_POST['department_name'])));

    if ($department === '') {
        flash('error', 'Department name is required.');
    } else {

        // check duplicate
        $check = $pdo->prepare("SELECT id FROM departments WHERE name = ?");
        $check->execute([$department]);

        if ($check->rowCount() > 0) {
            flash('error', 'Department already exists.');
        } else {
            $insert = $pdo->prepare("INSERT INTO departments (name) VALUES (?)");
            $insert->execute([$department]);
            flash('success', 'Department added successfully.');
        }
    }
}

/* ================= DELETE DEPARTMENT ================= */
if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    // check if students are linked
    $checkStudents = $pdo->prepare("
        SELECT COUNT(*) 
        FROM students 
        WHERE department_id = ?
    ");
    $checkStudents->execute([$id]);
    $studentCount = (int) $checkStudents->fetchColumn();

    if ($studentCount > 0) {
        $error = "Cannot delete department. Students are linked to it.";
    } else {
        $delete = $pdo->prepare("DELETE FROM departments WHERE id = ?");
        $delete->execute([$id]);
        redirect('department.php');
    }
}

/* ================= FETCH DEPARTMENTS ================= */
$stmt = $pdo->query("
    SELECT d.id, d.name, COUNT(r.id) AS total_students
    FROM departments d
    LEFT JOIN students r ON r.department_id = d.id
    GROUP BY d.id
    ORDER BY d.name ASC
");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments | DLMS</title>

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

            <h2 class="page-title">Manage Departments</h2>

            <?php include('../includes/_toast.php'); ?>
            <!-- ADD DEPARTMENT -->
            <form class="form-card" method="POST">
                <label>Department Name</label>
                <input type="text" name="department_name" autocomplete="off"
                autofocus style="text-transform: capitalize;"
                    required>

                <button type="submit" onclick="validateAndSubmit()" name="add_department">
                    <i class="fa-solid fa-plus"></i> Add Department
                </button>

                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            </form>

            <!-- DEPARTMENT LIST -->
            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Department</th>
                            <th>Students</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php if (!$departments): ?>
                            <tr>
                                <td colspan="4">No departments found.</td>
                            </tr>
                        <?php else: ?>
                            <?php $i = 1;
                            foreach ($departments as $d): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= e($d['name']) ?></td>
                                    <td><?= (int) $d['total_students'] ?></td>
                                    <td>
                                        <?php if ($d['total_students'] == 0): ?>
                                            <a href="department.php?delete=<?= $d['id'] ?>" class="alert danger"
                                                onclick="dlmsDeleteLink(event, this)">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="alert info">In Use</span>
                                        <?php endif; ?>
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
        title: 'Delete Department?',
        message: 'This action cannot be undone.',
        confirmText: 'Delete',
        onConfirm: () => { window.location.href = href; }
    });
}
function validateAndSubmit(){
    const form = document.querySelector('.form-card');
    const category= form.querySelector('[name="department_name"]').value.trim();
    if (!category) {
        showAlert('Department name is required.');
        form.querySelector('[name="department_name"]');
        return;
    }
}
</script>
    <?php include '../includes/_footer.php'; ?>
</body>

</html>