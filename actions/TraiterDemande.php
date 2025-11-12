<?php
// Inclusion fichiers config et sécurité
require_once __DIR__ . '/../vendor/autoload.php';
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php';
} else {
    require_once __DIR__ . '/../includes/Config.php';
}
include __DIR__ . '/../includes/Csrf.php';

// Vérification rôle utilisateur
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'utilisateur' || 
    !in_array($_SESSION['user']['statut'], ['chauffeur', 'passager_chauffeur'])) {
    die("Accès interdit");
}

// Vérification POST + CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Méthode non autorisée.");
if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) die("Erreur CSRF");

// Connexion base
$pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// URL de base
$baseUrl = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
    ? "http://localhost/" . BASE_URL
    : "https://eco-ride.onrender.com";

// Fonction d'envoi mail via Brevo
function sendBrevoMail($toEmail, $toName, $subject, $htmlContent, $textContent) {
    $apiKey = getenv('BREVO_API_KEY');
    $data = [
        'sender' => ['email' => 'pierrevincent720@gmail.com', 'name' => 'ECO RIDE'],
        'to' => [['email' => $toEmail, 'name' => $toName]],
        'subject' => $subject,
        'htmlContent' => $htmlContent,
        'textContent' => $textContent
    ];
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'api-key: ' . $apiKey,
        'content-type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if (curl_errno($ch)) error_log('Erreur cURL: ' . curl_error($ch));
    curl_close($ch);
}

try {
    $reservation_id = $_POST['reservation_id'] ?? null;
    $notification_id = $_POST['notification_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!$reservation_id || !$action) die("Données manquantes.");

    // Récupération infos réservation + trajet + passager + chauffeur
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
    if (!$data) die("Réservation introuvable.");

    $pdo->beginTransaction();

    if ($action === 'accepter') {
        $pdo->prepare("UPDATE reservation SET statut = 'acceptée' WHERE id = ?")->execute([$reservation_id]);

        $html = '<div style="font-family: Arial, sans-serif; color: #333; padding: 20px;">
            <h2 style="color: #e74c3c;">Bonjour ' . htmlspecialchars($data['passager_username']) . ',</h2>
            <p>Votre réservation pour le trajet <strong>' . htmlspecialchars($data['adresse_depart']) . ' → ' . htmlspecialchars($data['adresse_arrive']) . '</strong> prévu le ' . htmlspecialchars($data['date_depart']) . ' a été acceptée par le conducteur.</p>
            <p>Merci pour votre confiance !</p>
            </div>';
        $text = "Bonjour {$data['passager_username']}, votre réservation pour le trajet {$data['adresse_depart']} → {$data['adresse_arrive']} du {$data['date_depart']} a été acceptée par le conducteur.";

        sendBrevoMail($data['passager_email'], $data['passager_username'], 'Réservation acceptée pour votre trajet ECO RIDE', $html, $text);

    } elseif ($action === 'refuser') {
        // Rendre crédits au passager
        $prix_total = $data['nombre_places'] * $data['prix'];
        $pdo->prepare("UPDATE utilisateurs SET credits = credits + ? WHERE id = ?")->execute([$prix_total, $data['user_id']]);

        // Mise à jour nombre de places
        $pdo->prepare("UPDATE infos_trajet SET nombre_place = nombre_place + ? WHERE id = ?")
            ->execute([$data['nombre_places'], $data['trajet_id']]);

        // Supprimer réservation
        $pdo->prepare("DELETE FROM reservation WHERE id = ?")->execute([$reservation_id]);

        // Email refus
        $html = '<div style="font-family: Arial, sans-serif; color: #333; padding: 20px;">
            <h2 style="color: #e74c3c;">Bonjour ' . htmlspecialchars($data['passager_username']) . ',</h2>
            <p>Votre réservation pour le trajet <strong>' . htmlspecialchars($data['adresse_depart']) . ' → ' . htmlspecialchars($data['adresse_arrive']) . '</strong> prévu le ' . htmlspecialchars($data['date_depart']) . ' a été refusée par le conducteur.</p>
            </div>';
        $text = "Bonjour {$data['passager_username']}, votre réservation pour le trajet {$data['adresse_depart']} → {$data['adresse_arrive']} du {$data['date_depart']} a été refusée par le conducteur.";

        sendBrevoMail($data['passager_email'], $data['passager_username'], 'Réservation refusée pour votre trajet ECO RIDE', $html, $text);
    }

    if ($notification_id) {
        $pdo->prepare("DELETE FROM notifications WHERE id = ?")->execute([$notification_id]);
    }

    $pdo->commit();
    header("Location: " . BASE_URL . "/pages/TrajetIndividuel.php");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Erreur : " . $e->getMessage());
}
