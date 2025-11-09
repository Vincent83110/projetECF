<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pseudo = $_GET['username'] ?? '';

    // Vérification de la présence du pseudo
    if (!$pseudo) {
        echo json_encode(['success' => false, 'error' => 'Aucun pseudo fourni']);
        exit;
    }

    // Récupération du statut utilisateur (insensible à la casse)
    $stmt = $pdo->prepare("SELECT statut FROM utilisateurs WHERE LOWER(username) = LOWER(:username)");
    $stmt->execute([':username' => $pseudo]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable ptn']);
        exit;
    }

    echo json_encode(['success' => true, 'statut' => $result['statut']]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}