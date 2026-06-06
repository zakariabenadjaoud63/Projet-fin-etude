<?php
$host = 'localhost';
$db   = 'meca_speed';
$user = 'root';
$pass = '';

try {
   $pdo = new PDO(
    "mysql:host=127.0.0.1;port=3307;dbname=meca_speed;charset=utf8mb4",
    "root",
    ""
);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connexion échouée : " . $e->getMessage());
}
?>
