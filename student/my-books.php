<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-student.php';

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>My Bookmarks | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <style>
        .filter-bar { display:flex; flex-wrap:wrap; gap:.6rem; margin-bottom:1.2rem; background:var(--bg-card); border:1px solid var(--border-soft); border-radius:var(--radius); padding:.8rem; }
        .filter-bar input,.filter-bar select { flex:1; min-width:150px; padding:.55rem .8rem; border:1px solid var(--border-soft); border-radius:8px; background:var(--bg-input); color:var(--text-main); font-size:.88rem; outline:none; font-family:inherit; }
        .filter-bar input:focus,.filter-bar select:focus { border-color:var(--border-focus); }

        /* ── List layout ── */
        .bm-list { display:flex; flex-direction:column; gap:.75rem; }

        /* ── Card ── */
        .bm-card {
            background:var(--bg-card); border:1px solid var(--border-soft);
            border-radius:var(--radius); display:flex; flex-direction:column;
            overflow:hidden; transition:box-shadow .18s, border-color .18s;
        }
        .bm-card:hover { box-shadow:var(--shadow-md); border-color:var(--border-focus); }

        .bm-top { display:flex; align-items:stretch; flex:1; }

        /* ── Cover ── */
        .bm-cover {
            width:120px; min-width:90px;
            height:180px;
            overflow:hidden; border-radius:var(--radius) 0 0 0;
            background:var(--bg-hover);
            display:flex; align-items:center; justify-content:center;
        }
        .bm-cover img { width:100%; height:100%; object-fit:cover; display:block; }

        /* ── Info ── */
        .bm-info {
            flex:1; padding:.7rem 1rem;
            display:flex; flex-direction:column; gap:.25rem;
            min-width:0;
        }
        .bm-title {
            font-size:.92rem; font-weight:700; color:var(--text-main); line-height:1.3;
            display:-webkit-box; -webkit-line-clamp:2;
            -webkit-box-orient:vertical; overflow:hidden;
        }
        .bm-author { font-size:.78rem; color:var(--text-muted); }
        .bm-tags { display:flex; flex-wrap:wrap; gap:.3rem; margin-top:.15rem; }
        .bm-tag {
            font-size:.7rem; font-weight:600; padding:.15rem .5rem;
            border-radius:99px; display:inline-flex; align-items:center; gap:.25rem;
        }
        .bm-tag.cat  { background:var(--accent-light); color:var(--accent); }
        .bm-tag.lang { background:hsl(265,50%,95%); color:hsl(265,50%,40%); }
        .bm-tag.type { background:var(--bg-hover); color:var(--text-muted); border:1px solid var(--border-soft); font-family:monospace; }
        .bm-date { font-size:.7rem; color:var(--text-light); margin-top:.1rem; }

        /* ── Actions ── */
        .bm-actions {
            display:flex; gap:.5rem;
            padding:.6rem .9rem; border-top:1px solid var(--border-soft);
            background:var(--bg-main);
        }
        .bm-btn {
            display:inline-flex; align-items:center; justify-content:center;
            gap:.3rem; padding:.4rem .7rem; border-radius:var(--radius-sm);
            font-size:.75rem; font-weight:600; cursor:pointer; border:1.5px solid;
            font-family:inherit; transition:background .15s, color .15s; flex:1;
            white-space:nowrap;
        }
        .bm-btn.read   { background:var(--accent-light); color:var(--accent); border-color:hsl(225,60%,80%); }
        .bm-btn.read:hover { background:var(--accent); color:#fff; border-color:var(--accent); }
        .bm-btn.remove { background:var(--danger-bg); color:var(--danger); border-color:hsl(0,50%,82%); }
        .bm-btn.remove:hover { background:var(--danger); color:#fff; border-color:var(--danger); }

        /* ── Empty state ── */
        .empty-state { text-align:center; padding:3rem 1rem; color:var(--text-muted); }
        .empty-state i { font-size:2.5rem; display:block; margin-bottom:.7rem; opacity:.3; }

        /* ── Pagination ── */
        .pagination { display:flex; gap:.4rem; margin-top:1.2rem; flex-wrap:wrap; }
        .page-link { padding:.4rem .75rem; border:1px solid var(--border-soft); border-radius:6px; font-size:.82rem; cursor:pointer; background:var(--bg-card); color:var(--text-muted); text-decoration:none; }
        .page-link.active { background:var(--accent); color:#fff; border-color:var(--accent); }
    </style>
</head>
<body>
    <?php include 'includes/student-header.php'; ?>

    <main class="main">
        <div class="page-container" style="max-width:1200px">
            <h2 class="page-title">My Digital Bookmarks</h2>

            <div class="filter-bar">
                <input type="text" id="searchInput" placeholder="Search..." oninput="debounce()">
                <select id="sortBy" onchange="loadBooks()">
                    <option value="bookmarked">Newest Bookmarked</option>
                    <option value="title">Title</option>
                    <option value="author">Author</option>
                </select>
            </div>

            <div id="digiWrap" class="bm-list"></div>
            <div class="pagination" id="pagination"></div>
        </div>
    </main>

    <script>
        let currentPage = 1, debTimer;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        function debounce() {
            clearTimeout(debTimer);
            debTimer = setTimeout(() => { currentPage = 1; loadBooks(); }, 350);
        }

        function loadBooks(page) {
            if (page) currentPage = page;
            const params = new URLSearchParams({
                search: document.getElementById('searchInput').value,
                sort:   document.getElementById('sortBy').value,
                page:   currentPage
            });

            const wrap = document.getElementById('digiWrap');
            wrap.innerHTML = '<p style="color:var(--text-muted);padding:2rem;grid-column:1/-1"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</p>';

            fetch('../ajax/my-books-fetch.php?' + params)
                .then(r => r.text())
                .then(html => {
                    const [cards, pages] = html.split('<!-- SPLIT -->');
                    wrap.innerHTML = cards.trim() || '<p style="color:var(--text-muted);padding:2rem;grid-column:1/-1">No bookmarks yet. <a href="digital-books.php">Browse digital books</a></p>';
                    document.getElementById('pagination').innerHTML = pages?.trim() || '';
                });
        }

        function changePage(p) { loadBooks(p); }

        /* ── Read button ── */
        document.getElementById('digiWrap').addEventListener('click', e => {
            const btn = e.target.closest('.read-btn');
            if (!btn) return;
            const card = btn.closest('.mybook-card');
            if (!card) return;
            const id = card.dataset.id;
            window.location.href = `reader.php?id=${id}`;
        });

        /* ── Remove bookmark ── */
        function removeBookmark(id) {
            fetch('../ajax/toggle-bookmark.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `digi_id=${id}&csrf_token=${encodeURIComponent(csrf)}`
            })
            .then(r => r.json())
            .then(data => {
                if (!data.bookmarked) {
                    const card = document.querySelector(`.mybook-card[data-id="${id}"]`);
                    if (card) card.remove();
                }
            });
        }

        loadBooks();
    </script>
    <?php include '../includes/_footer.php'; ?>
</body>
</html>
