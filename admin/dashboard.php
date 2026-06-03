<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';

$pdo->exec("
    DELETE r FROM students r
    LEFT JOIN email_otps o ON o.student_id = r.id
    WHERE r.email_verified = 0
      AND r.created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
");
/* =======================
   BOOK STATS
======================= */

// Total unique books
$totalBookTitles = $pdo->query("
    SELECT COUNT(*) FROM books
")->fetchColumn();

// Total copies (quantity sum)
$totalBookCopies = $pdo->query("
    SELECT COALESCE(SUM(quantity),0) FROM books
")->fetchColumn();

/* =======================
   ISSUE / RETURN
======================= */

$issuedStudents = $pdo->query("
    SELECT COUNT(DISTINCT student_id)
    FROM issued_books
    WHERE returned_at IS NULL
")->fetchColumn();

$returnedBooks = $pdo->query("
    SELECT COUNT(*)
    FROM issued_books
    WHERE returned_at IS NOT NULL
")->fetchColumn();

/* =======================
   TOP STUDENTS
======================= */

$topStudents = $pdo->query("
    SELECT 
        r.fullname,
        r.user_id,
        d.name AS department,
        COUNT(ib.id) total_books
    FROM issued_books ib
    JOIN students r ON r.id = ib.student_id
    JOIN departments d ON d.id = r.department_id
    GROUP BY ib.student_id
    ORDER BY total_books DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

/* =======================
   STUDENTS
======================= */

$totalStudents = $pdo->query("
    SELECT COUNT(*) FROM students
")->fetchColumn();

$blockedStudents = $pdo->query("
    SELECT COUNT(*) FROM students WHERE status = 0
")->fetchColumn();

$deptStats = $pdo->query("
    SELECT
        d.name AS department,
        COUNT(r.id) AS total
    FROM students r
    JOIN departments d ON d.id = department_id
    GROUP BY d.id
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* =======================
   TOP CATEGORIES
======================= */

$topCategories = $pdo->query("
    SELECT c.name, SUM(b.quantity) total
    FROM books b
    JOIN categories c ON c.id = b.category_id
    GROUP BY b.category_id
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* =======================
   LOW STOCK
======================= */

$lowStock = $pdo->query("
    SELECT book_name, quantity
    FROM books
    WHERE quantity < 4
    ORDER BY quantity ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* =======================
   DIGITAL BOOKS
======================= */

$totalDigital = $pdo->query("
    SELECT COUNT(*) FROM digital_books WHERE is_active = 1
")->fetchColumn();

$totalDigitalInactive = $pdo->query("
    SELECT COUNT(*) FROM digital_books WHERE is_active = 0
")->fetchColumn();

$recentDigital = $pdo->query("
    SELECT db.title, db.author, db.file_type, db.uploaded_at, c.name AS category
    FROM digital_books db
    LEFT JOIN categories c ON c.id = db.category_id
    WHERE db.is_active = 1
    ORDER BY db.uploaded_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$topDigitalCategories = $pdo->query("
    SELECT c.name, COUNT(db.id) AS total
    FROM digital_books db
    JOIN categories c ON c.id = db.category_id
    WHERE db.is_active = 1
    GROUP BY db.category_id
    ORDER BY total DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

/* =======================
   OVERDUE STUDENTS
======================= */

$overdue = $pdo->query("
    SELECT 
        r.fullname,
        r.user_id,
        d.name AS department,
        ib.return_date
    FROM issued_books ib
    JOIN students r ON r.id = ib.student_id
    JOIN departments d ON d.id = r.department_id
    WHERE ib.returned_at IS NULL
      AND ib.return_date < CURDATE()
    ORDER BY ib.return_date ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Admin Dashboard | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <link rel="stylesheet" href="assets/css/adminui.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<body>
    <?php include 'includes/admin-header.php'; ?>

    <main class="main">
        <div class="page-container">

            <h2 class="page-title">Dashboard</h2>

            <div class="dashboard-grid">

                <!-- LIBRARY BOOKS -->
                <div class="dash-card">
                    <h3><i class="fa-solid fa-book"></i> Library Books</h3>

                    <p>Total Books: <strong><?= $totalBookTitles ?></strong></p>
                    <p>Total Copies: <strong><?= $totalBookCopies ?></strong></p>

                    <div class="card-actions center">
                        <a href="add-book.php" class="alert success">Add Book</a>
                        <a href="manage-books.php" class="alert info">Manage Books</a>
                    </div>
                </div>

                <!-- DIGITAL BOOKS -->
                <div class="dash-card">
                    <h3><i class="fa-solid fa-computer"></i> Digital Books</h3>

                    <p>Active: <strong><?= $totalDigital ?></strong></p>
                    <p>Inactive: <strong><?= $totalDigitalInactive ?></strong></p>

                    <div class="card-actions center">
                        <a href="add-digital-book.php" class="alert success">Add Digital</a>
                        <a href="manage-digital-books.php" class="alert info">Manage</a>
                    </div>
                </div>

                <!-- ISSUE & RETURN -->
                <div class="dash-card">
                    <h3><i class="fa-solid fa-book-open-reader"></i> Issue & Return</h3>

                    <p>Students Issued: <strong><?= $issuedStudents ?></strong></p>
                    <p>Books Returned: <strong><?= $returnedBooks ?></strong></p>

                    <div class="card-actions center">
                        <a href="issue-book.php" class="alert success">Issue Book</a>
                        <a href="issued-books.php" class="alert info">Manage Issued</a>
                    </div>
                </div>

                <!-- TOP STUDENTS -->
                <div class="dash-card">
                    <h3><i class="fa-solid fa-user-graduate"></i> Top Students</h3>

                    <ul class="list">
                        <?php foreach ($topStudents as $s): ?>
                            <div class="between">
                                <strong><?= htmlspecialchars($s['fullname']) ?></strong>
                                <span class="muted"><?=
                                htmlspecialchars($s['user_id']) ?></span>
                            </div>
                            <div class="muted">
                                <?= htmlspecialchars($s['department']) ?>
                                <span>(<?= $s['total_books'] ?>)</span>
                            </div>
                            <hr>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- STUDENTS -->
                <div class="dash-card">
                    <div class="sticky-title">
                        <h3><i class="fa-solid fa-users"></i> Students</h3>
                        <p>Total: <strong><?= $totalStudents ?></strong> | Blocked:
                            <strong><?= $blockedStudents ?></strong>
                        </p>
                    </div>

                    <div class="scroll-box">
                        <?php foreach ($deptStats as $d): ?>
                            <div class="between">
                                <span><?= htmlspecialchars($d['department']) ?></span>
                                <strong><?= $d['total'] ?></strong>
                            </div>
                            <hr>
                        <?php endforeach; ?>

                    </div>
                </div>

                <!-- TOP CATEGORIES -->
                <div class="dash-card">
                    <div class="sticky-title">
                        <h3><i class="fa-solid fa-layer-group"></i> Top Categories</h3>
                    </div>

                    <div class="scroll-box">
                        <?php foreach ($topCategories as $c): ?>
                            <div class="between">
                                <span><?= htmlspecialchars($c['name']) ?></span>
                                <span class="margin"><?= $c['total'] ?> Copies</span>
                            </div>
                            <hr>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- LOW STOCK -->
                <div class="dash-card">
                    <div class="sticky-title">
                        <h3><i class="fa-solid fa-triangle-exclamation"></i> Low Stock</h3>
                    </div>

                    <div class="scroll-box">
                        <?php if (!$lowStock): ?>
                            <p class="muted">No low stock books 🎉</p>
                        <?php endif; ?>

                        <?php foreach ($lowStock as $b): ?>
                            <div class="between">
                                <span><?= htmlspecialchars($b['book_name']) ?></span>
                                <?= $b['quantity'] ?> Left
                            </div>
                            <hr>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- RECENTLY UPLOADED DIGITAL BOOKS -->
                <div class="dash-card">
                    <div class="sticky-title">
                        <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Digital Uploads</h3>
                    </div>

                    <div class="scroll-box">
                        <?php if (!$recentDigital): ?>
                            <p class="muted">No digital books yet.</p>
                        <?php endif; ?>

                        <?php foreach ($recentDigital as $d): ?>
                            <div class="between">
                                <span><?= htmlspecialchars($d['title']) ?></span>
                                <span class="muted"><?= strtoupper(htmlspecialchars($d['file_type'])) ?></span>
                            </div>
                            <div class="muted">
                                <?= htmlspecialchars($d['author'] ?? '—') ?>
                                &bull; <?= date('d M Y', strtotime($d['uploaded_at'])) ?>
                            </div>
                            <hr>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- DIGITAL BOOKS BY CATEGORY -->
                <div class="dash-card">
                    <div class="sticky-title">
                        <h3><i class="fa-solid fa-layer-group"></i> Digital by Category</h3>
                    </div>

                    <div class="scroll-box">
                        <?php if (!$topDigitalCategories): ?>
                            <p class="muted">No data yet.</p>
                        <?php endif; ?>

                        <?php foreach ($topDigitalCategories as $dc): ?>
                            <div class="between">
                                <span><?= htmlspecialchars($dc['name']) ?></span>
                                <strong><?= $dc['total'] ?></strong>
                            </div>
                            <hr>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- OVERDUE -->
                <div class="dash-card">
                    <div class="sticky-title">
                        <h3><i class="fa-solid fa-clock"></i> Overdue Students</h3>
                    </div>

                    <div class="scroll-box">
                        <?php if (!$overdue): ?>
                            <p class="muted">No overdue books 🎉</p>
                        <?php endif; ?>

                        <ul class="list">
                            <?php foreach ($overdue as $o): ?>
                                <strong><?= htmlspecialchars($o['fullname']) ?></strong>
                                <div class="muted"><?= htmlspecialchars($o['user_id']) ?> •
                                    <?= htmlspecialchars($o['department']) ?>
                                </div>
                                Due: <strong><?= date('d M Y', strtotime($o['return_date'])) ?></strong>
                                <hr>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                </div>

            </div>
        </div>
    </main>
    <?php include '../includes/_footer.php'; ?>

</body>

</html>