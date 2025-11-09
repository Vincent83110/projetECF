<?php
include __DIR__ . '/../actions/connexion.php'; // Fichier de connexion à la base de données

// Vérification de la présence de l'ID utilisateur
if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

$id = (int) $_GET['id'];

// Récupération du nom d'utilisateur depuis la table utilisateurs
$stmt = $pdo->prepare("SELECT username FROM utilisateurs WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Retour du résultat ou valeur par défaut
echo json_encode($user ?: ['username' => 'Inconnu']);