<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth-admin.php';

header('Content-Type: application/json');

verify_csrf();

$id = (int)($_POST['digi_id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid book']);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE digital_books
    SET is_active = IF(is_active = 1, 0, 1)
    WHERE id = ?
");
$stmt->execute([$id]);

echo json_encode(['success' => true]);
exit;
