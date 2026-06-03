<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-student.php';

header('Content-Type: text/html');

$studentId = (int)$_SESSION['user_id'];
$limit     = 10;
$page      = max(1, (int)($_GET['page'] ?? 1));
$offset    = ($page - 1) * $limit;

/* COUNT */
$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM issued_books WHERE student_id = ?
");
$countStmt->execute([$studentId]);
$total      = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

/* FETCH */
$stmt = $pdo->prepare("
    SELECT
        ib.id, ib.issue_date, ib.return_date, ib.returned_at, ib.status,
        b.book_name, b.author_name, b.isbn, b.image
    FROM issued_books ib
    JOIN books b ON b.id = ib.book_id
    WHERE ib.student_id = ?
    ORDER BY
        CASE WHEN ib.status = 'issued' THEN 0 ELSE 1 END,
        ib.return_date ASC
    LIMIT $limit OFFSET $offset
");
$stmt->execute([$studentId]);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (empty($books)): ?>
<div style="background:var(--bg-card);border:1px solid var(--border-soft);border-radius:var(--radius);padding:3rem;text-align:center;color:var(--text-muted)">
    <i class="fa-solid fa-book-open" style="font-size:2rem;margin-bottom:.8rem;display:block;opacity:.3"></i>
    No books issued yet. <a href="books.php">Browse books</a>
</div>
<?php endif; ?>

<?php foreach ($books as $b):
    $img = !empty($b['image'])
        ? '../ajax/book-cover.php?file=' . urlencode($b['image'])
        : '../assets/img/no-book.png';
    $isOverdue = $b['status'] === 'issued' && strtotime($b['return_date']) < time();
?>
<div class="issue-card <?= $isOverdue ? 'overdue' : '' ?>">
    <img src="<?= $img ?>" alt="Cover"
         onerror="this.src='../assets/img/no-book.png'">
    <div class="issue-info">
        <h3><?= e($b['book_name']) ?></h3>
        <p><?= e($b['author_name']) ?></p>
        <p>ISBN: <?= e($b['isbn']) ?></p>
        <p>Issued: <?= date('d M Y', strtotime($b['issue_date'])) ?></p>
        <p>Due: <strong><?= date('d M Y', strtotime($b['return_date'])) ?></strong>
            <?php if ($isOverdue): ?>
                <span style="color:var(--danger);font-size:.72rem"> ⚠ Overdue</span>
            <?php endif; ?>
        </p>
        <?php if ($b['returned_at']): ?>
        <p>Returned: <?= date('d M Y', strtotime($b['returned_at'])) ?></p>
        <?php endif; ?>
        <span class="issue-status <?= e($b['status']) ?>"><?= e($b['status']) ?></span>
    </div>
</div>
<?php endforeach; ?>

<!-- SPLIT -->

<?php if ($totalPages > 1): ?>
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
<a href="javascript:void(0)" onclick="changePage(<?= $i ?>)"
   class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
<?php endfor; ?>
<?php endif; ?>
