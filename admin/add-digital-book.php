<?php
ob_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';

$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);

// Is this an XHR call?
function isXhr(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Send JSON redirect (XHR) or normal Location header
function xhrRedirect(string $url): void {
    if (isXhr()) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['redirect' => $url]);
        exit;
    }
    ob_end_clean();
    header("Location: $url");
    exit;
}

// Send JSON error (XHR) or flash + redirect
function xhrFail(string $msg, string $back = 'add-digital-book.php'): void {
    if (isXhr()) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['error' => $msg]);
        exit;
    }
    // Only persist old form values on a real (non-XHR) failure
    $_SESSION['old'] = $_POST;
    flash('error', $msg);
    ob_end_clean();
    header("Location: $back");
    exit;
}

if (isset($_POST['add_digital'])) {

    verify_csrf();

    $book_id = !empty($_POST['book_id']) ? (int) $_POST['book_id'] : null;
    $title = ucwords(strtolower(trim($_POST['title'])));
    $author = ucwords(strtolower(trim($_POST['author'])));
    $description = trim($_POST['description']);
    $category_id = (int) $_POST['category_id'];
    $language_id = (int) $_POST['language_id'];
    $admin_id = $_SESSION['admin_id'];

    if ($title === '' || empty($_FILES['document']['name'])) {
        xhrFail('Title and digital file are required.');
    }

    if ($category_id <= 0) {
        xhrFail('Category is required.');
    }

    if ($language_id <= 0) {
        xhrFail('Language is required.');
    }

    /* DOCUMENT VALIDATION */
    $doc = $_FILES['document'];

    if ($doc['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE  => 'File is too large (server limit exceeded).',
            UPLOAD_ERR_FORM_SIZE => 'File is too large.',
            UPLOAD_ERR_PARTIAL   => 'File upload was incomplete. Please try again.',
            UPLOAD_ERR_NO_FILE   => 'No document was uploaded.',
        ];
        $msg = $uploadErrors[$doc['error']] ?? 'Document upload failed. Please try again.';
        xhrFail($msg);
    }

    $docExt = strtolower(pathinfo($doc['name'], PATHINFO_EXTENSION));
    $docType = mime_content_type($doc['tmp_name']);
    $docSize = $doc['size'];

    $allowedExt = ['pdf', 'doc', 'docx'];
    $allowedMime = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    if (!in_array($docExt, $allowedExt) || !in_array($docType, $allowedMime)) {
        xhrFail('Only PDF, DOC, DOCX files allowed.');
    }

    if ($docSize > 20 * 1024 * 1024) {
        xhrFail('File must be under 20MB.');
    }

    $docName = uniqid('doc_') . '.' . $docExt;
    $docDir  = __DIR__ . '/../uploads/documents/';

    if (!is_dir($docDir) && !mkdir($docDir, 0755, true)) {
        xhrFail('Upload folder missing. Please run setup.php first.');
    }

    if (!move_uploaded_file($doc['tmp_name'], $docDir . $docName)) {
        xhrFail('Failed to save file. Check folder permissions.');
    }

    /* COVER IMAGE (optional) */
    $cover = null;

    if (!empty($_FILES['cover']['name'])) {

        $file = $_FILES['cover'];

        // Check PHP upload error FIRST (catches files exceeding upload_max_filesize)
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'Cover image is too large (server limit exceeded).',
                UPLOAD_ERR_FORM_SIZE  => 'Cover image is too large.',
                UPLOAD_ERR_PARTIAL    => 'Cover upload was incomplete. Please try again.',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            ];
            $msg = $uploadErrors[$file['error']] ?? 'Cover upload failed. Please try again.';
            xhrFail($msg);
        }

        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileType = mime_content_type($file['tmp_name']);

        $allowedImgExt  = ['jpg', 'jpeg', 'png', 'webp'];
        $allowedImgMime = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($fileExt, $allowedImgExt) || !in_array($fileType, $allowedImgMime)) {
            xhrFail('Only JPG, PNG, WEBP images allowed for cover.');
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            xhrFail('Cover image must be under 2MB.');
        }

        $booksDir = __DIR__ . '/../uploads/books/';
        if (!is_dir($booksDir) && !mkdir($booksDir, 0755, true)) {
            xhrFail('Upload folder missing. Please run setup.php first.');
        }

        $cover = uniqid('cover_') . '.' . $fileExt;
        move_uploaded_file($file['tmp_name'], $booksDir . $cover);
    }

    /* INSERT */
    $stmt = $pdo->prepare("
        INSERT INTO digital_books
        (book_id, title, author, description, category_id, language_id,
         file_name, file_type, file_size, cover_image, uploaded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $book_id ?: null,
        $title,
        $author,
        $description,
        $category_id,
        $language_id,
        $docName,
        $docExt,
        $docSize,
        $cover,
        $admin_id
    ]);

    unset($_SESSION['old']);

    flash('success', 'Digital book uploaded successfully.');
    xhrRedirect("add-digital-book.php");
}

