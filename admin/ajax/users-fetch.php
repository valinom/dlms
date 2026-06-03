<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth-admin.php';

header('Content-Type: application/json');

/* ================= INPUT ================= */
$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$sort   = $_GET['sort'] ?? 'name';
$page   = max(1, (int)($_GET['page'] ?? 1));

$limit  = 12;
$offset = ($page - 1) * $limit;

/* ================= WHERE ================= */
$where = [];
$bind  = [];

/* ================= SEARCH ================= */
if ($q !== '') {
    $where[] = "(
        r.fullname   LIKE :q1
        OR r.user_id LIKE :q2
        OR r.email   LIKE :q3
        OR d.name    LIKE :q4
    )";

    $bind[':q1'] = "%$q%";
    $bind[':q2'] = "%$q%";
    $bind[':q3'] = "%$q%";
    $bind[':q4'] = "%$q%";
}

/* STATUS FILTER */
if ($status !== '') {
    $where[] = "r.status = :status";
    $bind[':status'] = $status;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ================= SORT ================= */
$orderBy = "r.fullname ASC";
if ($sort === 'department') {
    $orderBy = "d.name ASC, r.fullname ASC";
}

/* ================= COUNT ================= */
$countSql = "
    SELECT COUNT(DISTINCT r.id)
    FROM students r
    LEFT JOIN departments d ON d.id = r.department_id
    LEFT JOIN issued_books ib ON ib.student_id = r.id
    $whereSQL
";

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($bind);

$totalUsers = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalUsers / $limit));

/* ================= FETCH ================= */
$sql = "
    SELECT
        r.id,
        r.user_id,
        r.fullname,
        r.email,
        r.mobile,
        r.status,
        d.name AS department,

        COUNT(CASE 
            WHEN ib.id IS NOT NULL AND ib.returned_at IS NULL 
            THEN 1 END) AS books_issued,

        COUNT(CASE 
            WHEN ib.id IS NOT NULL AND ib.returned_at IS NOT NULL 
            THEN 1 END) AS books_returned

    FROM students r
    LEFT JOIN departments d ON d.id = r.department_id
    LEFT JOIN issued_books ib ON ib.student_id = r.id
    $whereSQL
    GROUP BY
        r.id,
        r.user_id,
        r.fullname,
        r.email,
        r.mobile,
        r.status,
        d.name
    ORDER BY $orderBy
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($bind);

echo json_encode([
    'users'       => $stmt->fetchAll(PDO::FETCH_ASSOC),
    'totalPages'  => $totalPages,
    'currentPage' => $page
]);
exit;