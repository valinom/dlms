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
    SELECT id, book_name, isbn, author_name, image, quantity
    FROM books
    WHERE book_name LIKE :q1
       OR isbn LIKE :q2
    LIMIT 10
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':q1', "%$q%", PDO::PARAM_STR);
$stmt->bindValue(':q2', "%$q%", PDO::PARAM_STR);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
exit;
