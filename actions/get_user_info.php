<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../actions/connexion.php';
header('Content-Type: application/json');

// Vérification de la présence de l'ID utilisateur
if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

$userId = (int) $_GET['id'];

// Recherche de l'utilisateur dans les deux tables (users et utilisateurs)
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = :id 
                       UNION SELECT username FROM utilisateurs WHERE id = :id 
                       LIMIT 1");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo json_encode(['username' => $user['username']]);
} else {
    echo json_encode(['username' => 'Utilisateur inconnu']);
}