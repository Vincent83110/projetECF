<?php
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}

require_once __DIR__ . '/../vendor/autoload.php';
session_start();

// Vérification des paramètres requis
if (!isset($_SESSION['user']['id']) || !isset($_POST['from_user_id'])) {
    http_response_code(400);
    exit;
}

$receiverId = $_SESSION['user']['id'];
$senderId = (int)$_POST['from_user_id'];

try {
    // Connexion à MongoDB
    $mongo = new MongoDB\Client($mongoUri);
    $collection = $mongo->eco_ride->messages;

    // Compter d'abord les messages non lus
    $unreadCount = $collection->countDocuments([
        'sender_id' => $senderId,
        'receiver_id' => $receiverId,
        'is_read' => false
    ]);

    // Marquer comme lus seulement s'il y en a
    if ($unreadCount > 0) {
        $result = $collection->updateMany(
            [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'is_read' => false
            ],
            ['$set' => ['is_read' => true]]
        );
    }

   // Après la mise à jour dans MongoDB
header('Content-Type: application/json');
header('Cache-Control: no-store');

// Préparer toutes les données à retourner
$response = [
    'success' => true,
    'modified_count' => $unreadCount,
    'unread_count' => $unreadCount // ou tout autre info nécessaire
];

// Retourner le JSON **une seule fois**
echo json_encode($response);
exit;

    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}