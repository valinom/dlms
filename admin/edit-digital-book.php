<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: manage-digital-books.php');
    exit;
}

/* Fetch book */
$stmt = $pdo->prepare("SELECT * FROM digital_books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    header('Location: manage-digital-books.php');
    exit;
}

/* Fetch supporting data */
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$languages  = $pdo->query("SELECT id, name FROM languages ORDER BY name")->fetchAll();

/* Fetch linked physical book name if any */
$linkedBookName = '';
if ($book['book_id']) {
    $s = $pdo->prepare("SELECT book_name FROM books WHERE id = ?");
    $s->execute([$book['book_id']]);
    $lb = $s->fetch();
    if ($lb) $linkedBookName = $lb['book_name'];
}

/* ─── POST HANDLER ─────────────────────────────────────── */
if (isset($_POST['update_digital'])) {
    verify_csrf();

    $book_id     = !empty($_POST['book_id']) ? (int)$_POST['book_id'] : null;
    $title       = ucwords(strtolower(trim($_POST['title'] ?? '')));
    $author      = ucwords(strtolower(trim($_POST['author'] ?? '')));
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $language_id = (int)($_POST['language_id'] ?? 0);
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '') {
        flash('error', 'Title is required.');
        header("Location: edit-digital-book.php?id=$id");
        exit;
    }
    if ($category_id <= 0) {
        flash('error', 'Category is required.');
        header("Location: edit-digital-book.php?id=$id");
        exit;
    }
    if ($language_id <= 0) {
        flash('error', 'Language is required.');
        header("Location: edit-digital-book.php?id=$id");
        exit;
    }

    $coverName = $book['cover_image'];
    $fileName  = $book['file_name'];
    $fileType  = $book['file_type'];
    $fileSize  = $book['file_size'];

    /* ── New cover image ── */
    if (!empty($_FILES['cover']['name'])) {
        $file = $_FILES['cover'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE  => 'Cover image is too large (server limit).',
                UPLOAD_ERR_FORM_SIZE => 'Cover image is too large.',
                UPLOAD_ERR_PARTIAL   => 'Cover upload was incomplete.',
            ];
            flash('error', $uploadErrors[$file['error']] ?? 'Cover upload failed.');
            header("Location: edit-digital-book.php?id=$id");
            exit;
        }

        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = mime_content_type($file['tmp_name']);

        if (!in_array($ext, ['jpg','jpeg','png','webp']) || !in_array($mime, ['image/jpeg','image/png','image/webp'])) {
            flash('error', 'Only JPG, PNG, WEBP images allowed for cover.');
            header("Location: edit-digital-book.php?id=$id");
            exit;
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            flash('error', 'Cover image must be under 2MB.');
            header("Location: edit-digital-book.php?id=$id");
            exit;
        }

        $newCover = uniqid('cover_') . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], "../uploads/books/$newCover")) {
            /* Delete old cover */
            if ($coverName && file_exists("../uploads/books/$coverName")) {
                unlink("../uploads/books/$coverName");
            }
            $coverName = $newCover;
        }
    }

    /* ── New document file ── */
    if (!empty($_FILES['document']['name'])) {
        $doc = $_FILES['document'];

        if ($doc['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE  => 'File is too large (server limit).',
                UPLOAD_ERR_FORM_SIZE => 'File is too large.',
                UPLOAD_ERR_PARTIAL   => 'File upload was incomplete.',
            ];
            flash('error', $uploadErrors[$doc['error']] ?? 'Document upload failed.');
            header("Location: edit-digital-book.php?id=$id");
            exit;
        }

        $docExt  = strtolower(pathinfo($doc['name'], PATHINFO_EXTENSION));
        $docMime = mime_content_type($doc['tmp_name']);

        $allowedExt  = ['pdf', 'doc', 'docx'];
        $allowedMime = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!in_array($docExt, $allowedExt) || !in_array($docMime, $allowedMime)) {
            flash('error', 'Only PDF, DOC, DOCX files allowed.');
            header("Location: edit-digital-book.php?id=$id");
            exit;
        }
        if ($doc['size'] > 50 * 1024 * 1024) {
            flash('error', 'File must be under 50MB.');
            header("Location: edit-digital-book.php?id=$id");
            exit;
        }

        $newDoc = uniqid('doc_') . '.' . $docExt;
        if (move_uploaded_file($doc['tmp_name'], "../uploads/documents/$newDoc")) {
            /* Delete old document */
            if ($fileName && file_exists("../uploads/documents/$fileName")) {
                unlink("../uploads/documents/$fileName");
            }
            $fileName = $newDoc;
            $fileType = $docExt;
            $fileSize = $doc['size'];
        }
    }

    /* ── UPDATE ── */
    $pdo->prepare("
        UPDATE digital_books SET
            book_id     = ?,
            title       = ?,
            author      = ?,
            description = ?,
            category_id = ?,
            language_id = ?,
            file_name   = ?,
            file_type   = ?,
            file_size   = ?,
            cover_image = ?,
            is_active   = ?
        WHERE id = ?
    ")->execute([
        $book_id ?: null,
        $title,
        $author,
        $description,
        $category_id,
        $language_id,
        $fileName,
        $fileType,
        $fileSize,
        $coverName,
        $is_active,
        $id
    ]);

    flash('success', 'Digital book updated successfully.');
    header('Location: manage-digital-books.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Edit Digital Book | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <link rel="stylesheet" href="assets/css/adminui.css">
    <link rel="stylesheet" href="assets/css/book.css">
</head>

<body>
    <?php include 'includes/admin-header.php'; ?>
    <?php include '../includes/_toast.php'; ?>

    <main class="main">
        <div class="page-container">

            <h2 class="page-title">Edit Digital Book</h2>

            <?php if ($err = flash('error')): ?>
                <div class="alert danger"><?= e($err) ?></div>
            <?php endif; ?>

            <form class="form-card" method="POST" enctype="multipart/form-data">

                <!-- Link Physical Book -->
                <label>Link Physical Book <span style="font-weight:400;color:var(--text-muted)">(optional)</span></label>
                <div class="search-box">
                    <input type="text" id="bookSearch"
                        value="<?= e($linkedBookName) ?>"
                        placeholder="Search physical book"
                        autocomplete="off">
                    <input type="hidden" name="book_id" id="book_id" value="<?= e($book['book_id'] ?? '') ?>">
                    <ul class="search-results" id="bookResults"></ul>
                </div>

                <!-- Title -->
                <label>Title *</label>
                <input type="text" name="title" value="<?= e($book['title']) ?>" required autocomplete="off">

                <!-- Author -->
                <label>Author</label>
                <input type="text" name="author" value="<?= e($book['author'] ?? '') ?>" autocomplete="off">

                <!-- Description -->
                <label>Description</label>
                <textarea name="description" rows="3"><?= e($book['description'] ?? '') ?></textarea>

                <!-- Category -->
                <label>Category *</label>
                <div class="custom-select">
                    <div class="select-trigger">
                        <?php
                        $catLabel = '-- Select Category --';
                        foreach ($categories as $c) {
                            if ($c['id'] == $book['category_id']) { $catLabel = $c['name']; break; }
                        }
                        echo e($catLabel);
                        ?>
                    </div>
                    <ul class="select-options">
                        <?php foreach ($categories as $c): ?>
                            <li data-value="<?= $c['id'] ?>"><?= e($c['name']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <input type="hidden" name="category_id" value="<?= e($book['category_id']) ?>" required>
                </div>

                <!-- Language -->
                <label>Language *</label>
                <div class="custom-select">
                    <div class="select-trigger">
                        <?php
                        $langLabel = '-- Select Language --';
                        foreach ($languages as $l) {
                            if ($l['id'] == $book['language_id']) { $langLabel = $l['name']; break; }
                        }
                        echo e($langLabel);
                        ?>
                    </div>
                    <ul class="select-options">
                        <?php foreach ($languages as $l): ?>
                            <li data-value="<?= $l['id'] ?>"><?= e($l['name']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <input type="hidden" name="language_id" value="<?= e($book['language_id']) ?>" required>
                </div>

                <!-- Cover Image -->
                <label>Cover Image <span style="font-weight:400;color:var(--text-muted)">(leave blank to keep current)</span></label>
                <div class="image-upload">
                    <input type="file" name="cover" id="coverInput" accept="image/jpeg,image/png,image/webp">
                    <div class="image-preview" id="imagePreview">
                        <?php if ($book['cover_image']): ?>
                            <img src="../ajax/book-cover.php?file=<?= e($book['cover_image']) ?>" alt="Cover">
                        <?php else: ?>
                            <span>No cover image</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Replace Document -->
                <label>Replace Document <span style="font-weight:400;color:var(--text-muted)">(leave blank to keep current)</span></label>
                <div style="background:var(--bg-card);border:1px solid var(--border-soft);border-radius:8px;padding:0.75rem 1rem;margin-bottom:0.5rem;font-size:0.88rem;color:var(--text-muted);">
                    <i class="fa-solid fa-file"></i>
                    Current: <strong><?= e(strtoupper($book['file_type'])) ?></strong>
                    &nbsp;·&nbsp;
                    <?php
                    $bytes = (int)$book['file_size'];
                    echo $bytes >= 1048576
                        ? round($bytes/1048576, 1) . ' MB'
                        : round($bytes/1024, 1) . ' KB';
                    ?>
                </div>
                <input type="file" name="document" id="docInput" accept=".pdf,.doc,.docx">

                <!-- Status -->
                <label>Status</label>
                <label style="display:flex;align-items:center;gap:0.6rem;cursor:pointer;font-weight:400;">
                    <input type="checkbox" name="is_active" value="1"
                        <?= $book['is_active'] ? 'checked' : '' ?>
                        style="width:1.1rem;height:1.1rem;cursor:pointer;">
                    Active (visible to students)
                </label>

                <!-- Actions -->
                <div class="form-actions">
                    <button type="submit" name="update_digital" class="alert success">
                        <i class="fa-solid fa-check"></i> Update
                    </button>
                    <a href="manage-digital-books.php" class="alert info">
                        <i class="fa-solid fa-close"></i> Cancel
                    </a>
                </div>

                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            </form>

        </div>
    </main>

    <script src="../assets/js/select.js"></script>
    <script src="../assets/js/dlms-dialogs.js"></script>

    <script>
        /* Cover image preview with compression */
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

        document.getElementById('coverInput').addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;

            compressImage(file, 800, 0.82, blob => {
                const compressed = new File([blob], 'cover.jpg', { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(compressed);
                this.files = dt.files;

                const preview = document.getElementById('imagePreview');
                const img = document.createElement('img');
                img.src = URL.createObjectURL(blob);
                preview.innerHTML = '';
                preview.appendChild(img);
            });
        });

        /* Physical book search */
        const searchInput  = document.getElementById('bookSearch');
        const resultsBox   = document.getElementById('bookResults');
        const hiddenBookId = document.getElementById('book_id');
        let timer = null;

        searchInput.addEventListener('input', () => {
            const q = searchInput.value.trim();
            clearTimeout(timer);
            if (q === '') {
                resultsBox.innerHTML = '';
                hiddenBookId.value = '';
                return;
            }
            timer = setTimeout(() => {
                fetch(`ajax/search-books.php?q=${encodeURIComponent(q)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (!data.length) {
                            resultsBox.innerHTML = '<div class="search-item"><span class="muted">No books found</span></div>';
                            return;
                        }
                        resultsBox.innerHTML = data.map(b => `
                            <div class="search-item" onclick="selectBook(${b.id}, '${escHtml(b.book_name)}')">
                                <span>${b.book_name}</span>
                                <div class="muted">${b.isbn}</div>
                            </div>`).join('');
                    });
            }, 300);
        });

        function selectBook(id, name) {
            hiddenBookId.value = id;
            searchInput.value  = name;
            resultsBox.innerHTML = '';
        }

        function escHtml(str) {
            return String(str).replace(/[&<>"']/g, m =>
                ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])
            );
        }
    </script>
    <?php include '../includes/_footer.php'; ?>

</body>

</html>
