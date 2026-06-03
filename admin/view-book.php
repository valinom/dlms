<?php
// 1. Include your setup
require_once __DIR__ . '/../includes/config.php';

// 2. Get the physical book ID from the URL
$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($book_id > 0) {
    // 3. Find the digital version associated with this physical book
    $stmt = $pdo->prepare("SELECT id FROM digital_books WHERE book_id = ? LIMIT 1");
    $stmt->execute([$book_id]);
    $digital_book = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($digital_book) {
        // 4. If found, redirect to the reader with the digital ID
        header("Location: reader.php?id=" . $digital_book['id']);
        exit;
    } else {
        // 5. If not found, handle the error (maybe redirect back with an alert)
        echo "Digital version not available for this book.";
    }
} else {
    echo "Invalid book ID.";
}
?>
