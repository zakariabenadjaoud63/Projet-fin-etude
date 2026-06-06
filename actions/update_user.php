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
$role = trim($_POST['role'] ?? 'client');
$garages_id = !empty($_POST['garages_id']) ? intval($_POST['garages_id']) : null;

if (!in_array($role, ['client', 'mecano', 'admin'], true) || $user_id <= 0) {
    header('Location: ../espace_admin.php?erreur=utilisateur_invalide#admin-users');
    exit();
}

if ($user_id === intval($_SESSION['user_id']) && $role !== 'admin') {
    header('Location: ../espace_admin.php?erreur=role_admin_requis#admin-users');
    exit();
}

$stmt = $pdo->prepare("UPDATE utilisateurs SET role = :role, garages_id = :garages_id WHERE id = :id");
$stmt->execute([
    ':role' => $role,
    ':garages_id' => $garages_id,
    ':id' => $user_id
]);

header('Location: ../espace_admin.php?ok=user#admin-users');
exit();
