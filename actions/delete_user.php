<?php
session_start();
require_once '../connexion_bd.php';
require_once '../includes/security.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../espace_admin.php#admin-users');
    exit();
}

require_csrf();

$user_id = intval($_POST['user_id'] ?? 0);

if ($user_id <= 0 || $user_id === intval($_SESSION['user_id'])) {
    header('Location: ../espace_admin.php?erreur=suppression_refusee#admin-users');
    exit();
}

$stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
$stmt->execute([':id' => $user_id]);

header('Location: ../espace_admin.php?ok=user_supprime#admin-users');
exit();
