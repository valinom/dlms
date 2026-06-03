<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-student.php';

header('Content-Type: application/json');
verify_csrf();

$studentId = (int)$_SESSION['user_id'];
$digiId    = (int)($_POST['digi_id'] ?? 0);

if (!$digiId) { echo json_encode(['error' => 'Invalid book']); exit; }

$check = $pdo->prepare("SELECT id FROM digi_bookmarks WHERE student_id = ? AND digi_id = ?");
$check->execute([$studentId, $digiId]);

if ($check->fetch()) {
    $pdo->prepare("DELETE FROM digi_bookmarks WHERE student_id = ? AND digi_id = ?")
        ->execute([$studentId, $digiId]);
    echo json_encode(['bookmarked' => false]);
} else {
    $pdo->prepare("INSERT INTO digi_bookmarks (student_id, digi_id) VALUES (?, ?)")
        ->execute([$studentId, $digiId]);
    echo json_encode(['bookmarked' => true]);
}
