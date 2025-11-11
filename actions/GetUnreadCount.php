<?php
require_once __DIR__ . '/../includes/Config.php';
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

// Vérification de la session utilisateur
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['unread' => 0]);
    exit;
}

$userId = $_SESSION['user']['id'];

// Connexion à MongoDB
$mongo = new MongoDB\Client($mongoUri);
    // Sélection de la collection messages
$collection = $mongo->eco_ride->messages;

// Comptage des messages non lus pour l'utilisateur courant
$unreadCount = $collection->countDocuments([
    'receiver_id' => $userId,
    'is_read' => false
]);

echo json_encode(['unread' => $unreadCount]);