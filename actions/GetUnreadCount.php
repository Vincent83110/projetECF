<?php
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

// Vérification de la session utilisateur
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['unread' => 0]);
    exit;
}

$userId = $_SESSION['user']['id'];

// Connexion à MongoDB
$mongo = new MongoDB\Client("mongodb+srv://pierrevincent720_db_user:Fromage300%40@eco-ride-cluster.7yd4ukm.mongodb.net/eco_ride");
$collection = $mongo->eco_ride->messages;

// Comptage des messages non lus pour l'utilisateur courant
$unreadCount = $collection->countDocuments([
    'receiver_id' => $userId,
    'is_read' => false
]);

echo json_encode(['unread' => $unreadCount]);