/* Categories */
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Add Digital Book | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="<?= csrf_token() ?>">

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

    <main class="main">
        <div class="page-container">
            <h2 class="page-title">Add Digital Book</h2>

            <?php include('../includes/_toast.php'); ?>

            <form class="form-card" method="POST" enctype="multipart/form-data">

                <label>Link Physical Book (optional)</label>

                <div class="search-box">
                    <input type="text" id="bookSearch" placeholder="Search physical book" autocomplete="off">
                    <input type="hidden" name="book_id" id="book_id">

                    <ul class="search-results" id="bookResults"></ul>
                </div>

                <label>Title</label>
                <input type="text" name="title" id="title" value="<?= e($old['title'] ?? '') ?>" required>

                <label>Author</label>
                <input type="text" name="author" id="author" value="<?= e($old['author'] ?? '') ?>" required>

                <label>Description</label>
                <textarea name="description" rows="3"></textarea>

                <label>Category</label>

                <div class="custom-select">
                    <div class="select-trigger">-- Select Category --</div>

                    <ul class="select-options">
                        <?php foreach ($categories as $cat): ?>
                            <li data-value="<?= $cat['id'] ?>">
                                <?= e($cat['name']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>


                    <input type="hidden" name="category_id" required value="<?= e($old['category_id'] ?? '') ?>">
                </div>

                <label>Language</label>

                <div class="custom-select">
                    <div class="select-trigger">-- Select Language --</div>
                    <ul class="select-options">
                        <?php
                        $langs = $pdo->query("SELECT id, name FROM languages ORDER BY name")->fetchAll();
                        foreach ($langs as $l): ?>
                            <li data-value="<?= $l['id'] ?>">
                                <?= e($l['name']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <input type="hidden" name="language_id" required value="<?= e($old['language_id'] ?? '') ?>">
                </div>

                <!-- Files -->
                <label>Digital File (PDF / DOC) * <span
                style="font-weight:400;color:var(--text-muted);font-size:.78rem">max 20MB</span></label>
                <input type="file" name="document" id="docInput" required>

                <label>Cover Image <span style="font-weight:400;color:var(--text-muted)">(optional)</span></label>
                <input type="file" name="cover" id="coverInput" accept="image/jpeg,image/png,image/webp">

                <!-- Upload progress bar (hidden until upload starts) -->
                <div id="uploadProgress" style="display:none;margin:0.8rem 0;">
                    <div style="display:flex;justify-content:space-between;font-size:0.82rem;color:var(--text-muted);margin-bottom:0.4rem;">
                        <span id="uploadLabel">Uploading...</span>
                        <span id="uploadPct">0%</span>
                    </div>
                    <div style="background:var(--border-soft);border-radius:99px;height:10px;overflow:hidden;">
                        <div id="uploadBar" style="height:100%;width:0%;background:var(--accent);border-radius:99px;transition:width 0.2s ease;"></div>
                    </div>
                    <p id="uploadStatus" style="font-size:0.8rem;color:var(--text-muted);margin:0.4rem 0 0;text-align:center;"></p>
                </div>

                <button type="button" id="submitBtn" onclick="startUpload()" class="alert success">
                    <i class="fa-solid fa-upload"></i> Upload Digital Book
                </button>

                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            </form>
        </div>
    </main>

    <script src="../assets/js/dlms-dialogs.js"></script>
    <script src="../assets/js/select.js"></script>

    <script>
        const searchInput = document.getElementById('bookSearch');
        const resultsBox = document.getElementById('bookResults');
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
                    .then(renderResults);
            }, 300);
        });

        function renderResults(data) {
            if (!data.length) {
                resultsBox.innerHTML = '<div class="search-item"><div class="muted">No books found</div></div>';
                return;
            }

            resultsBox.innerHTML = data.map(b => `
        <div class="search-item" onclick="selectBook(${b.id}, '${escapeHtml(b.book_name)}')">
            <span>${b.book_name}</span>
            <div class="muted">${b.isbn}</div>
        </div>
    `).join('');
        }

        function selectBook(id, name) {
            hiddenBookId.value = id;
            searchInput.value = name;
            resultsBox.innerHTML = '';
        }

        function escapeHtml(str) {
            return str.replace(/[&<>"']/g, m =>
                ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m])
            );
        }

        // ── XHR upload with progress bar ──
        function startUpload() {
            const form     = document.querySelector('.form-card');
            const btn      = document.getElementById('submitBtn');
            const bar      = document.getElementById('uploadBar');
            const pct      = document.getElementById('uploadPct');
            const label    = document.getElementById('uploadLabel');
            const status   = document.getElementById('uploadStatus');
            const progress = document.getElementById('uploadProgress');

            // Client-side validation — mirrors PHP checks
            const title   = form.querySelector('[name="title"]').value.trim();
            const catEl   = form.querySelector('[name="category_id"]');
            const langEl  = form.querySelector('[name="language_id"]');
            const docFile = document.getElementById('docInput').files[0];

            const author = form.querySelector('[name="author"]').value.trim();

            if (!title) {
                showAlert('Title is required.');
                form.querySelector('[name="title"]').focus();
                return;
            }
            if (!author) {
                showAlert('Author is required.');
                form.querySelector('[name="author"]').focus();
                return;
            }
            if (!catEl || !catEl.value) {
                showAlert('Category is required.');
                return;
            }
            if (!langEl || !langEl.value) {
                showAlert('Language is required.');
                return;
            }
            if (!docFile) {
                showAlert('Please select a document file (PDF/DOC).');
                document.getElementById('docInput').focus();
                return;
            }
            // Client-side size guard (20MB)
            if (docFile.size > 20 * 1024 * 1024) {
                showAlert('File is too large. Maximum allowed size is 20MB.');
                return;
            }

            // Build FormData manually to ensure all fields are captured
            const formData = new FormData();

            // Text fields
            formData.append('add_digital', '1');
            formData.append('csrf_token', form.querySelector('[name="csrf_token"]').value);
            formData.append('title',       form.querySelector('[name="title"]').value);
            formData.append('author',      form.querySelector('[name="author"]').value);
            formData.append('description', form.querySelector('[name="description"]').value);
            formData.append('category_id', form.querySelector('[name="category_id"]').value);
            formData.append('language_id', form.querySelector('[name="language_id"]').value);

            // Optional book link
            const bookId = form.querySelector('[name="book_id"]');
            if (bookId) formData.append('book_id', bookId.value);

            // Document file (no compression for PDFs/docs)
            formData.append('document', docFile);

            // Cover image — compress before appending
            const coverFile = document.getElementById('coverInput').files[0];

            function sendXhr(fd) {
                const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', e => {
                if (!e.lengthComputable) return;
                const percent = Math.round((e.loaded / e.total) * 100);
                bar.style.width   = percent + '%';
                pct.textContent   = percent + '%';

                // Show file size info
                const loaded = (e.loaded / 1048576).toFixed(1);
                const total  = (e.total  / 1048576).toFixed(1);
                label.textContent = `Uploading ${loaded} MB of ${total} MB`;

                if (percent === 100) {
                    label.textContent = 'Processing...';
                    status.textContent = 'Upload complete. Please wait...';
                    bar.style.background = 'hsl(145, 55%, 45%)';
                }
            });

            xhr.addEventListener('load', () => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-upload"></i> Upload Digital Book';

                // HTTP error from server (413, 500, etc.)
                if (xhr.status === 413) {
                    progress.style.display = 'none';
                    showAlert('File too large. The server rejected the upload. Try a smaller file.', 'warning');
                    return;
                }
                if (xhr.status >= 500) {
                    progress.style.display = 'none';
                    showAlert('Server error (' + xhr.status + '). The file may be too large for this host.', 'warning');
                    return;
                }

                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.redirect) {
                        window.location.href = res.redirect;
                        return;
                    }
                    if (res.error) {
                        progress.style.display = 'none';
                        showAlert(res.error, 'warning');
                        return;
                    }
                } catch (e) {
                    progress.style.display = 'none';
                    // Show the actual raw response so we know what went wrong
                    const raw = xhr.responseText ? xhr.responseText.substring(0, 300) : '(empty response)';
                    showAlert('Server response error. Raw: ' + raw, 'warning');
                    return;
                }
                window.location.reload();
            });

            xhr.addEventListener('error', () => {
                progress.style.display = 'none';
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-upload"></i> Upload Digital Book';
                showAlert('Upload failed. This usually means the file is too large for the server, or your connection dropped.', 'warning');
            });

            xhr.addEventListener('abort', () => {
                progress.style.display = 'none';
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-upload"></i> Upload Digital Book';
                showAlert('Upload was cancelled.', 'warning');
            });

            xhr.open('POST', 'add-digital-book.php');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(fd);

            // Show progress, disable button
            progress.style.display = 'block';
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';
            } // end sendXhr

            // Compress cover if present, then send
            if (coverFile) {
                compressImage(coverFile, 800, 0.82, blob => {
                    formData.append('cover', new File([blob], 'cover.jpg', { type: 'image/jpeg' }));
                    sendXhr(formData);
                });
            } else {
                sendXhr(formData);
            }
        }

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

    </script>
    <?php include '../includes/_footer.php'; ?>
</body>

</html>