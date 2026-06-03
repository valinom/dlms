<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: issued-books.php');
    exit;
}

$issueId = (int) $_GET['id'];

try {
    $pdo->beginTransaction();

    /* Lock issue row */
    $stmt = $pdo->prepare("
        SELECT book_id, status
        FROM issued_books
        WHERE id = :id
        FOR UPDATE
    ");
    $stmt->execute([':id' => $issueId]);
    $issue = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$issue) {
        throw new Exception('Invalid issue record.');
    }

    if ($issue['status'] === 'returned') {
        throw new Exception('This book is already returned.');
    }

    /* Mark as returned */
    $stmt = $pdo->prepare("
        UPDATE issued_books
        SET 
            status = 'returned',
            returned_at = CURDATE()
        WHERE id = :id
    ");
    $stmt->execute([':id' => $issueId]);

    /* Increase book quantity */
    $stmt = $pdo->prepare("
        UPDATE books
        SET quantity = quantity + 1
        WHERE id = :book_id
    ");
    $stmt->execute([':book_id' => $issue['book_id']]);

    $pdo->commit();

    flash('success', 'Book returned successfully.');
    header('Location: issued-books.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    flash('error', $e->getMessage());
    header('Location: issued-books.php');
    exit;
}
