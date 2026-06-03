<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth-admin.php';

header('Content-Type: application/json');

$q      = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 12;
$offset = ($page - 1) * $limit;

$where  = [];
$params = [];

if ($q !== '') {
    $where[] = "(r.fullname LIKE :q1 OR r.user_id LIKE :q2 OR b.book_name LIKE :q3 OR b.isbn LIKE :q4)";
    $params[':q1'] = "%$q%";
    $params[':q2'] = "%$q%";
    $params[':q3'] = "%$q%";
    $params[':q4'] = "%$q%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* COUNT */
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM issued_books ib
    JOIN students r ON ib.student_id = r.id
    JOIN books b ON ib.book_id = b.id
    $whereSQL
");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

/* FETCH */
$stmt = $pdo->prepare("
    SELECT
        ib.id,
        ib.issue_date,
        ib.return_date,
        ib.returned_at,
        ib.status,
        r.user_id,
        r.fullname,
        b.book_name,
        b.isbn
    FROM issued_books ib
    JOIN students r ON ib.student_id = r.id
    JOIN books b ON ib.book_id = b.id
    $whereSQL
    ORDER BY
        CASE
            WHEN ib.status = 'issued' AND ib.return_date < CURDATE() THEN 0
            WHEN ib.status = 'issued' THEN 1
            WHEN ib.status = 'returned' THEN 2
        END,
        ib.return_date ASC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'rows'       => $rows,
    'total'      => $total,
    'totalPages' => $totalPages,
    'page'       => $page,
]);
