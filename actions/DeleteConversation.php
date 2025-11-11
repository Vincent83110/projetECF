<?php
// Importation de MongoDB
require_once __DIR__ . '/../includes/Config.php';
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

// Récupération des données (version compatible JSON et form-data)
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$currentUserId = $_SESSION['user']['id'];
$otherUserId = $data['user_id'] ?? null;

// Vérification de la présence de l'ID utilisateur
if (!$otherUserId) {
    echo json_encode(['success' => false, 'error' => 'ID utilisateur manquant']);
    exit;
}

try {
    // Connexion à MongoDB
    $mongo = new MongoDB\Client($mongoUri);
    $db = $mongo->eco_ride;
    
    // 1. Suppression des messages entre les deux utilisateurs
    $deleteResult = $db->messages->deleteMany([
        '$or' => [
            ['sender_id' => $currentUserId, 'receiver_id' => $otherUserId],
            ['sender_id' => $otherUserId, 'receiver_id' => $currentUserId]
        ]
    ]);
    
    // Retour du résultat de la suppression
    echo json_encode([
        'success' => true,
        'deletedCount' => $deleteResult->getDeletedCount()
    ]);

} catch (Exception $e) {
    // Gestion des erreurs
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}