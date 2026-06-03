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
    SELECT COUNT(*) FROM favorites WHERE student_id = ?
");
$countStmt->execute([$studentId]);
$total      = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

/* FETCH */
$stmt = $pdo->prepare("
    SELECT b.id, b.book_name, b.author_name, b.isbn, b.image, b.quantity,
           c.name AS category_name
    FROM favorites f
    JOIN books b ON b.id = f.book_id
    LEFT JOIN categories c ON c.id = b.category_id
    WHERE f.student_id = ?
    ORDER BY f.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute([$studentId]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (empty($favorites)): ?>
<div class="empty-state" style="grid-column:1/-1">
    <i class="fa-solid fa-heart-crack"></i>
    No favorites yet. <a href="books.php">Browse books</a> and click the ♥ button.
</div>
<?php endif; ?>

<?php foreach ($favorites as $b):
    $img = !empty($b['image'])
        ? '../ajax/book-cover.php?file=' . urlencode($b['image'])
        : null;
    $qty = (int)$b['quantity'];
?>
<div class="fav-card" id="fav-<?= $b['id'] ?>">

    <!-- Cover -->
    <div class="fav-cover">
        <?php if ($img): ?>
            <img src="<?= $img ?>" alt="Cover"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <div class="fav-cover-placeholder" style="display:none">
                <i class="fa-solid fa-book"></i>
            </div>
        <?php else: ?>
            <img src="../assets/img/no-book.png">
        <?php endif; ?>
    </div>

    <!-- Info -->
    <div class="fav-info">
        <div class="fav-title"><?= e($b['book_name']) ?></div>
        <div class="fav-author"><i class="fa-solid fa-user-pen fa-xs"></i> <?= e($b['author_name']) ?></div>
        <div class="fav-tags">
            <span class="fav-tag cat"><i class="fa-solid fa-tag fa-xs"></i> <?= e($b['category_name'] ?? 'Uncategorized') ?></span>
            <span class="fav-tag isbn">ISBN: <?= e($b['isbn']) ?></span>
        </div>
        <?php if ($qty === 0): ?>
            <span class="fav-stock out">Out of Stock</span>
        <?php else: ?>
            <span class="fav-stock in"><i class="fa-solid fa-circle-check fa-xs"></i> <?= $qty ?> in stock</span>
        <?php endif; ?>
    </div>

    <!-- Remove -->
    <button class="fav-remove-btn" onclick="removeFavorite(<?= $b['id'] ?>)" title="Remove from favorites">
        <i class="fa-solid fa-heart"></i>
    </button>

</div>
<?php endforeach; ?>

<!-- SPLIT -->

<?php if ($totalPages > 1): ?>
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
<a href="javascript:void(0)" onclick="changePage(<?= $i ?>)"
   class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
<?php endfor; ?>
<?php endif; ?>
