<?php
/* =============================================
   DLMS - Admin Auth Guard
   ============================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_id'])) {
    $loginUrl = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false)
        ? '../admin.php'
        : 'admin.php';
    header("Location: $loginUrl");
    exit;
}
