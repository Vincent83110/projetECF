<?php
// Inclusion fichiers config et sécurité
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php';
} else {
    require_once __DIR__ . '/../includes/Config.php';
}
include __DIR__ . '/../includes/Csrf.php';
include __DIR__ . '/../includes/Auth.php';

// Démarrage de session pour gestion des erreurs
session_start();

// Vérification POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Méthode non autorisée.";
    header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php");
    exit;
}
if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
    $_SESSION['error'] = "Erreur de sécurité CSRF.";
    header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php");
    exit;
}

// Connexion à la base
$pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Base URL
$baseUrl = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
    ? "http://localhost/" . BASE_URL
    : "https://eco-ride.onrender.com";

// Fonction pour envoyer mail via Brevo
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
    if (!$estConnecte || !isset($_SESSION['user'])) {
        $_SESSION['error'] = "Vous devez être connecté pour effectuer cette action.";
        header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php");
        exit;
    }

    $user_id = $_SESSION['user']['id'];
    $trajet_id = $_POST['trajet_id'] ?? null;
    $places_demandees = filter_input(INPUT_POST, 'nombre_places', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?? 1;

    if (!$trajet_id) {
        $_SESSION['error'] = "Aucun trajet spécifié.";
        header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php");
        exit;
    }

    $pdo->beginTransaction();

    // Récupération crédits utilisateur
    $utilisateur = $pdo->prepare("SELECT credits FROM utilisateurs WHERE id = ?");
    $utilisateur->execute([$user_id]);
    $utilisateur = $utilisateur->fetch(PDO::FETCH_ASSOC);
    if (!$utilisateur) {
        $_SESSION['error'] = "Utilisateur non trouvé.";
        header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php");
        exit;
    }

    // Récupération infos trajet
    $trajet = $pdo->prepare("SELECT nombre_place, prix, id_utilisateur, numero_trajet FROM infos_trajet WHERE id = ?");
    $trajet->execute([$trajet_id]);
    $trajet = $trajet->fetch(PDO::FETCH_ASSOC);
    if (!$trajet) {
        $_SESSION['error'] = "Trajet non trouvé.";
        header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php");
        exit;
    }

    if ($places_demandees > $trajet['nombre_place']) {
        $_SESSION['error'] = "Nombre de places demandé trop élevé.";
        header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php");
        exit;
    }
    $prix_total = $trajet['prix'] * $places_demandees;
    if ($utilisateur['credits'] < $prix_total) {
        $_SESSION['error'] = "Crédits insuffisants.";
        header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php");
        exit;
    }

    // Vérification réservation existante
    $stmtCheck = $pdo->prepare("SELECT * FROM reservation WHERE trajet_id = ? AND user_id = ?");
    $stmtCheck->execute([$trajet_id, $user_id]);
    if ($stmtCheck->fetch()) {
        $_SESSION['error'] = "Vous avez déjà réservé ce trajet.";
        header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php");
        exit;
    }

    // Création réservation
    $stmtRes = $pdo->prepare("INSERT INTO reservation (user_id, trajet_id, statut, nombre_places) VALUES (?, ?, 'en_attente', ?) RETURNING id");
    $stmtRes->execute([$user_id, $trajet_id, $places_demandees]);
    $reservation_id = $stmtRes->fetchColumn();

    // Mise à jour places et crédits
    $pdo->prepare("UPDATE infos_trajet SET nombre_place = nombre_place - ? WHERE id = ?")->execute([$places_demandees, $trajet_id]);
    $nouveaux_credits = $utilisateur['credits'] - $prix_total;
    $pdo->prepare("UPDATE utilisateurs SET credits = ? WHERE id = ?")->execute([$nouveaux_credits, $user_id]);

    // Notification chauffeur
    $chauffeur_id = $trajet['id_utilisateur'];
    $pdo->prepare("INSERT INTO notifications (user_id, date_creation, reservation_id) VALUES (?, NOW(), ?)")->execute([$chauffeur_id, $reservation_id]);

    // Récupération infos chauffeur
    $stmtChauffeur = $pdo->prepare("SELECT email, username FROM utilisateurs WHERE id = ?");
    $stmtChauffeur->execute([$chauffeur_id]);
    $chauffeur = $stmtChauffeur->fetch(PDO::FETCH_ASSOC);

    if ($chauffeur && !empty($chauffeur['email'])) {
        $htmlContent = "
            <p>Bonjour {$chauffeur['username']},</p>
            <p>Un passager vient de réserver votre trajet numéro <strong>{$trajet['numero_trajet']}</strong>.</p>
            <p>Consultez vos notifications sur ECO RIDE pour plus de détails.</p>
            <p>— L'équipe ECO RIDE</p>";
        $textContent = "Bonjour {$chauffeur['username']}, un passager a réservé votre trajet n°{$trajet['numero_trajet']}.";

        sendBrevoMail($chauffeur['email'], $chauffeur['username'], 'Nouvelle réservation sur votre trajet', $htmlContent, $textContent);
    }

    $pdo->commit();

    header("Location: " . BASE_URL . "/pages/TrajetIndividuel.php?success=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['error'] = "Erreur lors de la réservation : " . $e->getMessage();
    header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php");
    exit;
}