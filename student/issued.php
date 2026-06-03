<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-student.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Issued Books | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <style>
        .issued-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1rem; }
        .issue-card { background:var(--bg-card); border:1px solid
        var(--border-soft); border-radius:var(--radius); padding: .8rem .8rem;
        display:flex; gap:1rem; align-items:flex-start; }
        .issue-card img { width:100px; height:160px; object-fit:cover; border-radius:6px; flex-shrink:0; }
        .issue-info h3 { font-size:.9rem; font-weight:700; margin-bottom:.3rem; }
        .issue-info p { font-size:.78rem; color:var(--text-muted); margin:.15rem 0; }
        .issue-status { display:inline-flex; align-items:center; gap:.3rem; font-size:.72rem; font-weight:700; padding:.2rem .6rem; border-radius:99px; text-transform:uppercase; }
        .issue-status.issued   { background:var(--info-bg);    color:var(--info); }
        .issue-status.returned { background:var(--success-bg); color:var(--success); }
        .overdue { border-color:var(--danger); background:var(--danger-bg); }
        .overdue .issue-info h3 { color:var(--danger); }
        .pagination { display:flex; gap:.4rem; margin-top:1rem; flex-wrap:wrap; }
        .page-link { padding:.4rem .75rem; border:1px solid var(--border-soft); border-radius:6px; font-size:.82rem; cursor:pointer; background:var(--bg-card); color:var(--text-muted); text-decoration:none; }
        .page-link.active { background:var(--accent); color:#fff; border-color:var(--accent); }
        .loading { text-align:center; padding:3rem; color:var(--text-muted); }
    </style>
</head>
<body>
    <?php include 'includes/student-header.php'; ?>

    <main class="main">
        <div class="page-container" style="max-width:1000px">
            <h2 class="page-title">My Issued Books</h2>

            <div id="issuedWrap" class="issued-grid">
                <div class="loading" style="grid-column:1/-1">
                    <i class="fa-solid fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
            <div class="pagination" id="pagination"></div>
        </div>
    </main>

    <?php include '../includes/_footer.php'; ?>

    <script>
        let currentPage = 1;

        function loadIssued(page) {
            if (page) currentPage = page;

            const wrap = document.getElementById('issuedWrap');
            wrap.innerHTML = '<div class="loading" style="grid-column:1/-1"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';

            fetch(`../ajax/issued-student-fetch.php?page=${currentPage}`)
                .then(r => r.text())
                .then(html => {
                    const [cards, pages] = html.split('<!-- SPLIT -->');
                    wrap.innerHTML = cards.trim();
                    document.getElementById('pagination').innerHTML = pages?.trim() || '';
                })
                .catch(() => {
                    wrap.innerHTML = '<p style="color:var(--danger);padding:2rem">Failed to load.</p>';
                });
        }

        function changePage(p) { loadIssued(p); window.scrollTo(0, 0); }

        loadIssued();
    </script>
</body>
</html>
