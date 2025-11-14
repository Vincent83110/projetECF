<?php
// Démarrage de session pour gestion des erreurs
session_start();

// Inclusion des fichiers de configuration et de protection CSRF
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}

include __DIR__ . '/../includes/Csrf.php';

// Vérification que la méthode de requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Méthode non autorisée.";
    header("Location: " . BASE_URL . "/pages/TrajetIndividuel.php");
    exit;
}

// Vérification CSRF pour prévenir les attaques
if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
    $_SESSION['error'] = "Erreur de sécurité CSRF.";
    header("Location: " . BASE_URL . "/pages/TrajetIndividuel.php");
    exit;
}

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérification de la méthode POST et récupération des données
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $numero_trajet = $_POST['numero_trajet'] ?? null;
        $action = $_POST['action'] ?? null;

        // Validation des données requises
        if (!$numero_trajet || $action !== 'lancer') {
            $_SESSION['error'] = "Données manquantes ou action invalide.";
            header("Location: " . BASE_URL . "/pages/TrajetIndividuel.php");
            exit;
        }

        // Met à jour le statut du trajet à 'en_cours'
        $stmt = $pdo->prepare("UPDATE infos_trajet SET statut = 'en_cours' WHERE numero_trajet = :numero_trajet");
        $stmt->execute([':numero_trajet' => $numero_trajet]);

        // Récupère l'ID du trajet à partir du numéro
        $stmtGetId = $pdo->prepare("SELECT id FROM infos_trajet WHERE numero_trajet = :numero_trajet");
        $stmtGetId->execute([':numero_trajet' => $numero_trajet]);
        $trajet = $stmtGetId->fetch(PDO::FETCH_ASSOC);

        if ($trajet) {
            $trajet_id = $trajet['id'];

            // Met à jour les réservations associées à ce trajet
            $stmtUpdateReservations = $pdo->prepare("UPDATE reservation SET statut = 'en_cours' WHERE trajet_id = :trajet_id");
            $stmtUpdateReservations->execute([':trajet_id' => $trajet_id]);
        }

        // Redirection vers la page du trajet individuel
        header("Location: " . BASE_URL . "/pages/TrajetIndividuel.php?numero_trajet=" . urlencode($numero_trajet));
        exit;
    } else {
        $_SESSION['error'] = "Méthode non autorisée.";
        header("Location: " . BASE_URL . "/pages/TrajetIndividuel.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de connexion à la base de données : " . $e->getMessage();
    header("Location: " . BASE_URL . "/pages/TrajetIndividuel.php");
    exit;
}