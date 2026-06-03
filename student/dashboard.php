<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-student.php';

$studentId = (int)$_SESSION['user_id'];

/* Issued books */
$issuedCount = $pdo->prepare("
    SELECT COUNT(*) FROM issued_books
    WHERE student_id = ? AND status = 'issued'
");
$issuedCount->execute([$studentId]);
$issuedCount = (int)$issuedCount->fetchColumn();

/* Returned books */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM issued_books WHERE student_id = ? AND status = 'returned'");
$stmt->execute([$studentId]);
$returnedCount = (int)$stmt->fetchColumn();

/* Favorites */
$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE student_id = ?");
$stmt2->execute([$studentId]);
$favCount = (int)$stmt2->fetchColumn();

/* Digital bookmarks */
$stmt3 = $pdo->prepare("SELECT COUNT(*) FROM digi_bookmarks WHERE student_id = ?");
$stmt3->execute([$studentId]);
$bookmarkCount = (int)$stmt3->fetchColumn();

/* Overdue books */
$stmt4 = $pdo->prepare("
    SELECT ib.id, b.book_name, ib.return_date
    FROM issued_books ib
    JOIN books b ON b.id = ib.book_id
    WHERE ib.student_id = ? AND ib.status = 'issued' AND ib.return_date < CURDATE()
    ORDER BY ib.return_date ASC
");
$stmt4->execute([$studentId]);
$overdue = $stmt4->fetchAll();

/* Current issued */
$stmt5 = $pdo->prepare("
    SELECT ib.id, b.book_name, b.author_name, ib.issue_date, ib.return_date
    FROM issued_books ib
    JOIN books b ON b.id = ib.book_id
    WHERE ib.student_id = ? AND ib.status = 'issued'
    ORDER BY ib.return_date ASC
    LIMIT 5
");
$stmt5->execute([$studentId]);
$currentIssued = $stmt5->fetchAll();

/* Recent digital books */
$recentDigital = $pdo->query("
    SELECT id, title, author, file_type, uploaded_at
    FROM digital_books
    WHERE is_active = 1
    ORDER BY uploaded_at DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Dashboard | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <style>
        .stat-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem; margin-bottom:1.4rem; }
        .stat-card { background:var(--bg-card); border:1px solid var(--border-soft); border-radius:var(--radius); padding:1.2rem 1.4rem; display:flex; align-items:center; gap:1rem; }
        .stat-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
        .stat-icon.blue   { background:var(--accent-light); color:var(--accent); }
        .stat-icon.green  { background:var(--success-bg);   color:var(--success); }
        .stat-icon.red    { background:var(--danger-bg);     color:var(--danger); }
        .stat-icon.yellow { background:var(--warning-bg);   color:var(--warning); }
        .stat-val  { font-size:1.6rem; font-weight:900; color:var(--text-main); line-height:1; }
        .stat-lbl  { font-size:.78rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin-top:.2rem; }
        .dash-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:1.2rem; }
        .dash-card { background:var(--bg-card); border:1px solid var(--border-soft); border-radius:var(--radius); padding:1.2rem 1.4rem; }
        .dash-card h3 { font-size:.95rem; font-weight:700; margin-bottom:.8rem; display:flex; align-items:center; gap:.5rem; }
        .dash-card h3 i { color:var(--accent); }
        .book-row { display:flex; justify-content:space-between; align-items:flex-start; padding:.5rem 0; border-bottom:1px solid var(--border-soft); font-size:.85rem; }
        .book-row:last-child { border-bottom:none; }
        .overdue-row { background:var(--danger-bg); border-radius:6px; padding:.4rem .7rem; margin:.3rem 0; font-size:.83rem; color:var(--danger); }
    </style>
</head>
<body>
    <?php include 'includes/student-header.php'; ?>
    <?php include '../includes/_toast.php'; ?>

    <main class="main">
        <div class="page-container">
            <h2 class="page-title">Welcome back, <?= e(explode(' ', $_SESSION['student_name'])[0]) ?>! 👋</h2>

            <!-- Stats -->
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-book-open-reader"></i></div>
                    <div><div class="stat-val"><?= $issuedCount ?></div><div class="stat-lbl">Books Issued</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-check-circle"></i></div>
                    <div><div class="stat-val"><?= $returnedCount ?></div><div class="stat-lbl">Books Returned</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fa-solid fa-heart"></i></div>
                    <div><div class="stat-val"><?= $favCount ?></div><div class="stat-lbl">Favorites</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow"><i class="fa-solid fa-bookmark"></i></div>
                    <div><div class="stat-val"><?= $bookmarkCount ?></div><div class="stat-lbl">Bookmarks</div></div>
                </div>
            </div>

            <!-- Cards -->
            <div class="dash-grid">

                <!-- Overdue -->
                <?php if ($overdue): ?>
                <div class="dash-card">
                    <h3><i class="fa-solid fa-clock"></i> Overdue Books</h3>
                    <?php foreach ($overdue as $o): ?>
                    <div class="overdue-row">
                        <strong><?= e($o['book_name']) ?></strong><br>
                        Due: <?= date('d M Y', strtotime($o['return_date'])) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Current Issued -->
                <div class="dash-card">
                    <h3><i class="fa-solid fa-book-open-reader"></i> Currently Issued</h3>
                    <?php if (!$currentIssued): ?>
                    <p style="color:var(--text-muted);font-size:.85rem">No books currently issued.</p>
                    <?php endif; ?>
                    <?php foreach ($currentIssued as $b): ?>
                    <div class="book-row">
                        <div>
                            <strong><?= e($b['book_name']) ?></strong>
                            <div style="color:var(--text-muted);font-size:.78rem"><?= e($b['author_name']) ?></div>
                        </div>
                        <span style="font-size:.75rem;color:var(--text-muted)">Due: <?= date('d M', strtotime($b['return_date'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if ($issuedCount > 5): ?>
                    <a href="issued.php" class="alert info" style="margin-top:.8rem;font-size:.8rem">View all</a>
                    <?php endif; ?>
                </div>

                <!-- Recent Digital -->
                <div class="dash-card">
                    <h3><i class="fa-solid fa-computer"></i> New Digital Books</h3>
                    <?php if (!$recentDigital): ?>
                    <p style="color:var(--text-muted);font-size:.85rem">No digital books yet.</p>
                    <?php endif; ?>
                    <?php foreach ($recentDigital as $d): ?>
                    <div class="book-row">
                        <div>
                            <strong><?= e($d['title']) ?></strong>
                            <div style="color:var(--text-muted);font-size:.78rem"><?= e($d['author'] ?? '—') ?></div>
                        </div>
                        <span style="font-size:.73rem;background:var(--accent-light);color:var(--accent);padding:.15rem .5rem;border-radius:99px;font-weight:700">
                            <?= strtoupper(e($d['file_type'])) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <a href="digital-books.php" class="alert primary" style="margin-top:.8rem;font-size:.8rem">Browse All</a>
                </div>

                <!-- Quick links -->
                <div class="dash-card">
                    <h3><i class="fa-solid fa-bolt"></i> Quick Links</h3>
                    <div style="display:flex;flex-direction:column;gap:.5rem;margin-top:.4rem">
                        <a href="books.php"         class="alert info"   ><i class="fa-solid fa-book"></i> Browse Books</a>
                        <a href="digital-books.php" class="alert success"><i class="fa-solid fa-computer"></i> Digital Books</a>
                        <a href="favorites.php"     class="alert danger" ><i class="fa-solid fa-heart"></i> My Favorites</a>
                        <a href="issued.php"        class="alert warning"><i class="fa-solid fa-bookmark"></i> Issued Books</a>
                    </div>
                </div>

            </div>
        </div>
    </main>
    <?php include '../includes/_footer.php'; ?>
</body>
</html>
