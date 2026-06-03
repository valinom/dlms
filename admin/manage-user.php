<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Manage Users | DLMS</title>
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
        .filter-bar input{
            min-width:350px;
        }
    </style>
</head>

<body>
    <?php include 'includes/admin-header.php'; ?>
    <?php include '../includes/_toast.php'; ?>

    <main class="main">
        <div class="page-container-books">

            <h2 class="page-title">Manage Students</h2>

            <!-- FILTER BAR -->
            <div class="filter-bar">
                <input type="text" id="search" placeholder="Search name, ID, email or department">

                <select id="status">
                    <option value="">All</option>
                    <option value="1">Active</option>
                    <option value="0">Blocked</option>
                </select>

                <select id="sort">
                    <option value="name">Sort by Name</option>
                    <option value="department">Sort by Department</option>
                </select>
            </div>

            <!-- USERS -->
            <div id="users" class="user-list"></div>
            <div id="pagination" class="pagination"></div>


        </div>
    </main>

    <script src="../assets/js/dlms-dialogs.js"></script>
    <script>
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const usersBox = document.getElementById('users');
        const paginationBox = document.getElementById('pagination');

        const searchInput = document.getElementById('search');
        const statusSelect = document.getElementById('status');
        const sortSelect = document.getElementById('sort');

        let currentPage = 1;
        let debounce = null;

        /* INITIAL LOAD WITH URL PARAM */
        const params = new URLSearchParams(window.location.search);
        const initialId = params.get('id');

        if (initialId) {
            searchInput.value = initialId;
            fetchUsers();

            // clean URL so search works normally afterward
            history.replaceState({}, '', window.location.pathname);
        } else {
            fetchUsers();
        }

        /* EVENTS */
        searchInput.addEventListener('input', resetAndFetch);
        statusSelect.addEventListener('change', resetAndFetch);
        sortSelect.addEventListener('change', resetAndFetch);

        function resetAndFetch() {
            currentPage = 1;
            triggerFetch();
        }

        function triggerFetch() {
            clearTimeout(debounce);
            debounce = setTimeout(fetchUsers, 300);
        }

        /* FETCH */
        function fetchUsers() {

            const q = searchInput.value.trim();
            const status = statusSelect.value;
            const sort = sortSelect.value;

            const url = `ajax/users-fetch.php?q=${encodeURIComponent(q)}&status=${status}&sort=${sort}&page=${currentPage}`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    renderUsers(data.users);
                    renderPagination(data.totalPages, data.currentPage);
                })
                .catch(() => {
                    usersBox.innerHTML = '<p>Error loading users</p>';
                });
        }

        /* RENDER USERS */
        function renderUsers(users) {

            if (!users.length) {
                usersBox.innerHTML = '<p>No users found.</p>';
                return;
            }

            usersBox.innerHTML = users.map(u => `
        <div class="user-card ${u.status == 0 ? 'blocked' : ''}">
            <div class="user-header">
                <div>
                    <strong>${escapeHTML(u.fullname)}</strong>
                    <div class="muted">${escapeHTML(u.user_id)}</div>
                </div>
                <div>
                <span class="${u.status == 1 ? 'returned' : 'overdue'}">
                    ${u.status == 1 ? 'Active' : 'Blocked'}
                </span>
                </div>
            </div>

            <div class="user-body">
                <div>${escapeHTML(u.email)}</div>
                <div>${escapeHTML(u.mobile)}</div>
                <div class="muted">${escapeHTML(u.department)}</div>

                <div class="stats">
                    <span>Issued: <strong>${u.books_issued}</strong></span>
                    <span>Returned: <strong>${u.books_returned}</strong></span>
                </div>
            </div>

            <div class="user-actions">
                <a href="edit-user.php?id=${u.id}" class="alert info"><i class="fa-solid fa-pen"></i>Edit</a>
                <button class="alert warning" onclick="toggleUser(${u.id})">
                    ${u.status == 1 ? '<i class="fa-solid fa-ban"></i> Block' : '<i class="fa-solid fa-unlock"></i>Unblock'}
                </button>
                <button class="alert danger" onclick="deleteUser(${u.id})"><i class="fa-solid fa-trash"></i></button>
            </div>
        </div>
    `).join('');
        }

        /* Actions */
        function toggleUser(id) {
            showConfirm({
                title: 'Change User Status?',
                message: 'This will block or unblock the user.',
                type: 'warning',
                confirmText: 'Yes, Change',
                onConfirm: () => {
                    fetch('ajax/toggle-user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    user_id: id,
                    csrf_token: csrf
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('User status updated.', 'success');
                        fetchUsers();
                    } else {
                        showToast(data.error || 'Action failed', 'error');
                    }
                })
                .catch(() => showToast('Server error', 'error'));
                } // onConfirm
            }); // showConfirm
        }


        function deleteUser(id) {
            showConfirm({
                title: 'Delete User?',
                message: 'This will permanently delete the user and all their issued book history.',
                confirmText: 'Delete',
                onConfirm: () => {
                    fetch('ajax/delete-user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    user_id: id,
                    csrf_token: csrf
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('User deleted successfully.', 'success');
                        fetchUsers();
                    } else {
                        showToast(data.error || 'Delete failed', 'error');
                    }
                })
                .catch(() => showToast('Server error', 'error'));
                } // onConfirm
            }); // showConfirm
        }

        /* PAGINATION */
        function renderPagination(totalPages, current) {

            if (totalPages <= 1) {
                paginationBox.innerHTML = '';
                return;
            }

            let html = '';

            for (let i = 1; i <= totalPages; i++) {
                html += `
            <button
                class="alert ${i === current ? 'info' : ''}"
                onclick="goToPage(${i})">
                ${i}
            </button>
        `;
            }

            paginationBox.innerHTML = html;
        }

        function goToPage(page) {
            currentPage = page;
            fetchUsers();
        }

        /* HELPERS */
        function escapeHTML(str) {
            return String(str).replace(/[&<>"']/g, s =>
                ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s])
            );
        }
    </script>
    <?php include '../includes/_footer.php'; ?>
</body>

</html>