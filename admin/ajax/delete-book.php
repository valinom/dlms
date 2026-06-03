<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth-admin.php';

header('Content-Type: application/json');

verify_csrf();

$bookId = (int)($_POST['book_id'] ?? 0);

if ($bookId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid book']);
    exit;
}

/* Fetch image */
$stmt = $pdo->prepare("SELECT image FROM books WHERE id = ?");
$stmt->execute([$bookId]);
$image = $stmt->fetchColumn();

try {
    $pdo->beginTransaction();

    $pdo->prepare("DELETE FROM issued_books WHERE book_id = ?")
        ->execute([$bookId]);

    $pdo->prepare("DELETE FROM books WHERE id = ?")
        ->execute([$bookId]);

    $pdo->commit();

    /* Delete image file */
    if ($image && file_exists(__DIR__ . '/../../uploads/books/' . $image)) {
        unlink(__DIR__ . '/../../uploads/books/' . $image);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Delete failed']);
}

exit;