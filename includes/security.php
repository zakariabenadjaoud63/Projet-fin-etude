<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

function require_csrf() {
    $sent = $_POST['csrf_token'] ?? '';
    $known = $_SESSION['csrf_token'] ?? '';
    if (!$sent || !$known || !hash_equals($known, $sent)) {
        http_response_code(403);
        die('Requete non autorisee.');
    }
}

function redirect_role_home() {
    $role = $_SESSION['role'] ?? 'client';
    if ($role === 'mecano') {
        header('Location: espace_mecano.php');
        exit();
    }
    if ($role === 'admin') {
        header('Location: espace_admin.php');
        exit();
    }
    header('Location: espace_client.php');
    exit();
}
