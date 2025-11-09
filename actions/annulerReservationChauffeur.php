<?php
// Inclusion des fichiers de configuration et de sécurité
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/csrf.php';

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    die("Accès refusé.");
}


// Importation de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

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

    // Récupération des données du formulaire
    $trajet_id = $_POST['trajet_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!$trajet_id || $action !== 'annuler') {
        die("Données manquantes ou action non valide.");
    }

    // 🔹 Récupération des informations du trajet pour le mail
    $stmtTrajet = $pdo->prepare("
        SELECT t.*, u.username AS chauffeur_nom, u.email AS chauffeur_email
        FROM infos_trajet t
        JOIN utilisateurs u ON t.id_utilisateur = u.id
        WHERE t.id = :trajet_id
    ");
    $stmtTrajet->execute([':trajet_id' => $trajet_id]);
    $trajet = $stmtTrajet->fetch(PDO::FETCH_ASSOC);

    if (!$trajet) {
        die("Trajet introuvable.");
    }

    // 🔹 Récupération des passagers ayant réservé ce trajet
    $stmtPassagers = $pdo->prepare("
        SELECT u.email, u.username
        FROM reservation r
        JOIN utilisateurs u ON r.user_id = u.id
        WHERE r.trajet_id = :trajet_id
    ");
    $stmtPassagers->execute([':trajet_id' => $trajet_id]);
    $passagers = $stmtPassagers->fetchAll(PDO::FETCH_ASSOC);

    // 🔹 Envoi du mail à chaque passager
    if (!empty($passagers)) {
        foreach ($passagers as $passager) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'pierrevincent720@gmail.com';
                $mail->Password   = 'tnhv khps ljpg inua';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('pierrevincent720@gmail.com', 'ECO RIDE');
                $mail->addAddress($passager['email'], $passager['username']);

                $mail->isHTML(true);
                $mail->Subject = "Annulation du trajet - ECO RIDE";

                $mail->Body = '
                <div style="font-family: Arial, sans-serif; color: #333; padding: 20px;">
                    <h2 style="color: #e74c3c;">Bonjour ' . htmlspecialchars($passager['username']) . ',</h2>
                    <p>Nous vous informons que le trajet <strong>' . htmlspecialchars($trajet['adresse_depart']) . ' → ' . htmlspecialchars($trajet['adresse_arrive']) . '</strong> prévu le <strong>' . htmlspecialchars($trajet['date_depart']) . '</strong> a été <strong>annulé</strong> par le conducteur ' . htmlspecialchars($trajet['chauffeur_nom']) . '.</p>
                    <p>Nous sommes désolés pour la gêne occasionnée.</p>
                    <p>Vous pouvez rechercher un autre trajet sur la plateforme <a href="#">ECO RIDE</a>.</p>
                    <hr>
                    <p style="font-size:12px;color:#999;">Ce mail est automatique, merci de ne pas y répondre.</p>
                </div>';

                $mail->AltBody = "Bonjour " . $passager['username'] . ",
Le trajet " . $trajet['adresse_depart'] . " → " . $trajet['adresse_arrive'] . " prévu le " . $trajet['date_depart'] . " a été annulé par le conducteur " . $trajet['chauffeur_nom'] . ".";

                $mail->send();
            } catch (Exception $e) {
                error_log("Erreur envoi mail annulation à " . $passager['email'] . " : " . $mail->ErrorInfo);
            }
        }
    }

    // 🔹 Suppression des données liées
    $stmtPref = $pdo->prepare("DELETE FROM preferences WHERE trajet_id = ?");
    $stmtPref->execute([$trajet_id]);

    $stmtRes = $pdo->prepare("DELETE FROM reservation WHERE trajet_id = ?");
    $stmtRes->execute([$trajet_id]);

    $stmtTrajet = $pdo->prepare("DELETE FROM infos_trajet WHERE id = ?");
    $stmtTrajet->execute([$trajet_id]);

    // ✅ Redirection
    header("Location: " . BASE_URL . "/pages/TrajetIndividuel.php?annule=1");
    exit;

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
