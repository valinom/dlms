<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-student.php';

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$languages  = $pdo->query("SELECT id, name FROM languages  ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Digital Books | DLMS</title>
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
            flex: 1; min-width: 140px;
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
            display: flex; flex-direction: column;
            overflow: hidden;
            transition: box-shadow .18s, border-color .18s;
        }
        .bl-card:hover { box-shadow: var(--shadow-md); border-color: var(--border-focus); }

        .bl-top { display: flex; align-items: stretch; flex: 1; }

        .bl-cover {
            width: 120px;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; position: relative;
        }
        .bl-cover img { width: 120px; height: 180px; object-fit: cover; display: block; }

        .bl-info {
            flex: 1; padding: .75rem 1rem;
            display: flex; flex-direction: column; gap: .3rem;
            min-width: 0;
        }
        .bl-title {
            font-size: .92rem; font-weight: 700;
            color: var(--text-main); line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .bl-author { font-size: .78rem; color: var(--text-muted); }
        .bl-tags { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .1rem; }
        .bl-tag {
            font-size: .7rem; font-weight: 600; padding: .10rem .40rem;
            border-radius: 99px; display: inline-flex; align-items: center; gap: .25rem;
        }
        .bl-tag.cat  { background: var(--accent-light); color: var(--accent); }
        .bl-tag.lang { background: hsl(265,50%,95%); color: hsl(265,50%,40%); }
        .bl-tag.type { background: var(--bg-hover); color: var(--text-muted); border: 1px solid var(--border-soft); font-family: monospace; letter-spacing: .03em; }
        .bl-meta { font-size: .72rem; color: var(--text-light); margin-top: .1rem; }

        .bl-actions {
            padding: .6rem .9rem;
            border-top: 1px solid var(--border-soft);
            display: flex; gap: .5rem;
            background: var(--bg-main);
        }
        .bl-btn {
            display: inline-flex; align-items: center; justify-content: center;
            gap: .35rem; padding: .45rem .8rem;
            border-radius: var(--radius-sm); font-size: .78rem;
            font-weight: 600; cursor: pointer; border: 1.5px solid;
            font-family: inherit; transition: background .15s, color .15s;
            text-decoration: none; flex: 1;
        }
        .bl-btn.read {
            background: var(--accent-light); color: var(--accent);
            border-color: hsl(225,60%,80%);
        }
        .bl-btn.read:hover { background: var(--accent); color: #fff; border-color: var(--accent); }
        .bl-btn.bmark {
            background: hsl(38,80%,95%); color: hsl(38,80%,30%);
            border-color: hsl(38,60%,78%);
        }
        .bl-btn.bmark.active { background: hsl(38,80%,50%); color: #fff; border-color: hsl(38,80%,50%); }

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
            <h2 class="page-title">Digital Books</h2>

            <div class="filter-bar">
                <input type="text" id="searchInput" placeholder="Search title, author..." oninput="debounceLoad()">
                <select id="categoryFilter" onchange="loadBooks()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="languageFilter" onchange="loadBooks()">
                    <option value="">All Languages</option>
                    <?php foreach ($languages as $l): ?>
                    <option value="<?= $l['id'] ?>"><?= e($l['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="sortBy" onchange="loadBooks()">
                    <option value="title">Sort: Title</option>
                    <option value="author">Sort: Author</option>
                    <option value="date">Sort: Newest</option>
                </select>
            </div>

            <div id="digiWrap" class="bl-list">
                <div class="loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>
            </div>
            <div class="pagination" id="pagination"></div>
        </div>
    </main>

    <script>
        let currentPage = 1, debTimer;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        function debounceLoad() {
            clearTimeout(debTimer);
            debTimer = setTimeout(() => { currentPage = 1; loadBooks(); }, 350);
        }

        function loadBooks(page) {
            if (page) currentPage = page;
            const params = new URLSearchParams({
                search:   document.getElementById('searchInput').value,
                category: document.getElementById('categoryFilter').value,
                language: document.getElementById('languageFilter').value,
                sort:     document.getElementById('sortBy').value,
                page:     currentPage
            });

            document.getElementById('digiWrap').innerHTML =
                '<div class="loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';

            fetch('../ajax/digi-books-fetch.php?' + params)
                .then(r => r.json())
                .then(data => {
                    if (!data.books || !data.books.length) {
                        document.getElementById('digiWrap').innerHTML =
                            '<p style="color:var(--text-muted);padding:2rem;text-align:center"><i class="fa-solid fa-file-pdf fa-2x" style="display:block;margin-bottom:.5rem;opacity:.3"></i>No digital books found.</p>';
                        document.getElementById('pagination').innerHTML = '';
                        return;
                    }

                    document.getElementById('digiWrap').innerHTML = data.books.map(renderCard).join('');

                    let pg = '';
                    if (data.totalPages > 1) {
                        for (let i = 1; i <= data.totalPages; i++) {
                            pg += `<a class="page-link ${i === data.page ? 'active' : ''}" onclick="loadBooks(${i})" href="javascript:void(0)">${i}</a>`;
                        }
                    }
                    document.getElementById('pagination').innerHTML = pg;
                })
                .catch(err => {
                    document.getElementById('digiWrap').innerHTML =
                        '<p style="color:var(--danger);padding:2rem">Failed to load books. ' + err + '</p>';
                });
        }

        function renderCard(b) {
            const cover = b.cover_image
                ? `<img src="../ajax/book-cover.php?file=${encodeURIComponent(b.cover_image)}" alt="Cover"
                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
                : '';
            const placeholder = (!b.cover_image)
                ? `<img src="../assets/img/no-book.png">`
                : ``;

            return `
            <div class="bl-card">
                <div class="bl-top">
                    <div class="bl-cover">${cover}${placeholder}</div>
                    <div class="bl-info">
                        <div class="bl-title">${esc(b.title)}</div>
                        <div class="bl-author"><i class="fa-solid fa-user-pen fa-xs"></i> ${esc(b.author)}</div>
                        <div class="bl-tags">
                            <span class="bl-tag cat"><i class="fa-solid fa-tag fa-xs"></i> ${esc(b.category_name)}</span>
                            <span class="bl-tag lang"><i class="fa-solid fa-language fa-xs"></i> ${esc(b.language_name)}</span>
                            <span class="bl-tag type">${esc(b.file_type)}</span>
                        </div>
                        <div class="bl-meta">${esc(b.file_size)} &bull; ${esc(b.uploaded_at)}</div>
                    </div>
                </div>
                <div class="bl-actions">
                    <a href="reader.php?id=${b.id}" class="bl-btn read">
                        <i class="fa-solid fa-book-open"></i> Read
                    </a>
                    <button onclick="toggleBookmark(${b.id}, this)" class="bl-btn bmark ${b.is_bookmarked ? 'active' : ''}" id="bm-${b.id}">
                        <i class="fa-solid fa-bookmark"></i> ${b.is_bookmarked ? 'Saved' : 'Save'}
                    </button>
                </div>
            </div>`;
        }

        function esc(s) {
            return (s || '').replace(/[&<>"']/g, m =>
                ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])
            );
        }

        function toggleBookmark(id, btn) {
            fetch('../ajax/toggle-bookmark.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `digi_id=${id}&csrf_token=${encodeURIComponent(csrf)}`
            })
            .then(r => r.json())
            .then(d => {
                btn.classList.toggle('active', d.bookmarked);
                btn.innerHTML = d.bookmarked
                    ? '<i class="fa-solid fa-bookmark"></i> Saved'
                    : '<i class="fa-solid fa-bookmark"></i> Save';
            });
        }

        loadBooks();
    </script>
    <?php include '../includes/_footer.php'; ?>
</body>
</html>
