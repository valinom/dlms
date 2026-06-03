<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Manage Books | DLMS</title>
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

        /* Top: cover + info */
        .bl-top {
            display: flex;
            gap: 0;
        }

        .bl-cover {
            width: 120px;
            height: 180px;
            aspect-ratio: 2 / 3;
            flex-shrink: 0;
            object-fit: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .04em;
        }
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
            width: 110px;
            min-height: 150px;
            background: hsl(225,35%,78%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .04em;
            flex-shrink: 0;
        }

        .bl-info {
            flex: 1;
            padding: 1rem 1.1rem;
            display: flex;
            flex-direction: column;
            gap: .3rem;
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
            margin: .25rem 0;
        }

        .bl-tag {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            background: var(--bg-hover);
            border: 1px solid var(--border-soft);
            color: var(--text-muted);
            font-size: .80rem;
            font-weight: 600;
            padding: .22rem .65rem;
            border-radius: 99px;
        }
        .bl-tag i { font-size: .7rem; }

        .bl-meta {
            font-size: .82rem;
            color: var(--text-muted);
            display: flex;
            flex-wrap: wrap;
            gap: .35rem .7rem;
            margin-top: .1rem;
        }
        .bl-meta span { display: flex; align-items: center; gap: .3rem; }
        .bl-tag-divider { color: var(--border-soft); font-size: .9rem; }

        .qty-badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            font-size: .80rem;
            font-weight: 600;
            padding: .22rem .65rem;
            border-radius: 99px;
            border: 1.5px solid;
        }
        .qty-badge.available {
            background: var(--success-bg);
            color: var(--success);
            border-color: hsl(145,45%,72%);
        }
        .qty-badge.out {
            background: var(--danger-bg);
            color: var(--danger);
            border-color: hsl(0,50%,82%);
        }

        /* Bottom: actions */
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
        }
        .bl-btn:hover { filter: brightness(.94); text-decoration: none; }
        .bl-btn:active { transform: scale(.97); }

        .bl-btn.view   { color: hsl(200,70%,40%); border-color: hsl(200,50%,78%); background: hsl(200,70%,96%); }
        .bl-btn.edit   { color: hsl(38,80%,32%);  border-color: hsl(38,60%,76%);  background: hsl(38,90%,96%); }
        .bl-btn.del    { color: var(--danger);     border-color: hsl(0,50%,82%);   background: var(--danger-bg); min-width:40px; justify-content:center; }

        /* Empty / loading */
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
                <h2>Manage Books</h2>
                <a href="add-book.php" class="alert success">
                    <i class="fa-solid fa-plus"></i> Add Book
                </a>
            </div>

            <!-- FILTER BAR -->
            <div class="filter-bar">
                <input type="text" id="search" placeholder="Search title, author or ISBN…">
                <select id="category">
                    <option value="0">All Categories</option>
                    <?php
                    $cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($cats as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filter">
                    <option value="all">All</option>
                    <option value="available">Available</option>
                    <option value="out">Unavailable</option>
                </select>
                <select id="sort">
                    <option value="name">Sort: Title</option>
                    <option value="author">Sort: Author</option>
                    <option value="quantity">Sort: Quantity</option>
                    <option value="category">Sort: Category</option>
                </select>
            </div>

            <!-- BOOK LIST -->
            <div id="booksContainer" class="book-list">
                <div class="bl-empty"><i class="fa-solid fa-book"></i>Loading books…</div>
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
        const filter   = document.getElementById('filter').value;
        const sort     = document.getElementById('sort').value;
        const params   = new URLSearchParams({ page, search, category, filter, sort });

        fetch(`ajax/books-fetch.php?${params}`)
            .then(r => r.json())
            .then(data => {
                const container  = document.getElementById('booksContainer');
                const pagination = document.getElementById('pagination');

                if (!data.books || data.books.length === 0) {
                    container.innerHTML = `<div class="bl-empty">
                        <i class="fa-solid fa-box-open"></i>No books found.
                    </div>`;
                    pagination.innerHTML = '';
                    return;
                }

                container.innerHTML = data.books.map(b => {
                    const cover = b.image
                        ? `<img src="../ajax/book-cover.php?file=${encodeURIComponent(b.image)}" alt="Cover">`
                        : `<img src="../assets/img/no-book.png">`;

                    const qty = b.quantity > 0
                        ? `<span class="qty-badge available"><i class="fa-solid
                        fa-circle-check"></i> ${b.quantity} copies available</span>`
                        : `<span class="qty-badge out"><i class="fa-solid
                        fa-circle-xmark"></i> Not Available</span>`;

                    return `
                    <div class="bl-card" data-id="${b.id}">
                        <div class="bl-top">
                            <div class="bl-cover">${cover}</div>
                            <div class="bl-info">
                                <div class="bl-title">${esc(b.book_name)}</div>
                                <div class="bl-author">Author: ${esc(b.author_name)}</div>
                                <div class="bl-tags">
                                    <span class="bl-tag"><i class="fa-solid fa-tag"></i>${esc(b.category_name)}</span>
                                    <span class="bl-tag"><i class="fa-solid
                                    fa-barcode"></i>${esc(b.isbn)}</span>
                                    <span class="bl-tag-divider">|</span>
                                    ${qty}
                                </div>
                            </div>
                        </div>
                        <div class="bl-actions">
                            ${b.has_digital ? `<a href="view-book.php?id=${b.id}" class="bl-btn view">
                                <i class="fa-solid fa-eye"></i> View
                            </a>` : ''}
                            <a href="edit-book.php?id=${b.id}" class="bl-btn edit">
                                <i class="fa-solid fa-pen"></i> Edit
                            </a>
                            <button class="bl-btn del delete-book" data-id="${b.id}" title="Delete">
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
                document.getElementById('booksContainer').innerHTML =
                    `<div class="bl-empty" style="color:var(--danger)"><i class="fa-solid fa-triangle-exclamation"></i>Load error: ${esc(err.message)}</div>`;
            });
    }

    document.getElementById('search').addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => { page = 1; loadBooks(); }, 300);
    });
    ['category','filter','sort'].forEach(id =>
        document.getElementById(id).addEventListener('change', () => { page = 1; loadBooks(); })
    );
    function changePage(p) { page = p; loadBooks(); }

    /* DELETE */
    document.addEventListener('click', e => {
        const btn = e.target.closest('.delete-book');
        if (!btn) return;
        const id = btn.dataset.id;
        showConfirm({
            title: 'Delete Book?',
            message: 'This will permanently delete the book and cannot be undone.',
            confirmText: 'Delete',
            onConfirm: () => {
                fetch('ajax/delete-book.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ book_id: id, csrf_token: csrf })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        btn.closest('.bl-card').remove();
                        showToast('Book deleted.', 'success');
                    } else {
                        showToast(data.error || 'Delete failed', 'error');
                    }
                })
                .catch(() => showToast('Server error', 'error'));
            }
        });
    });

    loadBooks();
    </script>
    <?php include '../includes/_footer.php'; ?>
</body>
</html>
