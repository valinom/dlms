<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-student.php';

header('Content-Type: text/html');

$studentId = (int)$_SESSION['user_id'];

$limit  = 10;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? '');
$sort   = $_GET['sort'] ?? 'bookmarked';

/* ================= WHERE ================= */
$where  = ['bk.student_id = ?', 'db.is_active = 1'];
$params = [$studentId];

if ($search !== '') {
    $where[]  = "(db.title LIKE ? OR db.author LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

/* ================= SORT ================= */
$orderBy = match ($sort) {
    'title'  => 'db.title ASC',
    'author' => 'db.author ASC',
    default  => 'bk.created_at DESC'
};

/* ================= COUNT ================= */
$countSQL = "
    SELECT COUNT(*)
    FROM digi_bookmarks bk
    JOIN digital_books db ON db.id = bk.digi_id
    $whereSQL
";

$countStmt = $pdo->prepare($countSQL);
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

/* ================= FETCH ================= */
$sql = "
    SELECT
        db.id,
        db.title,
        db.author,
        db.description,
        db.file_name,
        db.file_type,
        db.file_size,
        db.cover_image,
        db.uploaded_at,
        c.name AS category_name,
        l.name AS language_name,
        bk.created_at AS bookmarked_at
    FROM digi_bookmarks bk
    JOIN digital_books db ON db.id = bk.digi_id
    LEFT JOIN categories c ON c.id = db.category_id
    LEFT JOIN languages l  ON l.id = db.language_id
    $whereSQL
    ORDER BY $orderBy
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatSize(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
?>

<?php if (!$books): ?>
    <div class="empty-state" style="grid-column:1/-1">
        <i class="fa-solid fa-bookmark"></i>
        <p>No bookmarked books yet.</p>
        <a href="digital-books.php" class="alert info" style="display:inline-block;margin-top:1rem;">
            Browse Digital Books
        </a>
    </div>
<?php endif; ?>

<?php foreach ($books as $b):
    $cover = !empty($b['cover_image'])
        ? "../ajax/book-cover.php?file=" . urlencode($b['cover_image'])
        : "../assets/img/no-book.png";
?>
<div class="bm-card mybook-card" data-id="<?= (int)$b['id'] ?>">

    <div class="bm-top">
        <!-- Cover -->
        <div class="bm-cover">
            <?php if ($cover): ?>
                <img src="<?= $cover ?>" alt="Cover"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="bm-info">
            <div class="bm-title"><?= e($b['title']) ?></div>
            <div class="bm-author"><i class="fa-solid fa-user-pen fa-xs"></i> <?= e($b['author'] ?? '—') ?></div>

            <div class="bm-tags">
                <span class="bm-tag cat"><i class="fa-solid fa-tag fa-xs"></i> <?= e($b['category_name'] ?? 'Uncategorized') ?></span>
                <span class="bm-tag lang"><i class="fa-solid fa-language fa-xs"></i> <?= e($b['language_name'] ?? '—') ?></span>
                <span class="bm-tag type"><?= strtoupper(e($b['file_type'] ?? '')) ?> · <?= formatSize((int)$b['file_size']) ?></span>
            </div>

            <div class="bm-date">
                <i class="fa-solid fa-bookmark fa-xs"></i>
                Saved <?= date('d M Y', strtotime($b['bookmarked_at'])) ?>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="bm-actions">
        <button class="bm-btn read read-btn">
            <i class="fa-solid fa-book-open"></i> Read
        </button>
        <button class="bm-btn remove"
                onclick="event.stopPropagation(); removeBookmark(<?= (int)$b['id'] ?>)">
            <i class="fa-solid fa-bookmark-slash"></i> Remove
        </button>
    </div>

</div>
<?php endforeach; ?>

<!-- SPLIT -->

<?php if ($totalPages > 1): ?>
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="javascript:void(0)"
       onclick="changePage(<?= $i ?>)"
       class="page-link <?= $i === $page ? 'active' : '' ?>">
        <?= $i ?>
    </a>
<?php endfor; ?>
<?php endif; ?>
