<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-student.php';

header('Content-Type: application/json');
verify_csrf();

$studentId = $_SESSION['user_id'];
$bookId = (int)($_POST['book_id'] ?? 0);

if (!$bookId) {
    echo json_encode(['error' => 'Invalid book']);
    exit;
}

/* Check if already favorite */
$check = $pdo->prepare("
    SELECT id FROM favorites
    WHERE student_id = :sid AND book_id = :bid
");
$check->execute([
    ':sid' => $studentId,
    ':bid' => $bookId
]);

if ($check->fetch()) {
    /* Remove favorite */
    $pdo->prepare("
        DELETE FROM favorites
        WHERE student_id = :sid AND book_id = :bid
    ")->execute([
        ':sid' => $studentId,
        ':bid' => $bookId
    ]);

    echo json_encode(['favorited' => false]);
} else {
    /* Add favorite */
    $pdo->prepare("
        INSERT INTO favorites (student_id, book_id)
        VALUES (:sid, :bid)
    ")->execute([
        ':sid' => $studentId,
        ':bid' => $bookId
    ]);

    echo json_encode(['favorited' => true]);
}