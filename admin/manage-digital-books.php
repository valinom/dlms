<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Manage Digital Books | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <link rel="stylesheet" href="assets/css/adminui.css">
    <link rel="stylesheet" href="assets/css/book.css">
    <link rel="stylesheet" href="../assets/css/libook.css">
    <style>
        /* ── Book List Card ── */
        .filter-bar input{
            min-width:350px;
        }
        
        .book-list { display: flex; flex-direction: column; gap: 1rem; }

        .bl-card {
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-sm);
            transition: box-shadow .2s;
        }
        .bl-card:hover { box-shadow: var(--shadow-md); }

        .bl-top { display: flex; }

        .bl-cover img {
            width: 120px;
            height: 180px;
            aspect-ratio: 2 / 3;
            object-fit: cover;
            display: block;
            padding: 2px;
            border-radius: 12px;
        }
        .bl-cover-placeholder {
            width: 120px;
            min-height: 180px;
            background: linear-gradient(145deg, hsl(225,50%,55%), hsl(225,50%,38%));
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,.6);
            font-size: 1.8rem;
            flex-shrink: 0;
            border-radius: 12px 0 0 12px;
        }

        .bl-info {
            flex: 1;
            padding: 1rem 1.1rem;
            display: flex;
            flex-direction: column;
            gap: .28rem;
        }

        .bl-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.3;
        }
        .bl-author {
            font-size: .87rem;
            color: var(--text-muted);
        }

        .bl-tags {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .4rem;
            margin: .2rem 0;
        }
        .bl-tag {
            display: inline-flex;
            align-items: center;
            gap: .30rem;
            background: var(--bg-hover);
            border: 1px solid var(--border-soft);
            color: var(--text-muted);
            font-size: .78rem;
            font-weight: 600;
            padding: .10rem .30rem;
            border-radius: 99px;
        }
        .bl-tag i { font-size: .68rem; }
        .bl-tag-divider { color: var(--border-soft); font-size: .9rem; }

        .bl-meta {
            font-size: .82rem;
            color: var(--text-muted);
        }
        .bl-meta i { margin-right: .25rem; }

        .status-badge.active {
            color: var(--success);
            border-color: hsl(145,45%,72%);
        }
        .status-badge.inactive {
            color: var(--danger);
            border-color: hsl(0,50%,82%);
        }

        /* Bottom action row */
        .bl-actions {
            border-top: 1px solid var(--border-soft);
            padding: .65rem .9rem;
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            background: var(--bg-main);
        }

        .bl-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem .95rem;
            border-radius: 12px;
            font-size: .84rem;
            font-weight: 600;
            cursor: pointer;
            border: 1.5px solid;
            text-decoration: none;
            transition: filter .15s, transform .1s;
            background: var(--bg-card);
            font-family: inherit;
            white-space: nowrap;
        }
        .bl-btn:hover { filter: brightness(.93); text-decoration: none; }
        .bl-btn:active { transform: scale(.97); }

        .bl-btn.view       { color: hsl(200,70%,40%); border-color: hsl(200,50%,78%); background: hsl(200,70%,96%); }
        .bl-btn.edit       { color: hsl(38,80%,32%);  border-color: hsl(38,60%,76%);  background: hsl(38,90%,96%); }
        .bl-btn.deactivate { color: var(--text-muted); border-color: var(--border-soft); background: var(--bg-hover); }
        .bl-btn.activate   { color: var(--success);   border-color: hsl(145,45%,72%); background: var(--success-bg); }
        .bl-btn.del        { color: var(--danger);     border-color: hsl(0,50%,82%);   background: var(--danger-bg);
                             min-width: 40px; justify-content: center; }

        .bl-empty {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-muted);
            font-size: .95rem;
        }
        .bl-empty i { font-size: 2.5rem; display: block; margin-bottom: .7rem; opacity: .3; }
    </style>
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>
    <?php include '../includes/_toast.php'; ?>

    <main class="main">
        <div class="page-container-books">

            <div class="page-title">
                <h2>Manage Digital Books</h2>
                <a href="add-digital-book.php" class="alert success">
                    <i class="fa-solid fa-plus"></i> Add Digital Book
                </a>
            </div>

            <!-- FILTER BAR -->
            <div class="filter-bar">
                <input type="text" id="search" placeholder="Search title, author or category…">
                <select id="category">
                    <option value="0">All Categories</option>
                    <?php
                    $cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($cats as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="language">
                    <option value="0">All Languages</option>
                    <?php
                    $langs = $pdo->query("SELECT id, name FROM languages ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($langs as $l): ?>
                    <option value="<?= $l['id'] ?>"><?= e($l['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="status">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                <select id="sort">
                    <option value="title">Sort: Title</option>
                    <option value="author">Sort: Author</option>
                    <option value="date">Sort: Date Added</option>
                </select>
            </div>

            <!-- BOOK LIST -->
            <div id="digiContainer" class="book-list">
                <div class="bl-empty"><i class="fa-solid fa-book-open"></i>Loading books…</div>
            </div>

            <!-- PAGINATION -->
            <div id="pagination" class="pagination" style="margin-top:1.2rem"></div>

        </div>
    </main>

    <script src="../assets/js/dlms-dialogs.js"></script>
    <script>
    let page = 1, debounce;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    function esc(s) {
        return String(s||'').replace(/[&<>"']/g, m =>
            ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }

    function loadBooks() {
        const search   = document.getElementById('search').value;
        const category = document.getElementById('category').value;
        const language = document.getElementById('language').value;
        const status   = document.getElementById('status').value;
        const sort     = document.getElementById('sort').value;
        const params   = new URLSearchParams({ page, search, category, language, status, sort });

        fetch(`ajax/digi-books-fetch.php?${params}`)
            .then(r => r.json())
            .then(data => {
                const container  = document.getElementById('digiContainer');
                const pagination = document.getElementById('pagination');

                if (!data.books || data.books.length === 0) {
                    container.innerHTML = `<div class="bl-empty">
                        <i class="fa-solid fa-box-open"></i>No digital books found.
                    </div>`;
                    pagination.innerHTML = '';
                    return;
                }

                container.innerHTML = data.books.map(b => {
                    const cover = b.cover_image
                        ? `<img src="../ajax/book-cover.php?file=${encodeURIComponent(b.cover_image)}" alt="Cover">`
                        : `<img src="../assets/img/no-book.png">`;

                    const isActive = b.is_active == 1;
                    const statusBadge = isActive
                        ? `<span class="status-badge active"><i class="fa-solid
                        fa-circle-check"></i> Active</span>`
                        : `<span class="status-badge inactive"><i
                        class="fa-solid fa-circle-xmark"></i> Inactive</span>`;

                    const toggleBtn = isActive
                        ? `<button class="bl-btn deactivate toggle-digi" data-id="${b.id}"><i class="fa-solid fa-eye-slash"></i> Deactivate</button>`
                        : `<button class="bl-btn activate toggle-digi" data-id="${b.id}"><i class="fa-solid fa-eye"></i> Activate</button>`;

                    const linkedBook = b.linked_book
                        ? `<div class="bl-meta"><i class="fa-solid fa-link"></i>${esc(b.linked_book)}</div>` : '';

                    return `
                    <div class="bl-card" data-id="${b.id}">
                        <div class="bl-top">
                            <div class="bl-cover">${cover}</div>
                            <div class="bl-info">
                                <div class="bl-title">${esc(b.title)}</div>
                                <div class="bl-author">Author: ${esc(b.author)}</div>
                                <div class="bl-tags">
                                    <span class="bl-tag"><i class="fa-solid fa-tag"></i>${esc(b.category_name)}</span>
                                    <span class="bl-tag"><i class="fa-solid fa-language"></i>${esc(b.language_name)}</span>
                                    <span class="bl-tag-divider">|</span>
                                    <span class="bl-tag">${statusBadge}</span>
                                </div>
                                <div class="bl-meta"><i class="fa-solid fa-file"></i>${esc(b.file_type)} &nbsp;·&nbsp; ${esc(b.file_size)}</div>
                                <div class="bl-meta"><i class="fa-solid fa-clock"></i>Uploaded by ${esc(b.uploader_name)} on ${esc(b.uploaded_at)}</div>
                                ${linkedBook}
                            </div>
                        </div>
                        <div class="bl-actions">
                            <a href="reader.php?id=${b.id}" target="_blank" class="bl-btn view">
                                <i class="fa-solid fa-eye"></i> View
                            </a>
                            <a href="edit-digital-book.php?id=${b.id}" class="bl-btn edit">
                                <i class="fa-solid fa-pen"></i> Edit
                            </a>
                            ${toggleBtn}
                            
                            <button class="bl-btn del delete-digi" data-id="${b.id}" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>`;
                }).join('');

                if (data.totalPages <= 1) {
                    pagination.innerHTML = '';
                } else {
                    let pages = '';
                    for (let i = 1; i <= data.totalPages; i++) {
                        pages += `<button class="alert ${i === data.page ? 'info' : ''}"
                            onclick="changePage(${i})">${i}</button>`;
                    }
                    pagination.innerHTML = pages;
                }
            })
            .catch(err => {
                document.getElementById('digiContainer').innerHTML =
                    `<div class="bl-empty" style="color:var(--danger)"><i class="fa-solid fa-triangle-exclamation"></i>Load error: ${esc(err.message)}</div>`;
            });
    }

    document.getElementById('search').addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => { page = 1; loadBooks(); }, 300);
    });
    ['category','language','status','sort'].forEach(id =>
        document.getElementById(id).addEventListener('change', () => { page = 1; loadBooks(); })
    );
    function changePage(p) { page = p; loadBooks(); }

    /* DELETE */
    document.addEventListener('click', e => {
        const btn = e.target.closest('.delete-digi');
        if (!btn) return;
        showConfirm({
            title: 'Delete Digital Book?',
            message: 'This will permanently delete the file and cannot be undone.',
            confirmText: 'Delete',
            onConfirm: () => {
                fetch('ajax/delete-digi-book.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ digi_id: btn.dataset.id, csrf_token: csrf })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        btn.closest('.bl-card').remove();
                        showToast('Digital book deleted.', 'success');
                    } else {
                        showToast(data.error || 'Delete failed', 'error');
                    }
                })
                .catch(() => showToast('Server error', 'error'));
            }
        });
    });

    /* TOGGLE STATUS */
    document.addEventListener('click', e => {
        const btn = e.target.closest('.toggle-digi');
        if (!btn) return;
        fetch('ajax/toggle-digi-book.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ digi_id: btn.dataset.id, csrf_token: csrf })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('Status updated.', 'success');
                loadBooks();
            } else {
                showToast(data.error || 'Action failed', 'error');
            }
        })
        .catch(() => showToast('Server error', 'error'));
    });

    loadBooks();
    </script>
    <?php include '../includes/_footer.php'; ?>
</body>
</html>
