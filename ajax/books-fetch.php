<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-student.php';

header('Content-Type: text/html');

$studentId = (int)$_SESSION['user_id'];
$limit     = 10;
$page      = max(1, (int)($_GET['page'] ?? 1));
$offset    = ($page - 1) * $limit;

$search   = trim($_GET['search'] ?? '');
$filter   = $_GET['filter'] ?? 'all';
$sort     = $_GET['sort'] ?? 'name';
$category = (int)($_GET['category'] ?? 0);

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = "(b.book_name LIKE ? OR b.author_name LIKE ? OR b.isbn LIKE ? OR c.name LIKE ?)";
    $like    = "%$search%";
    array_push($params, $like, $like, $like, $like);
}
if ($category > 0) { $where[] = "b.category_id = ?"; $params[] = $category; }
if ($filter === 'available') { $where[] = "b.quantity > 0"; }
elseif ($filter === 'out')   { $where[] = "b.quantity = 0"; }

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$orderBy  = match ($sort) {
    'author'   => 'b.author_name ASC',
    'quantity' => 'b.quantity DESC',
    'category' => 'c.name ASC',
    default    => 'b.book_name ASC'
};

/* COUNT */
$countStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT b.id) FROM books b
    LEFT JOIN categories c ON c.id = b.category_id
    LEFT JOIN favorites f  ON f.book_id = b.id AND f.student_id = ?
    $whereSQL
");
$countStmt->execute(array_merge([$studentId], $params));
$totalBooks = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalBooks / $limit));

/* FETCH */
$stmt = $pdo->prepare("
    SELECT DISTINCT b.id, b.book_name, b.author_name, b.isbn, b.image, b.quantity,
           c.name AS category_name,
           (f.id IS NOT NULL) AS is_favorite
    FROM books b
    LEFT JOIN categories c ON c.id = b.category_id
    LEFT JOIN favorites f  ON f.book_id = b.id AND f.student_id = ?
    $whereSQL
    ORDER BY $orderBy
    LIMIT $limit OFFSET $offset
");
$stmt->execute(array_merge([$studentId], $params));
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (empty($books)): ?>
<p style="color:var(--text-muted);padding:2rem;text-align:center">
    <i class="fa-solid fa-book-open fa-2x" style="display:block;margin-bottom:.5rem;opacity:.3"></i>
    No books found.
</p>
<?php endif; ?>

<?php foreach ($books as $b):
    $qty = (int)$b['quantity'];
    $img = !empty($b['image'])
        ? '../ajax/book-cover.php?file=' . urlencode($b['image'])
        : '../assets/img/no-book.png';
?>
<div class="bl-card">

    <!-- Cover -->
    <div class="bl-cover">
        <img src="<?= $img ?>" alt="Cover"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">

    </div>

    <!-- Info -->
    <div class="bl-info">
        <div class="bl-title"><?= htmlspecialchars($b['book_name']) ?></div>
        <div class="bl-author"><i class="fa-solid fa-user-pen fa-xs"></i> <?= htmlspecialchars($b['author_name']) ?></div>

        <div class="bl-tags">
            <span class="bl-tag cat"><i class="fa-solid fa-tag fa-xs"></i> <?= htmlspecialchars($b['category_name'] ?? 'Uncategorized') ?></span>
            <span class="bl-tag isbn">ISBN: <?= htmlspecialchars($b['isbn']) ?></span>
        </div>

        <?php if ($qty === 0): ?>
            <span class="bl-stock out">Unavailable</span>
        <?php else: ?>
            <span class="bl-stock in"><i class="fa-solid fa-circle-check fa-xs"></i> <?= $qty ?> available</span>
        <?php endif; ?>
    </div>

    <!-- Favourite -->
    <button class="fav-btn <?= $b['is_favorite'] ? 'active' : '' ?>"
            onclick="toggleFavorite(<?= (int)$b['id'] ?>, this)"
            title="<?= $b['is_favorite'] ? 'Remove from favorites' : 'Add to favorites' ?>">
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
