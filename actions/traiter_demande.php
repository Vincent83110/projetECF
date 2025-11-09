<?php
// Chargement des dépendances et fichiers de configuration
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/csrf.php';

// Importation des classes PHPMailer pour l'envoi d'emails
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Vérification que l'utilisateur est connecté avec le rôle approprié
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'utilisateur' || 
    !in_array($_SESSION['user']['statut'], ['chauffeur', 'passager_chauffeur'])) {
    die("Accès interdit");
}

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

    // Récupération des données du formulaire
    $reservation_id = $_POST['reservation_id'] ?? null;
    $notification_id = $_POST['notification_id'] ?? null;
    $action = $_POST['action'] ?? null;

    // Vérification de la présence des données obligatoires
    if (!$reservation_id || !$action) {
        die("Données manquantes.");
    }

    // Requête pour récupérer les informations détaillées de la réservation
    $stmt = $pdo->prepare("
        SELECT r.*, t.numero_trajet, t.adresse_depart, t.adresse_arrive, t.date_depart,
       u.email AS chauffeur_email, u.username AS chauffeur_username,
       p.username AS passager_username, p.email AS passager_email
FROM reservation r
JOIN infos_trajet t ON r.trajet_id = t.id
JOIN utilisateurs u ON t.id_utilisateur = u.id
JOIN utilisateurs p ON r.user_id = p.id
WHERE r.id = :reservation_id
    ");
    $stmt->execute([':reservation_id' => $reservation_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérification que la réservation existe
    if (!$data) {
        die("Réservation introuvable.");
    }

    // Traitement selon l'action (accepter ou refuser)
    if ($action === 'accepter') {
        // Mise à jour du statut de la réservation en 'acceptée'
        $stmtUpdate = $pdo->prepare("UPDATE reservation SET statut = 'acceptée' WHERE id = ?");
        $stmtUpdate->execute([$reservation_id]);

        // Configuration et envoi d'un email au passager pour informer du refus
        $mail = new PHPMailer(true);
        try {
            // Configuration SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'pierrevincent720@gmail.com'; 
            $mail->Password   = 'tnhv khps ljpg inua';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Expéditeur et destinataire
            $mail->setFrom('pierrevincent720@gmail.com', 'ECO RIDE');
            $mail->addAddress($data['passager_email'], $data['passager_username']);
            $mail->isHTML(true);
            $mail->Subject = 'Réservation acceptée pour votre trajet ECO RIDE';

            // Corps du message en HTML
            $mail->Body = '<div style="font-family: Arial, sans-serif; color: #333; padding: 20px;">
                <h2 style="color: #e74c3c;">Bonjour ' . htmlspecialchars($data['passager_username']) . ',</h2>
                <p>Votre réservation pour le trajet <strong>' . htmlspecialchars($data['adresse_depart']) . ' → ' . htmlspecialchars($data['adresse_arrive']) . '</strong> prévu le ' . htmlspecialchars($data['date_depart']) . ' a été acceptée par le conducteur.</p>
                <p>Merci pour votre confiance !.</p>
                <p>Vous pouvez consulter d\'autres trajets sur notre plateforme.</p>
                <hr>
                <p style="font-size:12px;color:#999;">Ce mail est automatique. Merci de ne pas y répondre.</p>
            </div>';

            // Version texte brut du message
            $mail->AltBody = "Bonjour " . $data['passager_username'] . ",\nVotre réservation pour le trajet " . $data['adresse_depart'] . " → " . $data['adresse_arrive'] . " du " . $data['date_depart'] . " a été refusée par le conducteur.";

            $mail->send();
        } catch (Exception $e) {
            // Log des erreurs d'envoi d'email
            error_log("Erreur envoi mail : " . $mail->ErrorInfo);
        }
        
    } elseif ($action === 'refuser') {
    $stmtPrix = $pdo->prepare("
        SELECT r.nombre_places, r.user_id, t.prix, t.id AS trajet_id
        FROM reservation r
        JOIN infos_trajet t ON r.trajet_id = t.id
        WHERE r.id = ?
    ");
    $stmtPrix->execute([$reservation_id]);
    $res = $stmtPrix->fetch(PDO::FETCH_ASSOC);

    if ($res) {
        $prix_total = $res['nombre_places'] * $res['prix'];

        // Rendre les crédits au passager
        $stmtMajCredits = $pdo->prepare("UPDATE utilisateurs SET credits = credits + ? WHERE id = ?");
        $stmtMajCredits->execute([$prix_total, $res['user_id']]);

        // Mettre à jour le nombre de places disponibles
        $stmtUpdatePlace = $pdo->prepare("
            UPDATE infos_trajet
            SET nombre_place = nombre_place + ?
            WHERE id = ?
        ");
        $stmtUpdatePlace->execute([$res['nombre_places'], $res['trajet_id']]);
    }

    // Supprimer la réservation
    $stmtDelete = $pdo->prepare("DELETE FROM reservation WHERE id = ?");
    $stmtDelete->execute([$reservation_id]);

        // Configuration et envoi d'un email au passager pour informer du refus
        $mail = new PHPMailer(true);
        try {
            // Configuration SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'pierrevincent720@gmail.com'; 
            $mail->Password   = 'tnhv khps ljpg inua';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Expéditeur et destinataire
            $mail->setFrom('pierrevincent720@gmail.com', 'ECO RIDE');
            $mail->addAddress($data['passager_email'], $data['passager_username']);
            $mail->isHTML(true);
            $mail->Subject = 'Réservation refusée pour votre trajet ECO RIDE';

            // Corps du message en HTML
            $mail->Body = '<div style="font-family: Arial, sans-serif; color: #333; padding: 20px;">
                <h2 style="color: #e74c3c;">Bonjour ' . htmlspecialchars($data['passager_username']) . ',</h2>
                <p>Votre réservation pour le trajet <strong>' . htmlspecialchars($data['adresse_depart']) . ' → ' . htmlspecialchars($data['adresse_arrive']) . '</strong> prévu le ' . htmlspecialchars($data['date_depart']) . ' a été refusée par le conducteur.</p>
                <p>Nous sommes désolés pour la gêne occasionnée.</p>
                <p>Vous pouvez consulter d\'autres trajets sur notre plateforme.</p>
                <hr>
                <p style="font-size:12px;color:#999;">Ce mail est automatique. Merci de ne pas y répondre.</p>
            </div>';

            // Version texte brut du message
            $mail->AltBody = "Bonjour " . $data['passager_username'] . ",\nVotre réservation pour le trajet " . $data['adresse_depart'] . " → " . $data['adresse_arrive'] . " du " . $data['date_depart'] . " a été refusée par le conducteur.";

            $mail->send();
        } catch (Exception $e) {
            // Log des erreurs d'envoi d'email
            error_log("Erreur envoi mail : " . $mail->ErrorInfo);
        }
    }

    if ($notification_id) {
        $stmtNotif = $pdo->prepare("DELETE FROM notifications WHERE id = :id");
        $stmtNotif->execute([':id' => $notification_id]);
    }

    // Redirection vers la page des trajets individuels après traitement
    header("Location: " . BASE_URL . "/pages/TrajetIndividuel.php");
    exit;

} catch (PDOException $e) {
    // Gestion des erreurs de base de données
    die("Erreur : " . $e->getMessage());
}
?>