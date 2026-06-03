<?php
/* =============================================
   DLMS - Student Auth Guard
   ============================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    $loginUrl = (strpos($_SERVER['PHP_SELF'], '/student/') !== false)
        ? '../index.php'
        : 'index.php';
    header("Location: $loginUrl");
    exit;
}
