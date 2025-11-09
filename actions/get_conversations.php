<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
session_start();
$currentUserId = $_SESSION['user']['id'];

// Connexion à MongoDB et PostgreSQL
$mongo = new MongoDB\Driver\Manager("mongodb://localhost:27017");
$pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Pipeline d'aggrégation pour récupérer les dernières conversations avec le nombre de messages non lus
$pipeline = [
    ['$match' => [
        '$or' => [
            ['sender_id' => $currentUserId],
            ['receiver_id' => $currentUserId]
        ]
    ]],
    ['$sort' => ['timestamp' => -1]],
    ['$group' => [
        '_id' => [
            'other_user' => [
                '$cond' => [
                    ['$eq' => ['$sender_id', $currentUserId]],
                    '$receiver_id',
                    '$sender_id'
                ]
            ]
        ],
        'last_message' => ['$first' => '$$ROOT'],
        'unread_count' => [
            '$sum' => [
                '$cond' => [
                    ['$and' => [
                        ['$eq' => ['$receiver_id', $currentUserId]],
                        ['$eq' => ['$is_read', false]]
                    ]],
                    1,
                    0
                ]
            ]
        ]
    ]]
];

// Exécution de la commande d'aggrégation MongoDB
$command = new MongoDB\Driver\Command([
    'aggregate' => 'messages',
    'pipeline' => $pipeline,
    'cursor' => new stdClass,
]);
$rows = $mongo->executeCommand('eco_ride', $command);

$conversations = [];
foreach ($rows as $row) {
    $otherId = $row->_id->other_user;
    
    // Recherche de l'utilisateur dans les tables users puis utilisateurs
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ? 
                          UNION SELECT id, username FROM utilisateurs WHERE id = ? 
                          LIMIT 1");
    $stmt->execute([$otherId, $otherId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $lastMessage = $row->last_message;
        $timestamp = $lastMessage->timestamp->toDateTime();
        $timestamp->setTimezone(new DateTimeZone('Europe/Paris'));
        
        $conversations[] = [
            "id" => $user['id'],
            "username" => $user['username'],
            "last_message" => $lastMessage->message,
            "last_message_time" => $timestamp->format('Y-m-d H:i:s'),
            "unread_count" => $row->unread_count,
            "is_you" => $lastMessage->sender_id == $currentUserId,
            "photo" => BASE_URL ."/assets/images/profil.svg"
        ];
    }
}

// Tri des conversations par date du dernier message (plus récent en premier)
usort($conversations, function($a, $b) {
    return strtotime($b['last_message_time']) - strtotime($a['last_message_time']);
});

echo json_encode($conversations);