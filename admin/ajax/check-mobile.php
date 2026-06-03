<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth-admin.php';

header('Content-Type: application/json');

$mobile    = preg_replace('/[^0-9]/', '', trim($_GET['mobile'] ?? ''));
$excludeId = (int)($_GET['exclude_id'] ?? 0);

if ($mobile === '') {
    echo json_encode(['exists' => false]);
    exit;
}

$sql    = $excludeId
    ? "SELECT id FROM students WHERE mobile = ? AND id != ? LIMIT 1"
    : "SELECT id FROM students WHERE mobile = ? LIMIT 1";
$params = $excludeId ? [$mobile, $excludeId] : [$mobile];

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode(['exists' => (bool)$stmt->fetch()]);
