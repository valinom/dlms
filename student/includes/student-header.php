<?php
// Active link helper for student sidebar
$_navPage = basename($_SERVER['PHP_SELF']);
function studentNavActive(string $page): string {
    global $_navPage;
    return $_navPage === $page ? ' nav-active' : '';
}
?>
<nav class="top-nav">
    <button class="menu-btn" id="menuBtn">
        <i class="fa-solid fa-bars"></i>
    </button>
    <h1 class="logo"><i class="fa-solid fa-book-open-reader"></i> DLMS | LIBRARY</h1>
    <span style="margin-left:auto;font-size:.82rem;color:var(--text-muted)">
        <?= e($_SESSION['student_name'] ?? '') ?>
    </span>
</nav>

<aside class="sidebar" id="sidebar">
    <nav class="menu-group">

        <a href="dashboard.php" class="<?= studentNavActive('dashboard.php') ?>">
            <i class="fa-solid fa-gauge"></i> Dashboard
        </a>

        <a href="books.php" class="<?= studentNavActive('books.php') ?>">
            <i class="fa-solid fa-book"></i> Browse Books
        </a>

        <a href="digital-books.php" class="<?= studentNavActive('digital-books.php') ?>">
            <i class="fa-solid fa-computer"></i> Digital Books
        </a>

        <a href="my-books.php" class="<?= studentNavActive('my-books.php') ?>">
            <i class="fa-solid fa-bookmark"></i> My Bookmarks
        </a>

        <a href="issued.php" class="<?= studentNavActive('issued.php') ?>">
            <i class="fa-solid fa-book-open-reader"></i> My Issued Books
        </a>

        <a href="favorites.php" class="<?= studentNavActive('favorites.php') ?>">
            <i class="fa-solid fa-heart"></i> Favorites
        </a>

        <a href="profile.php" class="<?= studentNavActive('profile.php') ?>">
            <i class="fa-solid fa-user"></i> My Profile
        </a>

        <a href="logout.php" class="alert danger" style="margin-top:.5rem">
            <i class="fa-solid fa-right-from-bracket"></i> Log Out
        </a>

    </nav>
</aside>

<script src="../assets/js/header.js"></script>
