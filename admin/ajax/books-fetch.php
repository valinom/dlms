<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth-admin.php';

$limit  = 8;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$search   = trim($_GET['search'] ?? '');
$filter   = $_GET['filter'] ?? 'all';
$sort     = $_GET['sort'] ?? 'name';
$category = (int)($_GET['category'] ?? 0);

$where = [];

if ($search !== '') {
    $where[] = "(b.book_name LIKE :s1 OR b.author_name LIKE :s2 OR b.isbn LIKE :s3)";
}
if ($category > 0) {
    $where[] = "b.category_id = :cat";
}
if ($filter === 'available') {
    $where[] = "b.quantity > 0";
} elseif ($filter === 'out') {
    $where[] = "b.quantity = 0";
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

$orderBy = match ($sort) {
    'author'   => 'b.author_name ASC',
    'quantity' => 'b.quantity DESC',
    'category' => 'c.name ASC',
    default    => 'b.book_name ASC'
};

$countSQL = "SELECT COUNT(*) FROM books b LEFT JOIN categories c ON b.category_id = c.id $whereSQL";
$countStmt = $pdo->prepare($countSQL);
if ($search !== '') {
    $countStmt->bindValue(':s1', "%$search%");
    $countStmt->bindValue(':s2', "%$search%");
    $countStmt->bindValue(':s3', "%$search%");
}
if ($category > 0) $countStmt->bindValue(':cat', $category, PDO::PARAM_INT);
$countStmt->execute();
$total      = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

$sql = "
    SELECT b.*, c.name AS category_name,
           EXISTS(SELECT 1 FROM digital_books d WHERE d.book_id = b.id) AS has_digital
    FROM books b
    LEFT JOIN categories c ON b.category_id = c.id
    $whereSQL
    ORDER BY $orderBy
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
if ($search !== '') {
    $stmt->bindValue(':s1', "%$search%");
    $stmt->bindValue(':s2', "%$search%");
    $stmt->bindValue(':s3', "%$search%");
}
if ($category > 0) $stmt->bindValue(':cat', $category, PDO::PARAM_INT);
$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'total'      => (int)$total,
    'totalPages' => (int)$totalPages,
    'page'       => (int)$page,
    'books'      => array_map(fn($b) => [
        'id'            => (int)$b['id'],
        'book_name'     => $b['book_name'],
        'author_name'   => $b['author_name'],
        'isbn'          => $b['isbn'],
        'quantity'      => (int)$b['quantity'],
        'image'         => $b['image'] ?? '',
        'category_name' => $b['category_name'] ?? 'Uncategorized',
        'has_digital'   => (bool)$b['has_digital'],
    ], $books)
]);
