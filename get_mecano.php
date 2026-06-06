<?php
session_start();
require_once 'connexion_bd.php';

header('Content-Type: application/json; charset=utf-8');

$garage_id = intval($_GET['garage_id'] ?? 0);

if ($garage_id <= 0) {
    echo json_encode(['success' => false, 'mecanos' => []]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT id, nom
        FROM utilisateurs
        WHERE role = 'mecano' AND garages_id = :garage_id
        ORDER BY nom ASC
    ");
    $stmt->execute([':garage_id' => $garage_id]);
    echo json_encode(['success' => true, 'mecanos' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'mecanos' => [], 'message' => $e->getMessage()]);
}
exit();
?>
