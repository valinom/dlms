<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth-admin.php';

header('Content-Type: application/json');

verify_csrf();

$id = (int) ($_POST['user_id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user']);
    exit;
}

try {
    $pdo->beginTransaction();

    $pdo->prepare("DELETE FROM issued_books WHERE student_id = ?")
        ->execute([$id]);

    $pdo->prepare("DELETE FROM students WHERE id = ?")
        ->execute([$id]);

    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Delete failed']);
}

exit;
