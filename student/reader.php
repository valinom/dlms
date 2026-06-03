<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-student.php';

$bookId = (int)($_GET['id'] ?? 0);
if (!$bookId) redirect('digital-books.php');

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
        l.name  AS language
    FROM digital_books db
    LEFT JOIN categories c ON c.id = db.category_id
    LEFT JOIN languages  l ON l.id = db.language_id
    WHERE db.id = ? AND db.is_active = 1
    LIMIT 1
");
$stmt->execute([$bookId]);
$book = $stmt->fetch();
if (!$book) redirect('digital-books.php');

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
    <title><?= $title ?> — DLMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: var(--bg-main); color: var(--text-main); height: 100dvh; display: flex; flex-direction: column; overflow: hidden; }
        .topbar { height: 52px; display: flex; align-items: center; gap: .75rem; padding: 0 1.25rem; background: var(--bg-card); border-bottom: 1px solid var(--border-soft); flex-shrink: 0; z-index: 50; }
        .btn-back { display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .8rem; border:1px solid var(--border-soft); border-radius:8px; font-size:.78rem; font-weight:600; color:var(--text-muted); background:none; text-decoration:none; }
        .btn-back:hover { background:var(--bg-hover); color:var(--text-main); text-decoration:none; }
        .topbar-name { flex:1; font-size:.9rem; font-weight:700; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
        .reader-body { flex:1; display:grid; grid-template-columns:260px 1fr; overflow:hidden; }
        .sidebar { display:flex; flex-direction:column; background:var(--bg-card); border-right:1px solid var(--border-soft); overflow-y:auto; }
        .sb-hero { background:linear-gradient(150deg,var(--accent) 0%,hsl(225,45%,38%) 100%); padding:1.5rem 1.2rem; display:flex; flex-direction:column; align-items:center; gap:.8rem; flex-shrink:0; }
        .sb-icon { width:64px; height:80px; background:rgba(255,255,255,.12); border:2px solid rgba(255,255,255,.28); border-radius:5px 8px 8px 5px; display:flex; align-items:center; justify-content:center; font-size:1.8rem; color:#fff; }
        .sb-title { font-size:.85rem; font-weight:700; color:#fff; text-align:center; }
        .sb-author { font-size:.72rem; color:rgba(255,255,255,.7); text-align:center; }
        .sb-info { padding:.5rem 1rem .75rem; }
        .sb-row { display:flex; align-items:flex-start; gap:.6rem; padding:.6rem 0; border-bottom:1px solid var(--border-soft); }
        .sb-row:last-child { border-bottom:none; }
        .sb-row-icon { width:26px; height:26px; border-radius:6px; background:var(--bg-hover); display:flex; align-items:center; justify-content:center; font-size:.65rem; color:var(--accent); flex-shrink:0; }
        .sb-row-label { font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); margin-bottom:.15rem; }
        .sb-row-val { font-size:.78rem; font-weight:500; color:var(--text-main); }
        .viewer { display:flex; flex-direction:column; background:#404040; overflow:hidden; }
        .viewer-frame-wrap { flex:1; position:relative; overflow:hidden; }
        iframe.pdf-frame { position:absolute; inset:0; width:100%; height:100%; border:none; }
        .pdf-loading { position:absolute; inset:0; z-index:30; background:#404040; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:1rem; color:#ccc; transition:opacity .35s; }
        .pdf-loading.done { opacity:0; pointer-events:none; }
        .spin-ring { width:40px; height:40px; border-radius:50%; border:3px solid rgba(255,255,255,.1); border-top-color:var(--accent); animation:spin .75s linear infinite; }
        @keyframes spin { to { transform:rotate(360deg); } }
        @media(max-width:780px) {
            .reader-body { grid-template-columns:1fr; height:auto; overflow:visible; }
            body { height:auto; overflow:auto; }
            .sidebar { border-right:none; border-bottom:1px solid var(--border-soft); overflow:visible; }
            .viewer { height:72vh; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <a class="btn-back" href="digital-books.php"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <span class="topbar-name"><?= $title ?></span>
</header>

<div class="reader-body">
    <aside class="sidebar">
        <div class="sb-hero">
            <div class="sb-icon"><i class="fa-solid fa-file-pdf"></i></div>
            <div class="sb-title"><?= $title ?></div>
            <?php if (!empty($book['author'])): ?>
            <div class="sb-author"><?= e($book['author']) ?></div>
            <?php endif; ?>
        </div>
        <div class="sb-info">
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
                <div class="sb-row-icon"><i class="fa-solid fa-file"></i></div>
                <div><div class="sb-row-label">Size</div><div class="sb-row-val"><?= fmtSize((int)$book['file_size']) ?></div></div>
            </div>
            <div class="sb-row">
                <div class="sb-row-icon"><i class="fa-solid fa-calendar"></i></div>
                <div><div class="sb-row-label">Added</div><div class="sb-row-val"><?= date('d M Y', strtotime($book['uploaded_at'])) ?></div></div>
            </div>
        </div>
    </aside>

    <div class="viewer">
        <div class="viewer-frame-wrap">
            <div class="pdf-loading" id="loader">
                <div class="spin-ring"></div>
                <span>Loading &ldquo;<?= $title ?>&rdquo;&hellip;</span>
            </div>
            <iframe class="pdf-frame"
                src="<?= e($pdfUrl) ?>"
                title="<?= $title ?>"
                onload="document.getElementById('loader').classList.add('done')"
            ></iframe>
        </div>
    </div>
</div>
</body>
</html>
