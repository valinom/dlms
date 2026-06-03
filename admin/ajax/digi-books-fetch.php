<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth-admin.php';

$limit  = 10;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$search   = trim($_GET['search'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$language = (int)($_GET['language'] ?? 0);
$status   = $_GET['status'] ?? '';
$sort     = $_GET['sort'] ?? 'title';

$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = "(db.title LIKE ? OR db.author LIKE ? OR c.name LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($category > 0) {
    $where[]  = "db.category_id = ?";
    $params[] = $category;
}
if ($language > 0) {
    $where[]  = "db.language_id = ?";
    $params[] = $language;
}
if ($status !== '') {
    $where[]  = "db.is_active = ?";
    $params[] = (int)$status;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

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
    SELECT
        db.id, db.title, db.author, db.file_name, db.file_type,
        db.file_size, db.cover_image, db.is_active, db.uploaded_at,
        db.book_id,
        c.name  AS category_name,
        l.name  AS language_name,
        b.book_name AS linked_book,
        a.fullname  AS uploader_name
    FROM digital_books db
    LEFT JOIN categories c ON c.id = db.category_id
    LEFT JOIN languages l  ON l.id = db.language_id
    LEFT JOIN books b      ON b.id = db.book_id
    LEFT JOIN admin a      ON a.id = db.uploaded_by
    $whereSQL
    ORDER BY $orderBy
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
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
        'is_active'     => (int)$b['is_active'],
        'uploaded_at'   => date('d M Y', strtotime($b['uploaded_at'])),
        'category_name' => $b['category_name'] ?? 'Uncategorized',
        'language_name' => $b['language_name'] ?? '—',
        'linked_book'   => $b['linked_book'] ?? '',
        'uploader_name' => $b['uploader_name'] ?? '—',
    ], $books)
]);
