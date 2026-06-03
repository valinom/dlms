<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Issued Books | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="<?= csrf_token() ?>">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <link rel="stylesheet" href="../assets/css/libook.css">
    <link rel="stylesheet" href="assets/css/book.css">
    <link rel="stylesheet" href="assets/css/adminui.css">
</head>

<body>
    <?php include('includes/admin-header.php'); ?>
    <?php include '../includes/_toast.php'; ?>

    <main class="main">
        <div class="page-container">

            <h2 class="page-title">Issued Books</h2>

            <div class="filter-bar">
                <input type="text" id="issueSearch" placeholder="Search student, book, ISBN or ID…" autocomplete="off">
            </div>

            <div id="issuedList" class="issued-list"></div>
            <div id="pagination" class="pagination"></div>

        </div>
    </main>

    <script src="../assets/js/dlms-dialogs.js"></script>
    <script>
        const issuedList   = document.getElementById('issuedList');
        const paginationBox = document.getElementById('pagination');
        const searchInput  = document.getElementById('issueSearch');

        let currentPage = 1;
        let debounce    = null;

        fetchIssued();

        searchInput.addEventListener('input', () => {
            currentPage = 1;
            clearTimeout(debounce);
            debounce = setTimeout(fetchIssued, 300);
        });

        function fetchIssued() {
            const q = searchInput.value.trim();
            const url = `ajax/issued-fetch.php?q=${encodeURIComponent(q)}&page=${currentPage}`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    renderCards(data.rows);
                    renderPagination(data.totalPages, data.page);
                })
                .catch(() => {
                    issuedList.innerHTML = '<p>Error loading results.</p>';
                });
        }

        function formatDate(dateStr) {
            if (!dateStr) return '—';
            return new Date(dateStr).toLocaleDateString('en-GB', {
                day: '2-digit', month: 'short', year: 'numeric'
            });
        }

        function renderCards(rows) {
            if (!rows.length) {
                issuedList.innerHTML = '<p>No issued books found.</p>';
                return;
            }

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            issuedList.innerHTML = rows.map(row => {
                const returnDate = new Date(row.return_date);
                returnDate.setHours(0, 0, 0, 0);

                const isOverdue  = row.status === 'issued' && returnDate < today;
                const isDueToday = row.status === 'issued' && returnDate.getTime() === today.getTime();

                const badge =
                    row.status === 'returned'
                        ? '<span class="returned">Returned</span>'
                        : isOverdue
                            ? '<span class="overdue">Overdue</span>'
                            : isDueToday
                                ? '<span class="due-today">Due Today</span>'
                                : '<span class="issued">Issued</span>';

                const returnedLine = (row.status === 'returned' && row.returned_at)
                    ? `<span class="returnedDate">Returned: ${formatDate(row.returned_at)}</span>`
                    : '';

                const actions = row.status === 'issued'
                    ? `<div class="issued-actions">
                            <a href="return-book.php?id=${row.id}" class="alert warning"
                               onclick="dlmsReturnConfirm(event, this)">Return Book</a>
                            <a href="manage-user.php?id=${encodeURIComponent(row.user_id)}"
                               class="alert info" title="View student profile">
                                <i class="fa-solid fa-user-magnifying-glass"></i> Find Student
                            </a>
                       </div>`
                    : '';

                return `
                <div class="issued-card">
                    <div class="issued-header">
                        <div>
                            <a href="manage-user.php?id=${encodeURIComponent(row.user_id)}"
                               class="student-link" title="Find in Manage Users">
                                <strong>${escapeHTML(row.fullname)}</strong>
                            </a>
                            <div class="muted">${escapeHTML(row.user_id)}</div>
                        </div>
                        ${badge}
                    </div>

                    <div class="issued-body">
                        <div class="book-name">
                            ${escapeHTML(row.book_name)}
                            <span class="muted">(${escapeHTML(row.isbn)})</span>
                        </div>
                        <div class="dates">
                            <span>Issued: ${formatDate(row.issue_date)}</span>
                            <span>Due: ${formatDate(row.return_date)}</span>
                            ${returnedLine}
                        </div>
                    </div>

                    ${actions}
                </div>`;
            }).join('');
        }

        function renderPagination(totalPages, current) {
            if (totalPages <= 1) {
                paginationBox.innerHTML = '';
                return;
            }

            let html = '';
            for (let i = 1; i <= totalPages; i++) {
                html += `<button class="alert ${i === current ? 'info' : ''}"
                            onclick="goToPage(${i})">${i}</button>`;
            }
            paginationBox.innerHTML = html;
        }

        function goToPage(page) {
            currentPage = page;
            fetchIssued();
        }

        function dlmsReturnConfirm(e, el) {
            e.preventDefault();
            const href = el.href || el.closest('a')?.href;
            showConfirm({
                title: 'Mark as Returned?',
                message: 'This will mark the book as returned.',
                type: 'info',
                confirmText: 'Yes, Return',
                cancelText: 'Cancel',
                onConfirm: () => { window.location.href = href; }
            });
        }

        function escapeHTML(str) {
            return String(str).replace(/[&<>"']/g, s =>
                ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s])
            );
        }
    </script>
    <?php include '../includes/_footer.php'; ?>
</body>

</html>