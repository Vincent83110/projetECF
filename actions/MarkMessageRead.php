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
    echo json_encode(['error' => 'Données manquantes']);
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

    echo json_encode([
        'success' => true,
        'modified_count' => $unreadCount
    ]);

    // Après la mise à jour dans MongoDB
    if ($unreadCount > 0) {
        // Mettre à jour le cache côté client si nécessaire
        header('Cache-Control: no-store');
    }

    echo json_encode([
        'success' => true,
        'unread_count' => $unreadCount // Retourne le nombre de messages marqués comme lus
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}