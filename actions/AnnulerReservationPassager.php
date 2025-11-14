<?php
// Inclusion des fichiers de configuration et de sécurité
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}

include __DIR__ . '/../includes/Csrf.php';

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "Accès refusé.";
    header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php");
    exit;
}


// Vérification que la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Méthode non autorisée.";
    header("Location: " . BASE_URL . "/pages/TrajetIndividuel.php");
    exit;
}

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
    $_SESSION['error'] = "Erreur CSRF : action non autorisée !";
    header("Location: " . BASE_URL . "/pages/TrajetIndividuel.php");
    exit;
}

try {
    // Connexion à la base de données
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Traitement de l'annulation
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Récupération des données
        $reservationId = $_POST['reservation_id'] ?? null;
        $userId = $_SESSION['user']['id'];

        // Vérification des données obligatoires
        if (!$reservationId) {
            $_SESSION['error'] = "Données manquantes.";
            header("Location: " .BASE_URL. "/pages/TrajetIndividuel.php");
            exit;
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
            $_SESSION['error'] = "Réservation introuvable ou accès non autorisé.";
            header("Location: " .BASE_URL. "/pages/TrajetIndividuel.php");
            exit;
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
        $_SESSION['error'] = "Méthode non autorisée.";
        header("Location: " .BASE_URL. "/pages/TrajetIndividuel.php");
        exit;
    }
} catch (PDOException $e) {
    // Gestion des erreurs
    $_SESSION['error'] = "Erreur lors de l'annulation : " . $e->getMessage();
    header("Location: " .BASE_URL. "/pages/TrajetIndividuel.php");
    exit;
}