<?php
// Inclusion des fichiers de configuration et de sécurité
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/csrf.php';

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    die("Accès refusé.");
}


// Vérification que la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
    die("Erreur CSRF : action non autorisée !");
}

try {
    // Connexion à la base de données
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Traitement de l'annulation
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Récupération des données
        $reservationId = $_POST['reservation_id'] ?? null;
        $userId = $_SESSION['user']['user_id']; // Note : utilisation de user_id et non id

        // Debug : affichage des données reçues
        echo "reservation_id reçu : " . htmlspecialchars($reservationId) . "<br>";
        echo "user_id en session : " . htmlspecialchars($userId) . "<br>";

        // Vérification des données obligatoires
        if (!$reservationId) {
            die("Données manquantes.");
        }

        // Vérification que la réservation appartient à l'utilisateur et récupération des infos
        $stmtCheck = $pdo->prepare("
            SELECT r.nombre_places, r.trajet_id, t.prix 
            FROM reservation r 
            JOIN infos_trajet t ON r.trajet_id = t.id 
            WHERE r.id = :reservation_id AND r.user_id = :user_id
        ");
        $stmtCheck->execute([
            ':reservation_id' => $reservationId,
            ':user_id' => $userId
        ]);

        // Récupération des données de la réservation
        $reservation = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        // Vérification que la réservation existe et appartient à l'utilisateur
        if (!$reservation) {
            die("Réservation introuvable ou accès non autorisé.");
        }

        // Calcul du montant à rembourser (places * prix par place)
        $montantARembourser = $reservation['nombre_places'] * $reservation['prix'];

        // 1. Suppression de la notification liée à la réservation
        $stmtDeleteNotif = $pdo->prepare("DELETE FROM notifications WHERE reservation_id = :reservation_id");
        $stmtDeleteNotif->execute([':reservation_id' => $reservationId]);

        // 2. Réinjection des places annulées dans le trajet
        $stmtMajPlaces = $pdo->prepare("UPDATE infos_trajet SET nombre_place = nombre_place + :places WHERE id = :trajet_id");
        $stmtMajPlaces->execute([':places' => $reservation['nombre_places'], ':trajet_id' => $reservation['trajet_id']]);

        // 3. Suppression de la réservation
        $stmtDelete = $pdo->prepare("DELETE FROM reservation WHERE id = :id");
        $stmtDelete->execute([':id' => $reservationId]);

        // 4. Remboursement des crédits à l'utilisateur
        $stmtUpdate = $pdo->prepare("UPDATE utilisateurs SET credits = credits + :montant WHERE id = :user_id");
        $stmtUpdate->execute([':montant' => $montantARembourser, ':user_id' => $userId]);

        // 5. Mise à jour de la session utilisateur avec les nouveaux crédits
        $_SESSION['user']['credits'] += $montantARembourser;

        // Redirection avec confirmation de succès
        header("Location: " .BASE_URL. "/pages/TrajetIndividuel.php?cancel_success=1");
        exit;
    } else {
        die("Méthode non autorisée.");
    }
} catch (PDOException $e) {
    // Gestion des erreurs
    die("Erreur : " . $e->getMessage());
}