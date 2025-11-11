<?php
// Inclusion de l'autoloader Composer et de la protection CSRF
require_once __DIR__ . '/../includes/Config.php';
require_once __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../includes/Csrf.php';

header('Content-Type: application/json; charset=utf-8');

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user']['id'])) {
    http_response_code(401); // Non autorisé
    echo json_encode(['error' => 'Non connecté']);
    exit;
}

// Vérification que la méthode est bien POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Méthode non autorisée
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupération des données JSON
$data = json_decode(file_get_contents('php://input'), true);

// Vérification du token CSRF
if (!isset($data['csrf_token']) || !csrf_check($data['csrf_token'])) {
    http_response_code(403); // Interdit
    echo json_encode(['error' => 'Erreur CSRF : action non autorisée !']);
    exit;
}

// Récupération des données selon le type de contenu
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    // Données JSON
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    // Données POST standard
    $data = $_POST;
}

// Vérification des données obligatoires
if (empty($data['receiver_id']) || empty(trim($data['message']))) {
    http_response_code(400); // Mauvaise requête
    echo json_encode(['error' => 'Données manquantes ou invalides']);
    exit;
}

// Récupération des données du message
$senderId = $_SESSION['user']['id']; // ID de l'expéditeur depuis la session
$receiverId = (int)$data['receiver_id']; // ID du destinataire
$message = trim($data['message']); // Message nettoyé

// Vérification que le destinataire existe dans users ou utilisateurs
$pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
$stmt = $pdo->prepare("SELECT 1 FROM users WHERE id = ? UNION SELECT 1 FROM utilisateurs WHERE id = ? LIMIT 1");
$stmt->execute([$receiverId, $receiverId]);

// Si le destinataire n'existe pas
if (!$stmt->fetch()) {
    http_response_code(404); // Non trouvé
    echo json_encode(['error' => 'Destinataire introuvable']);
    exit;
}

// Connexion et insertion dans MongoDB
try {
    // Connexion à MongoDB
    $mongo = new MongoDB\Client($mongoUri);
    // Sélection de la collection messages
    $collection = $mongo->eco_ride->messages;

    // Insertion du message dans MongoDB
    $insertResult = $collection->insertOne([
        'sender_id' => $senderId,        // ID expéditeur
        'receiver_id' => $receiverId,    // ID destinataire
        'message' => $message,           // Contenu du message
        'timestamp' => new MongoDB\BSON\UTCDateTime((int)(microtime(true) * 1000)), // Horodatage actuel en ms
        'is_read' => false               // Message non lu par défaut
    ]);

    // Vérification que l'insertion a réussi
    if ($insertResult->getInsertedCount() !== 1) {
        throw new Exception('Échec de l\'insertion');
    }

    // Réponse de succès
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // Gestion des erreurs MongoDB
    http_response_code(500); // Erreur interne du serveur
    echo json_encode(['error' => 'Erreur MongoDB: ' . $e->getMessage()]);
}