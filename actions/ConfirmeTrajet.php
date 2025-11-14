<?php
require_once __DIR__ . '/../vendor/autoload.php';
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}

include __DIR__ . '/../includes/Csrf.php';

// Vérification que la méthode est bien POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Méthode non autorisée.";
    header("Location: " . BASE_URL . "/pages/Historique.php");
    exit;
}

// Vérification CSRF
if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
    $_SESSION['error'] = "Erreur CSRF : action non autorisée !";
    header("Location: " . BASE_URL . "/pages/Historique.php");
    exit;
}

// Définition du baseUrl selon environnement
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    $baseUrl = "http://localhost/" . BASE_URL;
} else {
    $baseUrl = "https://eco-ride.onrender.com";
}

// Fonction d'envoi d'email via l'API Brevo
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

// Traitement principal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trajetId = $_POST['trajet_id'] ?? null;

    if (!$trajetId) {
        $_SESSION['error'] = "Erreur: trajet_id non fourni";
        header("Location: " . BASE_URL . "/pages/Historique.php");
        exit;
    }

    try {
        // Connexion à la base de données
        $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Transaction
        $pdo->beginTransaction();

        // 1. Récupération du trajet
        $stmtTrajet = $pdo->prepare("SELECT prix, id_utilisateur FROM infos_trajet WHERE id = :id");
        $stmtTrajet->execute([':id' => $trajetId]);
        $trajet = $stmtTrajet->fetch(PDO::FETCH_ASSOC);
        if (!$trajet) {
            throw new Exception("Trajet introuvable avec l'ID: $trajetId");
        }

        // 2. Récupération du nombre total de places réservées
        $stmtPlaces = $pdo->prepare("
            SELECT COALESCE(SUM(nombre_places), 0) as total_places 
            FROM reservation 
            WHERE trajet_id = :trajet_id AND statut = 'en_cours'
        ");
        $stmtPlaces->execute([':trajet_id' => $trajetId]);
        $totalPlaces = $stmtPlaces->fetch(PDO::FETCH_ASSOC)['total_places'];

        // 3. Calcul du montant total pour le chauffeur
        $montantTotal = $trajet['prix'] * $totalPlaces;

        // 4. Mise à jour du trajet
        $stmtUpdateTrajet = $pdo->prepare("
            UPDATE infos_trajet 
            SET statut = 'termine', statut_paiement_chauffeur = 'en_attente'
            WHERE id = :id
        ");
        $stmtUpdateTrajet->execute([':id' => $trajetId]);

        // 5. Mise à jour des réservations
        $stmtUpdateReservations = $pdo->prepare("
            UPDATE reservation SET statut = 'termine' 
            WHERE trajet_id = :trajet_id AND statut = 'en_cours'
        ");
        $stmtUpdateReservations->execute([':trajet_id' => $trajetId]);

        // 6. Récupération des passagers
        $stmtRes = $pdo->prepare("
            SELECT u.email, u.username
            FROM reservation r
            JOIN utilisateurs u ON r.user_id = u.id
            WHERE r.trajet_id = :trajetId
        ");
        $stmtRes->execute([':trajetId' => $trajetId]);
        $reservations = $stmtRes->fetchAll(PDO::FETCH_ASSOC);

        // 7. Récupération du chauffeur
        $stmtChauffeur = $pdo->prepare("
            SELECT u.username
            FROM utilisateurs u
            JOIN infos_trajet i ON i.id_utilisateur = u.id
            WHERE i.id = :id
        ");
        $stmtChauffeur->execute([':id' => $trajetId]);
        $chauffeurUsername = $stmtChauffeur->fetch(PDO::FETCH_ASSOC)['username'] ?? 'conducteur';

        // Commit transaction
        $pdo->commit();

        // 8. Envoi des emails aux passagers
        foreach ($reservations as $user) {
            $commentLink = $baseUrl . "/pages/ConnexionUtilisateur.php?redirect=PageAvis.php&trajet_id=" 
                            . urlencode($trajetId) . "&user=" . urlencode($chauffeurUsername);

            $htmlContent = '
                <div style="font-family: Arial, sans-serif; color: #333; padding: 20px;">
                    <h2 style="color: #2e86de;">Bonjour ' . htmlspecialchars($user['username']) . ',</h2>
                    <p>Votre trajet <strong>n°' . htmlspecialchars($trajetId) . '</strong> est terminé.</p>
                    <p>Vous pouvez maintenant laisser un commentaire et une note au conducteur.</p>
                    <a href="' . $commentLink . '" 
                       style="background-color:#2e86de;color:#fff;padding:10px 15px;text-decoration:none;border-radius:5px;">
                       Donner mon avis
                    </a>
                    <p style="margin-top:20px;">Merci de faire confiance à notre plateforme 🙏</p>
                    <hr>
                    <p style="font-size:12px;color:#999;">Ce mail est automatique. Merci de ne pas y répondre.</p>
                </div>';

            $textContent = "Bonjour {$user['username']},\nVotre trajet n°{$trajetId} est terminé. Donnez votre avis ici : {$commentLink}";

            sendBrevoMail($user['email'], $user['username'], 'Votre trajet est terminé - Merci de laisser un avis', $htmlContent, $textContent);
        }

        // Redirection après traitement
        header("Location: " . BASE_URL . "/pages/Historique.php");
        exit;

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['error'] = "Erreur PDO: " . $e->getMessage();
        header("Location: " . BASE_URL . "/pages/Historique.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
        header("Location: " . BASE_URL . "/pages/Historique.php");
        exit;
    }
} else {
    $_SESSION['error'] = "Erreur: Méthode non autorisée";
    header("Location: " . BASE_URL . "/pages/Historique.php");
    exit;
}