<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-student.php';

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Favorites | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $csrf ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <style>
        .fav-list { display:flex; flex-direction:column; gap:.75rem; }
        .fav-card { background:var(--bg-card); border:1px solid var(--border-soft); border-radius:var(--radius); display:flex; align-items:stretch; position:relative; transition:box-shadow .18s, border-color .18s; }
        .fav-card:hover { box-shadow:var(--shadow-md); border-color:var(--border-focus); }
        .fav-cover { width:90px; min-width:90px; overflow:hidden; border-radius:var(--radius) 0 0 var(--radius); background:var(--bg-hover); display:flex; align-items:center; justify-content:center; }
        .fav-cover img { width:100%; height:100%; object-fit:cover; display:block; }
        .fav-cover-placeholder { width:100%; height:100%; min-height:110px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,hsl(225,40%,92%),hsl(225,40%,82%)); color:hsl(225,30%,65%); font-size:1.8rem; }
        .fav-info { flex:1; padding:.7rem 1rem; display:flex; flex-direction:column; gap:.25rem; min-width:0; }
        .fav-title { font-size:.92rem; font-weight:700; color:var(--text-main); line-height:1.3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .fav-author { font-size:.78rem; color:var(--text-muted); }
        .fav-tags { display:flex; flex-wrap:wrap; gap:.3rem; margin-top:.15rem; }
        .fav-tag { font-size:.7rem; font-weight:600; padding:.15rem .5rem; border-radius:99px; display:inline-flex; align-items:center; gap:.25rem; }
        .fav-tag.cat  { background:var(--accent-light); color:var(--accent); }
        .fav-tag.isbn { background:var(--bg-hover); color:var(--text-muted); border:1px solid var(--border-soft); }
        .fav-stock { display:inline-flex; align-items:center; gap:.3rem; font-size:.72rem; font-weight:700; padding:.2rem .55rem; border-radius:99px; width:fit-content; margin-top:.1rem; }
        .fav-stock.in  { background:hsl(145,50%,93%); color:hsl(145,50%,28%); }
        .fav-stock.out { background:var(--danger-bg); color:var(--danger); }
        .fav-remove-btn { position:absolute; top:.5rem; right:.5rem; width:28px; height:28px; border-radius:50%; background:rgba(255,255,255,.88); border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; color:var(--danger); font-size:.8rem; box-shadow:0 1px 4px rgba(0,0,0,.18); transition:background .15s, transform .1s; }
        .fav-remove-btn:hover { background:var(--danger); color:#fff; transform:scale(1.1); }
        .empty-state { background:var(--bg-card); border:1px solid var(--border-soft); border-radius:var(--radius); padding:3rem; text-align:center; color:var(--text-muted); }
        .empty-state i { font-size:2rem; display:block; margin-bottom:.8rem; opacity:.4; }
        .pagination { display:flex; gap:.4rem; margin-top:1rem; flex-wrap:wrap; }
        .page-link { padding:.4rem .75rem; border:1px solid var(--border-soft); border-radius:6px; font-size:.82rem; cursor:pointer; background:var(--bg-card); color:var(--text-muted); text-decoration:none; }
        .page-link.active { background:var(--accent); color:#fff; border-color:var(--accent); }
        .loading { text-align:center; padding:3rem; color:var(--text-muted); }
    </style>
</head>
<body>
    <?php include 'includes/student-header.php'; ?>

    <main class="main">
        <div class="page-container" style="max-width:1100px">
            <h2 class="page-title">My Favorites</h2>

            <div id="favWrap" class="fav-list">
                <div class="loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>
            </div>
            <div class="pagination" id="pagination"></div>
        </div>
    </main>

    <?php include '../includes/_footer.php'; ?>

    <script>
        let currentPage = 1;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        function loadFavorites(page) {
            if (page) currentPage = page;

            const wrap = document.getElementById('favWrap');
            wrap.innerHTML = '<div class="loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';

            fetch(`../ajax/favorites-fetch.php?page=${currentPage}`)
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

        function changePage(p) { loadFavorites(p); window.scrollTo(0, 0); }

        function removeFavorite(bookId) {
            fetch('../ajax/toggle-favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `book_id=${bookId}&csrf_token=${encodeURIComponent(csrf)}`
            }).then(r => r.json()).then(d => {
                if (!d.favorited) {
                    document.getElementById('fav-' + bookId)?.remove();
                }
            });
        }

        loadFavorites();
    </script>
</body>
</html>
