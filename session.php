<?php
// includes/session.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function checkRole($allowed_roles) {
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
        header("Location: dashboard.php?error=AccessDenied");
        exit();
    }
}
