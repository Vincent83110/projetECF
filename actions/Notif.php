<?php
// Initialisation des variables pour les notifications
$notificationCount = 0;
$notifications = [];

// Vérifier si l'utilisateur est connecté avant de tenter toute opération
if (isset($_SESSION['user']) && isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id'])) {
    
    // Inclusion du fichier de configuration de la base de données
    if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}
    

    try {
        // Connexion à la base de données PostgreSQL
        $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Récupération du nombre de notifications pour l'utilisateur connecté
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id");
        $stmtCount->execute([':user_id' => $_SESSION['user']['id']]);
        $notificationCount = $stmtCount->fetchColumn();

        // Récupération complète des notifications avec infos utiles, triées par date de création décroissante
        $stmtNotif = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY date_creation DESC");
        $stmtNotif->execute([':user_id' => $_SESSION['user']['id']]);
        $notifications = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

        // Enrichissement des notifications avec des informations supplémentaires
        foreach ($notifications as &$notif) {
            // Récupération de l'ID du trajet lié à la notification via la réservation
            $stmtTrajet = $pdo->prepare("SELECT trajet_id FROM reservation WHERE id = :reservation_id");
            $stmtTrajet->execute([':reservation_id' => $notif['reservation_id']]);
            $trajet = $stmtTrajet->fetch(PDO::FETCH_ASSOC);
            $notif['trajet_id'] = $trajet['trajet_id'] ?? null;

            
            // Récupération du nom d'utilisateur du passager ayant fait la réservation
            $stmtUser = $pdo->prepare("SELECT u.username FROM reservation r JOIN utilisateurs u ON r.user_id = u.id WHERE r.id = :reservation_id");
            $stmtUser->execute([':reservation_id' => $notif['reservation_id']]);
            $userInfo = $stmtUser->fetch(PDO::FETCH_ASSOC);
            $notif['username'] = $userInfo['username'] ?? 'Utilisateur inconnu';

            // Récupération des détails du trajet si l'ID du trajet est disponible
            if ($notif['trajet_id']) {
                $stmtTrajetDetails = $pdo->prepare("SELECT numero_trajet, adresse_depart, adresse_arrive, date_depart FROM infos_trajet WHERE id = :trajet_id");
                $stmtTrajetDetails->execute([':trajet_id' => $notif['trajet_id']]);
                $trajetDetails = $stmtTrajetDetails->fetch(PDO::FETCH_ASSOC);

                if ($trajetDetails) {
                    $notif['numero_trajet'] = $trajetDetails['numero_trajet'];
                    $notif['adresse_depart'] = $trajetDetails['adresse_depart'];
                    $notif['adresse_arrive'] = $trajetDetails['adresse_arrive'];
                    $notif['date_depart'] = $trajetDetails['date_depart'];
                } else {
                    // Valeurs par défaut si le trajet n'est pas trouvé
                    $notif['numero_trajet'] = null;
                    $notif['adresse_depart'] = null;
                    $notif['adresse_arrive'] = null;
                    $notif['date_depart'] = null;
                }
            } else {
                // Valeurs par défaut si aucun ID de trajet n'est disponible
                $notif['numero_trajet'] = null;
                $notif['adresse_depart'] = null;
                $notif['adresse_arrive'] = null;
                $notif['date_depart'] = null;
            }
        }

    } catch (PDOException $e) {
        // Silence des erreurs pour ne pas interrompre l'exécution
        // Les variables restent à leurs valeurs par défaut (0 et tableau vide)
        error_log("Erreur notifications: " . $e->getMessage());
    }
}
?>