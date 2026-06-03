<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header("Location: manage-books.php");
    exit;
}

/* FETCH BOOK */
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    header("Location: manage-books.php");
    exit;
}

/* FETCH CATEGORIES */
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")
    ->fetchAll(PDO::FETCH_ASSOC);

/* UPDATE BOOK */
if (isset($_POST['update_book'])) {
    verify_csrf();

    $name = trim($_POST['book_name']);
    $author = trim($_POST['author_name']);
    $isbn = trim($_POST['isbn']);
    $qty = (int) $_POST['quantity'];
    $cat = (int) $_POST['category_id'];

    if ($name === '' || $author === '' || !$cat) {
        flash('error', 'All fields are required.');
    } else {

        $imageName = $book['image'];

        /* IMAGE UPLOAD */
        if (!empty($_FILES['book_image']['name'])) {

            if ($_FILES['book_image']['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE  => 'Image is too large (Max size 2MB).',
                    UPLOAD_ERR_FORM_SIZE => 'Image is too large (Max size 2MB).',
                    UPLOAD_ERR_PARTIAL   => 'Image upload was incomplete. Please try again.',
                ];
                $msg = $uploadErrors[$_FILES['book_image']['error']] ?? 'Image upload failed. Please try again.';
                flash('error', $msg);
                header("Location: edit-book.php?id=" . $_GET['id']);
                exit;
            }

            if ($_FILES['book_image']['size'] > 2 * 1024 * 1024) {
                flash('error', 'Image must be under 2MB.');
                header("Location: edit-book.php?id=" . $_GET['id']);
                exit;
            }

            $allowedExt  = ['jpg', 'jpeg', 'png', 'webp'];
            $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
            $ext  = strtolower(pathinfo($_FILES['book_image']['name'], PATHINFO_EXTENSION));
            $mime = mime_content_type($_FILES['book_image']['tmp_name']);

            if (!in_array($ext, $allowedExt) || !in_array($mime, $allowedMime)) {
                flash('error', 'Only JPG, PNG, WEBP images allowed.');
                header("Location: edit-book.php?id=" . $_GET['id']);
                exit;
            }

            $imageName = uniqid('book_') . '.' . $ext;
            $path = "../uploads/books/" . $imageName;

            if (move_uploaded_file($_FILES['book_image']['tmp_name'], $path)) {
                // delete old image
                if ($book['image'] && file_exists("../uploads/books/" . $book['image'])) {
                    unlink("../uploads/books/" . $book['image']);
                }
            }
        }

        $sql = "
            UPDATE books SET
                book_name = :name,
                author_name = :author,
                category_id = :cat,
                isbn = :isbn,
                quantity = :qty,
                image = :img
            WHERE id = :id
        ";

        $pdo->prepare($sql)->execute([
            'name' => $name,
            'author' => $author,
            'cat' => $cat,
            'isbn' => $isbn,
            'qty' => $qty,
            'img' => $imageName,
            'id' => $id
        ]);

        flash('success', 'Book updated successfully.');
        header("Location: manage-books.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Edit Book | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <link rel="stylesheet" href="../assets/css/libook.css">
    <link rel="stylesheet" href="assets/css/book.css">
    <link rel="stylesheet" href="assets/css/adminui.css">
</head>

<body>
    <?php include 'includes/admin-header.php'; ?>
    <?php include '../includes/_toast.php'; ?>

    <main class="main">
        <div class="page-container">

            <h2 class="page-title">Edit Book</h2>

            <?php if ($err = flash('error')): ?>
                <div class="alert danger"><?= e($err) ?></div>
            <?php endif; ?>

            <form class="form-card" method="POST" enctype="multipart/form-data">

                <label>Book Name</label>
                <input type="text" name="book_name" value="<?= e($book['book_name']) ?>" required required
                    autocomplete="off" style="text-transform: capitalize;">

                <label>Author Name</label>
                <input type="text" name="author_name" value="<?= e($book['author_name']) ?>" required required
                    autocomplete="off" style="text-transform: capitalize;">

                <label>Category</label>
                <div class="custom-select">
                    <div class="select-trigger">
                        <?php
                        foreach ($categories as $c) {
                            if ($c['id'] == $book['category_id']) {
                                echo e($c['name']);
                            }
                        }
                        ?>
                    </div>
                    <ul class="select-options">
                        <?php foreach ($categories as $c): ?>
                            <li data-value="<?= $c['id'] ?>"><?= e($c['name']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <input type="hidden" name="category_id" value="<?= $book['category_id'] ?>" required>
                </div>

                <label>ISBN</label>
                <input type="text" name="isbn" value="<?= e($book['isbn']) ?>" required
                    style="text-transform: uppercase;">

                <label>Book Image</label>
                <div class="image-upload">
                    <input type="file" name="book_image" id="bookImage" accept="image/*">

                    <div class="image-preview" id="imagePreview">
                        <?php if ($book['image']): ?>
                            <img src="../ajax/book-cover.php?file=<?= e($book['image']) ?>">
                        <?php else: ?>
                            <span>No image selected</span>
                        <?php endif; ?>
                    </div>
                </div>

                <label>Quantity</label>
                <input type="number" name="quantity" min="1" value="<?= $book['quantity'] ?>" required>

                <div class="form-actions">
                    <button type="submit" name="update_book" class="alert success">
                        <i class="fa-solid fa-check"></i> Update Book
                    </button>

                    <a href="manage-books.php" class="alert info">
                        <i class="fa-solid fa-close"></i> Cancel
                    </a>
                </div>

                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            </form>

        </div>
    </main>

    <!-- IMAGE PREVIEW WITH COMPRESSION -->
    <script>
        const imageInput = document.getElementById("bookImage");
        const preview    = document.getElementById("imagePreview");

        function compressImage(file, maxWidth, quality, callback) {
            const reader = new FileReader();
            reader.onload = e => {
                const img = new Image();
                img.onload = () => {
                    let w = img.width, h = img.height;
                    if (w > maxWidth) { h = Math.round(h * maxWidth / w); w = maxWidth; }
                    const canvas = document.createElement('canvas');
                    canvas.width = w; canvas.height = h;
                    canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                    canvas.toBlob(blob => callback(blob), 'image/jpeg', quality);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        imageInput.addEventListener("change", () => {
            const file = imageInput.files[0];
            if (!file) return;

            compressImage(file, 800, 0.82, blob => {
                const compressed = new File([blob], 'cover.jpg', { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(compressed);
                imageInput.files = dt.files;

                const img = document.createElement("img");
                img.src = URL.createObjectURL(blob);
                preview.innerHTML = "";
                preview.appendChild(img);
            });
        });
    </script>

    <!-- YOUR EXISTING CUSTOM SELECT JS -->
    <script src="../assets/js/select.js"></script>
    <?php include '../includes/_footer.php'; ?>

</body>

</html>