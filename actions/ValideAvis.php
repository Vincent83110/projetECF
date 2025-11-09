<?php
// Inclusion des fichiers nécessaires pour l'authentification, configuration et protection CSRF
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/csrf.php';
include __DIR__ . '/../includes/auth.php';

// Vérification que la méthode de requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

// Vérification du token CSRF pour prévenir les attaques
if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
    die("Erreur CSRF : action non autorisée !");
}

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Traitement des actions sur les avis (valider ou supprimer)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_avis'], $_POST['action'])) {
        $id = (int)$_POST['id_avis']; // Conversion en entier pour sécurité
        $action = $_POST['action'];

        // Action de validation d'un avis
        if ($action === 'valider') {
            $stmt = $pdo->prepare("UPDATE avis SET statut = 'valide' WHERE id = :id");
            $stmt->execute([':id' => $id]);
        } 
        // Action de suppression d'un avis
        elseif ($action === 'supprimer') {
            $stmt = $pdo->prepare("DELETE FROM avis WHERE id = :id");
            $stmt->execute([':id' => $id]);
        }
    }
    
    // Retour à la même page que celle d'où provient la requête
    // Récupération du numéro de page pour maintenir la pagination
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    header("Location: " . BASE_URL . "/pages/AvisEmployésTotal.php?page=$page");
    exit;

} catch (PDOException $e) {
    // Gestion des erreurs de connexion à la base de données
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}