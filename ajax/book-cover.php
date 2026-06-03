<?php
/* =============================================
   DLMS — Book Cover Image Proxy
   Serves images from /uploads/books/ securely
   ============================================= */

$file = basename($_GET['file'] ?? '');

if (!$file) {
    http_response_code(404);
    exit;
}

// uploads/ is at project root, two levels up from /ajax/
$path = dirname(__DIR__) . '/uploads/books/' . $file;

if (!file_exists($path) || !is_file($path)) {
    // Serve the placeholder PNG directly instead of redirecting
    $placeholder = dirname(__DIR__) . '/assets/img/no-book.png';
    if (file_exists($placeholder)) {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        readfile($placeholder);
    } else {
        http_response_code(404);
    }
    exit;
}

$mime    = mime_content_type($path);
$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

if (!in_array($mime, $allowed)) {
    http_response_code(403);
    exit;
}

header("Content-Type: $mime");
header('Content-Length: ' . filesize($path));
header('Cache-Control: public, max-age=86400');
readfile($path);
exit;
