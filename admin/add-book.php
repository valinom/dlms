<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';

/* ================= FETCH CATEGORIES ================= */
$catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= ADD BOOK ================= */
if (isset($_POST['add_book'])) {

    verify_csrf();

    $bookName = ucwords(strtolower(trim($_POST['book_name'] ?? '')));
    $authorName = trim($_POST['author_name'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $isbn = strtoupper(trim($_POST['isbn'] ?? ''));
    $quantity = (int) ($_POST['quantity'] ?? 0);

    /* ===== VALIDATION ===== */
    if ($bookName === '' || $authorName === '' || $isbn === '' || $quantity < 1) {
        flash('error', 'All fields are required.');
        header("Location: add-book.php");
        exit;
    }

    if ($categoryId <= 0) {
        flash('error', 'Category is required.');
        header("Location: add-book.php");
        exit;
    }

    /* ===== CHECK DUPLICATE ISBN ===== */
    $check = $pdo->prepare("SELECT id FROM books WHERE isbn = ?");
    $check->execute([$isbn]);

    if ($check->rowCount() > 0) {
        flash('error', 'Book with this ISBN already exists.');
        header("Location: add-book.php");
        exit;
    }

    /* ===== IMAGE UPLOAD ===== */
    $imageName = null;

    if (!empty($_FILES['book_image']['name'])) {

        // Check PHP upload error FIRST (catches files exceeding upload_max_filesize)
        if ($_FILES['book_image']['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'Image is too large (server limit exceeded).',
                UPLOAD_ERR_FORM_SIZE  => 'Image is too large.',
                UPLOAD_ERR_PARTIAL    => 'Image upload was incomplete. Please try again.',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            ];
            $msg = $uploadErrors[$_FILES['book_image']['error']] ?? 'Image upload failed. Please try again.';
            flash('error', $msg);
            header("Location: add-book.php");
            exit;
        }

        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
        $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];

        $ext = strtolower(pathinfo($_FILES['book_image']['name'], PATHINFO_EXTENSION));
        $mime = mime_content_type($_FILES['book_image']['tmp_name']);

        if (!in_array($ext, $allowedExt) || !in_array($mime, $allowedMime)) {
            flash('error', 'Only JPG, PNG, WEBP images allowed.');
            header("Location: add-book.php");
            exit;
        }

        if ($_FILES['book_image']['size'] > 2 * 1024 * 1024) {
            flash('error', 'Image must be under 2MB.');
            header("Location: add-book.php");
            exit;
        }

        $imageName  = uniqid('book_', true) . '.' . $ext;
        $booksDir   = __DIR__ . '/../uploads/books/';

        if (!is_dir($booksDir) && !mkdir($booksDir, 0755, true)) {
            flash('error', 'Upload folder missing. Please run setup.php first.');
            header("Location: add-book.php");
            exit;
        }

        if (!move_uploaded_file($_FILES['book_image']['tmp_name'], $booksDir . $imageName)) {
            flash('error', 'Image upload failed. Check folder permissions.');
            header("Location: add-book.php");
            exit;
        }
    }

    /* ===== INSERT BOOK ===== */
    $insert = $pdo->prepare("
        INSERT INTO books
        (book_name, author_name, category_id, isbn, image, quantity)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $insert->execute([
        $bookName,
        $authorName,
        $categoryId,
        $isbn,
        $imageName,
        $quantity
    ]);

    flash('success', 'Book added successfully.');
    header("Location: add-book.php");
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Add Book | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <link rel="stylesheet" href="assets/css/book.css">
    <link rel="stylesheet" href="assets/css/adminui.css">

</head>

<body>

    <?php include('includes/admin-header.php'); ?>

    <main class="main">
        <div class="page-container">

            <h2 class="page-title">Add Book</h2>

            <?php include('../includes/_toast.php'); ?>

            <form class="form-card" method="POST" enctype="multipart/form-data">

                <label>Book Name</label>
                <input type="text" name="book_name" required autocomplete="off" style="text-transform: capitalize;">

                <label>Author Name</label>
                <input type="text" name="author_name" required autocomplete="off">

                <label>Category</label>

                <div class="custom-select" data-name="category_id">
                    <div class="select-trigger">-- Select Category --</div>

                    <ul class="select-options">
                        <?php foreach ($categories as $cat): ?>
                            <li data-value="<?= $cat['id'] ?>">
                                <?= e($cat['name']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>


                    <input type="hidden" name="category_id" id="category_id" required>
                </div>


                <label>ISBN</label>
                <input type="text" name="isbn" required style="text-transform: uppercase;" autocomplete="off">

                <label>Book Image</label>

                <div class="image-upload">
                    <input type="file" name="book_image" id="bookImage" accept="image/*">

                    <div class="image-preview" id="imagePreview">
                        <span>No image selected</span>
                    </div>
                </div>


                <label>Quantity</label>
                <input type="number" name="quantity" min="1" required>

                <button type="button" onclick="validateAndSubmit()" id="addBookBtn">
                    <i class="fa-solid fa-plus"></i> Add Book
                </button>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            </form>
        </div>
    </main>

    <script src="../assets/js/dlms-dialogs.js"></script>
    <script src="../assets/js/select.js"></script>

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

        function validateAndSubmit() {
            const form     = document.querySelector('.form-card');
            const bookName = form.querySelector('[name="book_name"]').value.trim();
            const author   = form.querySelector('[name="author_name"]').value.trim();
            const category = form.querySelector('[name="category_id"]').value;
            const isbn     = form.querySelector('[name="isbn"]').value.trim();
            const quantity = form.querySelector('[name="quantity"]').value;

            if (!bookName) {
                showAlert('Book name is required.');
                form.querySelector('[name="book_name"]');
                return;
            }
            if (!author) {
                showAlert('Author name is required.');
                form.querySelector('[name="author_name"]');
                return;
            }
            if (!category) {
                showAlert('Please select a category.');
                return;
            }
            if (!isbn) {
                showAlert('ISBN is required.');
                form.querySelector('[name="isbn"]');
                return;
            }
            if (!quantity || quantity < 1) {
                showAlert('Quantity must be at least 1.');
                form.querySelector('[name="quantity"]');
                return;
            }

            // Add hidden submit trigger and submit
            const hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = 'add_book';
            hidden.value = '1';
            form.appendChild(hidden);
            form.submit();
        }
    </script>
    <?php include '../includes/_footer.php'; ?>
</body>

</html>