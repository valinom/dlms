<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth-admin.php';

header('Content-Type: application/json');
verify_csrf();

$id = (int) ($_POST['user_id'] ?? 0);

$stmt = $pdo->prepare("
    UPDATE students
    SET status = IF(status = 1, 0, 1)
    WHERE id = :id
");
$stmt->execute(['id' => $id]);

echo json_encode(['success' => true]);
