<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth-admin.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT
        r.id,
        r.user_id,
        r.fullname,
        r.email,
        r.mobile,
        d.name AS department
    FROM students r
    LEFT JOIN departments d ON d.id = r.department_id
    WHERE r.fullname LIKE :q1
       OR r.user_id LIKE :q2
       OR d.name LIKE :q3
    ORDER BY r.fullname ASC
    LIMIT 10
";

$stmt = $pdo->prepare($sql);

$like = "%$q%";
$stmt->bindValue(':q1', $like, PDO::PARAM_STR);
$stmt->bindValue(':q2', $like, PDO::PARAM_STR);
$stmt->bindValue(':q3', $like, PDO::PARAM_STR);

$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
exit;