<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
session_start();

// Vérification des paramètres requis
if (!isset($_SESSION['user']['id']) || !isset($_GET['with'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Requête invalide.']);
    exit;
}

$userId = $_SESSION['user']['id'];
$with = (int)$_GET['with'];

// Connexion à MongoDB et PostgreSQL
$mongo = new MongoDB\Client("mongodb://localhost:27017");
$collection = $mongo->eco_ride->messages;

$pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Vérification dans quelle table se trouve l'utilisateur
$stmt = $pdo->prepare("SELECT 'users' as table_name FROM users WHERE id = ? 
                       UNION SELECT 'utilisateurs' as table_name FROM utilisateurs WHERE id = ?");
$stmt->execute([$with, $with]);
$userTable = $stmt->fetchColumn();

// Récupération des messages entre les deux utilisateurs
$messages = $collection->find([
    '$or' => [
        ['sender_id' => $userId, 'receiver_id' => $with],
        ['sender_id' => $with, 'receiver_id' => $userId]
    ]
], ['sort' => ['timestamp' => 1]]); // Tri par timestamp croissant

$result = [];
foreach ($messages as $msg) {
    // Chercher le nom de l'expéditeur dans la bonne table
    $tableToCheck = ($msg['sender_id'] == $with) ? $userTable : 'users'; 
    $stmt = $pdo->prepare("SELECT username FROM $tableToCheck WHERE id = ?");
    $stmt->execute([$msg['sender_id']]);
    $senderUsername = $stmt->fetchColumn();

    $result[] = [
        'id' => (string)$msg['_id'],
        'message' => $msg['message'],
        'sender_id' => $msg['sender_id'],
        'timestamp' => $msg['timestamp']->toDateTime()->format('c'),
        'sender_username' => $senderUsername
    ];
}

echo json_encode($result);