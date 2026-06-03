<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-student.php';

$studentId = (int)$_SESSION['user_id'];

$limit  = 10;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$search   = trim($_GET['search'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$language = (int)($_GET['language'] ?? 0);
$sort     = $_GET['sort'] ?? 'title';

$where    = ['db.is_active = 1'];
$params   = [];

if ($search !== '') {
    $where[]  = "(db.title LIKE ? OR db.author LIKE ? OR c.name LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($category > 0) { $where[] = "db.category_id = ?"; $params[] = $category; }
if ($language > 0) { $where[] = "db.language_id = ?"; $params[] = $language; }

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$orderBy = match ($sort) {
    'author' => 'db.author ASC',
    'date'   => 'db.uploaded_at DESC',
    default  => 'db.title ASC'
};

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM digital_books db LEFT JOIN categories c ON c.id = db.category_id LEFT JOIN languages l ON l.id = db.language_id $whereSQL");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

$stmt = $pdo->prepare("
    SELECT db.id, db.title, db.author, db.file_type, db.file_size,
           db.cover_image, db.uploaded_at,
           c.name AS category_name, l.name AS language_name,
           (bk.id IS NOT NULL) AS is_bookmarked
    FROM digital_books db
    LEFT JOIN categories c ON c.id = db.category_id
    LEFT JOIN languages  l ON l.id = db.language_id
    LEFT JOIN digi_bookmarks bk ON bk.digi_id = db.id AND bk.student_id = ?
    $whereSQL
    ORDER BY $orderBy
    LIMIT $limit OFFSET $offset
");
$stmt->execute(array_merge([$studentId], $params));
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

function fmtSize(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

header('Content-Type: application/json');
echo json_encode([
    'total'      => $total,
    'totalPages' => $totalPages,
    'page'       => $page,
    'books'      => array_map(fn($b) => [
        'id'            => (int)$b['id'],
        'title'         => $b['title'],
        'author'        => $b['author'] ?? '—',
        'file_type'     => strtoupper($b['file_type']),
        'file_size'     => fmtSize((int)$b['file_size']),
        'cover_image'   => $b['cover_image'] ?? '',
        'uploaded_at'   => date('d M Y', strtotime($b['uploaded_at'])),
        'category_name' => $b['category_name'] ?? 'Uncategorized',
        'language_name' => $b['language_name'] ?? '—',
        'is_bookmarked' => (bool)$b['is_bookmarked'],
    ], $books)
]);
