<?php
// Inclusion des fichiers de configuration et de sécurité
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php';
} else {
    require_once __DIR__ . '/../includes/Config.php';
}
include __DIR__ . '/../includes/Csrf.php';

// Vérification de la session utilisateur
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

// Définition du baseUrl selon environnement
$baseUrl = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
    ? "http://localhost/" . BASE_URL
    : "https://eco-ride.onrender.com";

// Fonction pour envoyer un mail via l'API Brevo
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
    if (curl_errno($ch)) {
        error_log('Erreur cURL: ' . curl_error($ch));
    } else {
        error_log('Réponse Brevo: ' . $response);
    }
    curl_close($ch);
}

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $trajet_id = $_POST['trajet_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!$trajet_id || $action !== 'annuler') {
        die("Données manquantes ou action non valide.");
    }

    // Récupération du trajet et du chauffeur
    $stmtTrajet = $pdo->prepare("
        SELECT t.*, u.username AS chauffeur_nom, u.email AS chauffeur_email
        FROM infos_trajet t
        JOIN utilisateurs u ON t.id_utilisateur = u.id
        WHERE t.id = :trajet_id
    ");
    $stmtTrajet->execute([':trajet_id' => $trajet_id]);
    $trajet = $stmtTrajet->fetch(PDO::FETCH_ASSOC);
    if (!$trajet) die("Trajet introuvable.");

    // Récupération des passagers
    $stmtPassagers = $pdo->prepare("
        SELECT u.email, u.username
        FROM reservation r
        JOIN utilisateurs u ON r.user_id = u.id
        WHERE r.trajet_id = :trajet_id
    ");
    $stmtPassagers->execute([':trajet_id' => $trajet_id]);
    $passagers = $stmtPassagers->fetchAll(PDO::FETCH_ASSOC);

    // Remboursement des crédits et envoi des mails
    foreach ($passagers as $passager) {
        // Créditer l'utilisateur
        $credits = (int)$trajet['prix'];
        $stmtUpdate = $pdo->prepare("UPDATE utilisateurs SET credits = credits + ? WHERE email = ?");
        $stmtUpdate->execute([$credits, $passager['email']]);

        // Préparer le mail
        $htmlContent = '
        <div style="font-family: Arial, sans-serif; color: #333; padding: 20px;">
            <h2 style="color: #e74c3c;">Bonjour ' . htmlspecialchars($passager['username']) . ',</h2>
            <p>Nous vous informons que le trajet <strong>' . htmlspecialchars($trajet['adresse_depart']) . ' → ' . htmlspecialchars($trajet['adresse_arrive']) . '</strong> prévu le <strong>' . htmlspecialchars($trajet['date_depart']) . '</strong> a été <strong>annulé</strong> par le conducteur ' . htmlspecialchars($trajet['chauffeur_nom']) . '.</p>
            <p>Le montant payé a été remboursé sur votre compte ECO RIDE.</p>
            <p>Vous pouvez rechercher un autre trajet sur la plateforme <a href="' . $baseUrl . '">ECO RIDE</a>.</p>
            <hr>
            <p style="font-size:12px;color:#999;">Ce mail est automatique, merci de ne pas y répondre.</p>
        </div>';

        $textContent = "Bonjour {$passager['username']},\nLe trajet {$trajet['adresse_depart']} → {$trajet['adresse_arrive']} prévu le {$trajet['date_depart']} a été annulé par le conducteur {$trajet['chauffeur_nom']}.\nLe montant payé a été remboursé sur votre compte ECO RIDE.\nVous pouvez rechercher un autre trajet ici : {$baseUrl}";

        sendBrevoMail($passager['email'], $passager['username'], 'Annulation du trajet - ECO RIDE', $htmlContent, $textContent);
    }

    // Suppression des données liées au trajet
    $pdo->prepare("DELETE FROM preferences WHERE trajet_id = ?")->execute([$trajet_id]);
    $pdo->prepare("DELETE FROM reservation WHERE trajet_id = ?")->execute([$trajet_id]);
    $pdo->prepare("DELETE FROM infos_trajet WHERE id = ?")->execute([$trajet_id]);

    // Redirection
    header("Location: " . BASE_URL . "/pages/TrajetIndividuel.php?annule=1");
    exit;

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
