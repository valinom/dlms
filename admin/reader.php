<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';

$bookId = (int)($_GET['id'] ?? 0);
if (!$bookId) redirect('manage-digital-books.php');

$stmt = $pdo->prepare("
    SELECT
        db.id,
        db.title,
        db.author,
        db.description,
        db.file_name,
        db.file_type,
        db.file_size,
        db.uploaded_at,
        db.is_active,
        c.name  AS category,
        l.name  AS language,
        a.fullname AS uploaded_by
    FROM digital_books db
    LEFT JOIN categories c ON c.id = db.category_id
    LEFT JOIN languages  l ON l.id = db.language_id
    LEFT JOIN admin      a ON a.id = db.uploaded_by
    WHERE db.id = ?
    LIMIT 1
");
$stmt->execute([$bookId]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$book) redirect('manage-digital-books.php');

function fmtSize(int $b): string {
    if ($b >= 1048576) return round($b / 1048576, 1) . ' MB';
    if ($b >= 1024)    return round($b / 1024, 1) . ' KB';
    return $b . ' B';
}

$appBase = rtrim(dirname(dirname(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))), '/');
$pdfUrl  = $appBase . '/uploads/documents/' . basename($book['file_name']);
$title   = e($book['title']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> — DLMS Admin</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: var(--bg-main);
            color: var(--text-main);
            height: 100dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .topbar {
            height: 52px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0 1.25rem;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-soft);
            flex-shrink: 0;
            z-index: 50;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.8rem;
            border: 1px solid var(--border-soft);
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            background: none;
            text-decoration: none;
            transition: all 0.15s;
            flex-shrink: 0;
        }
        .btn-back:hover { background: var(--bg-hover); color: var(--text-main); border-color: var(--accent); }

        .topbar-name { flex: 1; font-size: 0.9rem; font-weight: 700; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }

        .status-pill {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 0.28rem 0.65rem;
            border-radius: 20px;
        }
        .status-pill.active   { background: hsl(145,55%,90%); color: hsl(145,50%,28%); border: 1px solid hsl(145,40%,70%); }
        .status-pill.inactive { background: hsl(0,55%,92%);   color: hsl(0,50%,35%);   border: 1px solid hsl(0,40%,72%); }

        .reader-body { flex: 1; display: grid; grid-template-columns: 280px 1fr; overflow: hidden; }

        .sidebar {
            display: flex;
            flex-direction: column;
            background: var(--bg-card);
            border-right: 1px solid var(--border-soft);
            overflow-y: auto;
        }

        .sb-hero {
            background: linear-gradient(150deg, var(--accent) 0%, hsl(225,45%,38%) 100%);
            padding: 1.75rem 1.25rem 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.9rem;
            flex-shrink: 0;
        }

        .sb-book-icon {
            position: relative;
            width: 72px; height: 92px;
            background: rgba(255,255,255,0.12);
            border: 2px solid rgba(255,255,255,0.28);
            border-radius: 5px 8px 8px 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #fff;
            box-shadow: 4px 4px 16px rgba(0,0,0,0.25);
        }
        .sb-book-icon::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 8px; background: rgba(0,0,0,0.18); border-radius: 5px 0 0 5px; }
        .sb-book-icon::after  { content: ''; position: absolute; top: 6px; right: -5px; width: 100%; height: 100%; background: rgba(255,255,255,0.06); border: 2px solid rgba(255,255,255,0.14); border-radius: 5px 8px 8px 5px; z-index: -1; }

        .sb-title  { font-size: 0.88rem; font-weight: 700; color: #fff; text-align: center; line-height: 1.45; }
        .sb-author { font-size: 0.74rem; color: rgba(255,255,255,0.7); text-align: center; }

        .sb-info { padding: 0.5rem 1.1rem 0.75rem; }

        .sb-row { display: flex; align-items: flex-start; gap: 0.7rem; padding: 0.7rem 0; border-bottom: 1px solid var(--border-soft); }
        .sb-row:last-child { border-bottom: none; }

        .sb-row-icon { width: 28px; height: 28px; border-radius: 7px; background: var(--bg-hover); display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: var(--accent); flex-shrink: 0; margin-top: 2px; }
        .sb-row-label { font-size: 0.64rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--text-muted); margin-bottom: 0.18rem; }
        .sb-row-val   { font-size: 0.8rem; font-weight: 500; color: var(--text-main); line-height: 1.4; }

        .sb-desc { margin: 0 1.1rem 1.1rem; padding: 0.9rem 1rem; background: var(--bg-hover); border: 1px solid var(--border-soft); border-radius: 10px; }
        .sb-desc-label { font-size: 0.64rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--text-muted); margin-bottom: 0.45rem; }
        .sb-desc-text  { font-size: 0.79rem; color: var(--text-main); line-height: 1.7; }

        .viewer { display: flex; flex-direction: column; background: #404040; overflow: hidden; }

        .viewer-chrome { height: 38px; background: #252525; border-bottom: 1px solid #111; display: flex; align-items: center; padding: 0 1rem; gap: 0.6rem; flex-shrink: 0; }
        .chrome-dot { width: 11px; height: 11px; border-radius: 50%; }
        .chrome-dot.red    { background: #ff5f56; }
        .chrome-dot.yellow { background: #ffbd2e; }
        .chrome-dot.green  { background: #27c93f; }
        .chrome-url { flex: 1; height: 22px; background: #333; border-radius: 5px; display: flex; align-items: center; padding: 0 0.6rem; gap: 0.4rem; font-size: 0.7rem; color: #888; overflow: hidden; white-space: nowrap; }
        .chrome-url i { color: #e74c3c; font-size: 0.65rem; flex-shrink: 0; }

        .viewer-frame-wrap { flex: 1; position: relative; overflow: hidden; }

        iframe.pdf-frame { position: absolute; inset: 0; width: 100%; height: 100%; border: none; display: block; }


        .pdf-loading { position: absolute; inset: 0; z-index: 30; background: #404040; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem; color: #ccc; transition: opacity 0.35s; }
        .pdf-loading.done { opacity: 0; pointer-events: none; }

        .spin-ring { width: 42px; height: 42px; border-radius: 50%; border: 3px solid rgba(255,255,255,0.08); border-top-color: var(--accent); animation: spin 0.75s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .pdf-loading span { font-size: 0.82rem; font-weight: 500; }

        @media (max-width: 780px) {
            body { overflow: auto; height: auto; }
            .reader-body { grid-template-columns: 1fr; grid-template-rows: auto 1fr; overflow: visible; }
            .sidebar { border-right: none; border-bottom: 1px solid var(--border-soft); overflow: visible; }
            .sb-hero { flex-direction: row; align-items: center; padding: 1.25rem; gap: 1rem; }
            .sb-book-icon { width: 52px; height: 66px; font-size: 1.4rem; flex-shrink: 0; }
            .sb-title { text-align: left; } .sb-author { text-align: left; }
            .sb-desc { display: none; }
            .viewer { height: 72vh; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <a class="btn-back" href="manage-digital-books.php">
        <i class="fa-solid fa-arrow-left"></i>
        Back
    </a>
    <span class="topbar-name"><?= $title ?></span>
    <span class="status-pill <?= $book['is_active'] ? 'active' : 'inactive' ?>">
        <i class="fa-solid fa-circle" style="font-size:0.5rem"></i>
        <?= $book['is_active'] ? 'Active' : 'Inactive' ?>
    </span>
</header>

<div class="reader-body">

    <aside class="sidebar">
        <div class="sb-hero">
            <div class="sb-book-icon"><i class="fa-solid fa-file-pdf"></i></div>
            <div>
                <div class="sb-title"><?= $title ?></div>
                <?php if (!empty($book['author'])): ?>
                <div class="sb-author"><?= e($book['author']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="sb-info">
            <?php if (!empty($book['author'])): ?>
            <div class="sb-row">
                <div class="sb-row-icon"><i class="fa-solid fa-user-pen"></i></div>
                <div><div class="sb-row-label">Author</div><div class="sb-row-val"><?= e($book['author']) ?></div></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($book['category'])): ?>
            <div class="sb-row">
                <div class="sb-row-icon"><i class="fa-solid fa-tag"></i></div>
                <div><div class="sb-row-label">Category</div><div class="sb-row-val"><?= e($book['category']) ?></div></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($book['language'])): ?>
            <div class="sb-row">
                <div class="sb-row-icon"><i class="fa-solid fa-language"></i></div>
                <div><div class="sb-row-label">Language</div><div class="sb-row-val"><?= e($book['language']) ?></div></div>
            </div>
            <?php endif; ?>

            <div class="sb-row">
                <div class="sb-row-icon"><i class="fa-solid fa-file-pdf"></i></div>
                <div><div class="sb-row-label">File Size</div><div class="sb-row-val"><?= fmtSize((int)$book['file_size']) ?></div></div>
            </div>

            <?php if (!empty($book['uploaded_by'])): ?>
            <div class="sb-row">
                <div class="sb-row-icon"><i class="fa-solid fa-circle-user"></i></div>
                <div><div class="sb-row-label">Uploaded By</div><div class="sb-row-val"><?= e($book['uploaded_by']) ?></div></div>
            </div>
            <?php endif; ?>

            <div class="sb-row">
                <div class="sb-row-icon"><i class="fa-solid fa-calendar-days"></i></div>
                <div><div class="sb-row-label">Added On</div><div class="sb-row-val"><?= date('d M Y', strtotime($book['uploaded_at'])) ?></div></div>
            </div>
        </div>

        <?php if (!empty($book['description'])): ?>
        <div class="sb-desc">
            <div class="sb-desc-label">About this Book</div>
            <div class="sb-desc-text"><?= nl2br(e($book['description'])) ?></div>
        </div>
        <?php endif; ?>
    </aside>

    <div class="viewer">
        <div class="viewer-chrome">
            <div class="chrome-dot red"></div>
            <div class="chrome-dot yellow"></div>
            <div class="chrome-dot green"></div>
            <div class="chrome-url">
                <i class="fa-solid fa-file-pdf"></i>
                <?= $title ?>
            </div>
        </div>
        <div class="viewer-frame-wrap">
            <div class="pdf-loading" id="loader">
                <div class="spin-ring"></div>
                <span>Loading &ldquo;<?= $title ?>&rdquo;&hellip;</span>
            </div>
            <iframe
                class="pdf-frame"
                src="<?= e($pdfUrl) ?>"
                title="<?= $title ?>"
                onload="document.getElementById('loader').classList.add('done')"
            ></iframe>
        </div>
    </div>

</div>

<script>
</script>
</body>
</html>
