<?php
// Active link helper for admin sidebar
$_navPage  = basename($_SERVER['PHP_SELF']);
$_navQuery = $_SERVER['QUERY_STRING'] ?? '';
function adminNavActive(string $page): string {
    global $_navPage;
    return $_navPage === $page ? ' nav-active' : '';
}
function adminDropdownOpen(array $pages): string {
    global $_navPage;
    return in_array($_navPage, $pages) ? ' open' : '';
}
?>
<nav class="top-nav">
    <button class="menu-btn" id="menuBtn">
        <i class="fa-solid fa-bars"></i>
    </button>
    <h1 class="logo"><i class="fa-solid fa-book-open-reader"></i> DLMS | ADMIN</h1>
</nav>

<aside class="sidebar" id="sidebar">
    <nav class="menu-group">

        <a href="dashboard.php" class="<?= adminNavActive('dashboard.php') ?>">
            <i class="fa-solid fa-gauge"></i> Dashboard
        </a>

        <!-- Books -->
        <div class="menu-dropdown<?= adminDropdownOpen(['manage-books.php','add-book.php','category.php']) ?>">
            <button class="dropdown-toggle">
                <i class="fa-solid fa-book"></i> Books
                <i class="fa-solid fa-chevron-down arrow"></i>
            </button>
            <div class="dropdown-menu">
                <a href="manage-books.php" class="<?= adminNavActive('manage-books.php') ?>">Manage Books</a>
                <a href="add-book.php"      class="<?= adminNavActive('add-book.php') ?>">Add Physical Books</a>
                <a href="category.php"      class="<?= adminNavActive('category.php') ?>">Category</a>
            </div>
        </div>

        <!-- Digital Books -->
        <div class="menu-dropdown<?= adminDropdownOpen(['manage-digital-books.php','add-digital-book.php','language.php']) ?>">
            <button class="dropdown-toggle">
                <i class="fa-solid fa-computer"></i> Digital Books
                <i class="fa-solid fa-chevron-down arrow"></i>
            </button>
            <div class="dropdown-menu">
                <a href="manage-digital-books.php" class="<?= adminNavActive('manage-digital-books.php') ?>">Manage Digital Books</a>
                <a href="add-digital-book.php"      class="<?= adminNavActive('add-digital-book.php') ?>">Add Digital Books</a>
                <a href="language.php"              class="<?= adminNavActive('language.php') ?>">Language</a>
            </div>
        </div>

        <!-- Issue / Return -->
        <div class="menu-dropdown<?= adminDropdownOpen(['issue-book.php','issued-books.php','return-book.php']) ?>">
            <button class="dropdown-toggle">
                <i class="fa-solid fa-book-open-reader"></i> Issue / Return
                <i class="fa-solid fa-chevron-down arrow"></i>
            </button>
            <div class="dropdown-menu">
                <a href="issue-book.php"   class="<?= adminNavActive('issue-book.php') ?>">Issue New Book</a>
                <a href="issued-books.php" class="<?= adminNavActive('issued-books.php') ?>">Manage Issued Books</a>
            </div>
        </div>

        <!-- Users -->
        <div class="menu-dropdown<?= adminDropdownOpen(['manage-user.php','add-user.php','edit-user.php','department.php']) ?>">
            <button class="dropdown-toggle">
                <i class="fa-solid fa-users"></i> User
                <i class="fa-solid fa-chevron-down arrow"></i>
            </button>
            <div class="dropdown-menu">
                <a href="manage-user.php" class="<?= adminNavActive('manage-user.php') ?>">Manage User</a>
                <a href="add-user.php"    class="<?= adminNavActive('add-user.php') ?>">Add User</a>
                <a href="department.php"  class="<?= adminNavActive('department.php') ?>">Departments</a>
            </div>
        </div>

        <a href="my-profile.php" class="<?= adminNavActive('my-profile.php') ?>">
            <i class="fa-solid fa-user"></i> My Account
        </a>

        <a href="logout.php" class="alert danger">
            <i class="fa-solid fa-right-from-bracket"></i> Log Out
        </a>

    </nav>
</aside>

<script src="../assets/js/header.js"></script>
<script>
    document.querySelectorAll(".dropdown-toggle").forEach(toggle => {
        toggle.addEventListener("click", e => {
            e.stopPropagation();
            toggle.parentElement.classList.toggle("open");
        });
    });
</script>
