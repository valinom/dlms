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
    <title>Browse Books | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <style>
        /* ── Filter bar ── */
        .filter-bar {
            display: flex; flex-wrap: wrap; gap: .6rem;
            margin-bottom: 1.2rem;
            background: var(--bg-card); border: 1px solid var(--border-soft);
            border-radius: var(--radius); padding: .8rem;
        }
        .filter-bar input,
        .filter-bar select {
            flex: 1; min-width: 150px;
            padding: .55rem .8rem;
            border: 1px solid var(--border-soft); border-radius: 8px;
            background: var(--bg-input); color: var(--text-main);
            font-size: .88rem; outline: none; font-family: inherit;
        }
        .filter-bar input{
            min-width:350px;
        }
        
        .filter-bar input:focus,
        .filter-bar select:focus { border-color: var(--border-focus); }

        /* ── Book list ── */
        .bl-list { display: flex; flex-direction: column; gap: .75rem; }

        /* ── bl-card ── */
        .bl-card {
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            border-radius: var(--radius);
            display: flex; align-items: stretch;
            position: relative;
            transition: box-shadow .18s, border-color .18s;
        }
        .bl-card:hover { box-shadow: var(--shadow-md); border-color: var(--border-focus); }

        .bl-cover {
            width: 120px;
            height:180px;
            background: var(--bg-hover);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            border-radius: var(--radius) 0 0 var(--radius);
        }
        .bl-cover img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .bl-cover-placeholder {
            width: 120px; height: 180px;
            
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, hsl(225,40%,92%), hsl(225,40%,85%));
            color: hsl(225,30%,65%); font-size: 2rem;
        }

        /* ── Heart button on card top-right ── */
        .fav-btn {
            position: absolute; top: .5rem; right: .5rem;
            width: 28px; height: 28px; border-radius: 50%;
            background: rgba(255,255,255,.88);
            border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: var(--danger); font-size: .8rem;
            box-shadow: 0 1px 4px rgba(0,0,0,.18);
            transition: background .15s, transform .1s;
            z-index: 2;
        }
        .fav-btn:hover { background: #fff; transform: scale(1.1); }
        .fav-btn.active { background: var(--danger); color: #fff; }

        .bl-info {
            flex: 1; padding: .75rem 1rem;
            display: flex; flex-direction: column; gap: .3rem;
            min-width: 0;
        }
        .bl-title {
            font-size: .92rem; font-weight: 700;
            color: var(--text-main); line-height: 1.3;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .bl-author { font-size: .78rem; color: var(--text-muted); }
        .bl-tags { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .1rem; }
        .bl-tag {
            font-size: .7rem; font-weight: 600; padding: .18rem .55rem;
            border-radius: 99px; display: inline-flex; align-items: center; gap: .25rem;
        }
        .bl-tag.cat  { background: var(--accent-light); color: var(--accent); }
        .bl-tag.isbn { background: var(--bg-hover); color: var(--text-muted); border: 1px solid var(--border-soft); }
        .bl-stock {
            display: inline-flex; align-items: center; gap: .3rem;
            font-size: .72rem; font-weight: 700; padding: .2rem .6rem;
            border-radius: 99px; width: fit-content; margin-top: .1rem;
        }
        .bl-stock.in  { background: hsl(145,50%,93%); color: hsl(145,50%,28%); }
        .bl-stock.out { background: var(--danger-bg); color: var(--danger); }

        /* ── Pagination ── */
        .pagination { display: flex; gap: .4rem; margin-top: 1rem; flex-wrap: wrap; }
        .page-link {
            padding: .4rem .75rem; border: 1px solid var(--border-soft);
            border-radius: 6px; font-size: .82rem; cursor: pointer;
            background: var(--bg-card); color: var(--text-muted); text-decoration: none;
        }
        .page-link.active { background: var(--accent); color: #fff; border-color: var(--accent); }

        .loading { text-align: center; padding: 3rem; color: var(--text-muted); }
    </style>
</head>
<body>
    <?php include 'includes/student-header.php'; ?>

    <main class="main">
        <div class="page-container" style="max-width:900px">
            <h2 class="page-title">Browse Books</h2>

            <div class="filter-bar">
                <input type="text" id="searchInput" placeholder="Search books, author, ISBN..." oninput="debounceLoad()">
                <select id="categoryFilter" onchange="loadBooks()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filterStock" onchange="loadBooks()">
                    <option value="all">All</option>
                    <option value="available">Available</option>
                    <option value="out">Out of Stock</option>
                </select>
                <select id="sortBy" onchange="loadBooks()">
                    <option value="name">Sort: Name</option>
                    <option value="author">Sort: Author</option>
                    <option value="quantity">Sort: Quantity</option>
                    <option value="category">Sort: Category</option>
                </select>
            </div>

            <div id="booksWrap" class="bl-list">
                <div class="loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>
            </div>
            <div class="pagination" id="pagination"></div>
        </div>
    </main>

    <script>
        let currentPage = 1, debTimer = null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function debounceLoad() {
            clearTimeout(debTimer);
            debTimer = setTimeout(() => { currentPage = 1; loadBooks(); }, 350);
        }

        function loadBooks(page) {
            if (page) currentPage = page;
            const params = new URLSearchParams({
                search:   document.getElementById('searchInput').value,
                category: document.getElementById('categoryFilter').value,
                filter:   document.getElementById('filterStock').value,
                sort:     document.getElementById('sortBy').value,
                page:     currentPage
            });

            document.getElementById('booksWrap').innerHTML =
                '<div class="loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';

            fetch('../ajax/books-fetch.php?' + params)
                .then(r => r.text())
                .then(html => {
                    const [cards, pages] = html.split('<!-- SPLIT -->');
                    document.getElementById('booksWrap').innerHTML = cards.trim();
                    document.getElementById('pagination').innerHTML = pages?.trim() || '';
                })
                .catch(() => {
                    document.getElementById('booksWrap').innerHTML =
                        '<p style="color:var(--danger);padding:2rem">Failed to load books.</p>';
                });
        }

        function changePage(p) { loadBooks(p); window.scrollTo(0,0); }

        function toggleFavorite(bookId, btn) {
            fetch('../ajax/toggle-favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `book_id=${bookId}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(r => r.json())
            .then(data => {
                btn.classList.toggle('active', data.favorited);
                btn.title = data.favorited ? 'Remove from favorites' : 'Add to favorites';
            });
        }

        loadBooks();
    </script>
    <?php include '../includes/_footer.php'; ?>
</body>
</html>
