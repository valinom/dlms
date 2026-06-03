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

/* Fetch file info before deleting */
$stmt = $pdo->prepare("SELECT file_name, cover_image FROM digital_books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    echo json_encode(['success' => false, 'error' => 'Book not found']);
    exit;
}

try {
    $pdo->prepare("DELETE FROM digital_books WHERE id = ?")->execute([$id]);

    /* Delete document file */
    $docPath = __DIR__ . '/../../uploads/documents/' . $book['file_name'];
    if ($book['file_name'] && file_exists($docPath)) {
        unlink($docPath);
    }

    /* Delete cover image */
    if ($book['cover_image']) {
        $coverPath = __DIR__ . '/../../uploads/books/' . $book['cover_image'];
        if (file_exists($coverPath)) {
            unlink($coverPath);
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Delete failed']);
}

exit;
