<?php
// Inclusion des fichiers nécessaires pour l'authentification, la configuration et la protection CSRF
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}

include __DIR__ . '/../includes/Csrf.php';
include __DIR__ . '/../includes/Auth.php';

// Inclusion des bibliothèques nécessaires pour l'envoi d'emails
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Vérification que la méthode de requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

// Vérification CSRF pour prévenir les attaques
if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
    die("Erreur CSRF : action non autorisée !");
}

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérification que l'utilisateur est connecté
    if ($estConnecte) {

        if (!isset($_SESSION['user'])) {
            header("Location: " . BASE_URL . "/pages/Connexion.php");
            exit;
        }

        // Récupération des données de la réservation
        $user_id = $_SESSION['user']['id'];
        $trajet_id = $_POST['trajet_id'] ?? null;

        // Vérification de la présence de l'ID du trajet
        if (!$trajet_id) {
            die("Aucun trajet spécifié.");
        }

        // Validation du nombre de places demandées
        $places_demandees = filter_input(INPUT_POST, 'nombre_places', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?? 1;

        // Récupération des crédits de l'utilisateur
        $stmtUser = $pdo->prepare("SELECT credits FROM utilisateurs WHERE id = ?");
        $stmtUser->execute([$user_id]);
        $utilisateur = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$utilisateur) {
            die("Utilisateur non trouvé.");
        }

        // Récupération des informations du trajet (places disponibles, prix et propriétaire)
        $stmt = $pdo->prepare("SELECT nombre_place, prix, id_utilisateur FROM infos_trajet WHERE id = ?");
        $stmt->execute([$trajet_id]);
        $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trajet) {
            die("Trajet non trouvé.");
        }

        // Vérification qu'il reste des places disponibles
        if ($trajet['nombre_place'] <= 0) {
            die("Désolé, ce trajet est complet.");
        }

        // Vérification que le nombre de places demandées est disponible
        if ($places_demandees > $trajet['nombre_place']) {
            die("Le nombre de places demandées dépasse les places disponibles.");
        }

        // Calcul du prix total de la réservation
        $prix_total = $trajet['prix'] * $places_demandees;

        // Vérification que l'utilisateur a assez de crédits
        if ($utilisateur['credits'] < $prix_total) {
            die("Crédits insuffisants.");
        }

        // Vérification que l'utilisateur n'a pas déjà réservé ce trajet
        $stmtCheck = $pdo->prepare("SELECT * FROM reservation WHERE trajet_id = ? AND user_id = ?");
        $stmtCheck->execute([$trajet_id, $user_id]);
        if ($stmtCheck->fetch()) {
            die("Vous avez déjà réservé ce trajet.");
        }

        // Début de la transaction pour assurer l'intégrité des données
        $pdo->beginTransaction();

        // Insertion de la réservation et récupération de son ID
        $stmt = $pdo->prepare("INSERT INTO reservation (user_id, trajet_id, statut, nombre_places) VALUES (?, ?, 'en_attente', ?) RETURNING id");
        $stmt->execute([$user_id, $trajet_id, $places_demandees]);
        $reservation_id = $stmt->fetchColumn();

        // Mise à jour du nombre de places disponibles dans le trajet
        $stmt = $pdo->prepare("UPDATE infos_trajet SET nombre_place = nombre_place - ? WHERE id = ?");
        $stmt->execute([$places_demandees, $trajet_id]);

        // Calcul et mise à jour des nouveaux crédits de l'utilisateur
        $nouveaux_credits = $utilisateur['credits'] - $prix_total;
        $stmtMajCredits = $pdo->prepare("UPDATE utilisateurs SET credits = ? WHERE id = ?");
        $stmtMajCredits->execute([$nouveaux_credits, $user_id]);

        // Insertion d'une notification pour le chauffeur
        $chauffeur_id = $trajet['id_utilisateur'] ?? null;
        $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, date_creation, reservation_id) VALUES (?, NOW(), ?)");
        $resultNotif = $stmtNotif->execute([$chauffeur_id, $reservation_id]);

        // Journalisation en cas d'erreur d'insertion de notification
        if (!$resultNotif) {
            error_log("Erreur insertion notification : " . implode(' | ', $stmtNotif->errorInfo()));
        }

        // Récupération des informations du chauffeur pour l'envoi d'email
        $stmtChauffeur = $pdo->prepare("SELECT email, username FROM utilisateurs WHERE id = :chauffeur_id");
        $stmtChauffeur->execute([':chauffeur_id' => $chauffeur_id]);
        $chauffeur = $stmtChauffeur->fetch(PDO::FETCH_ASSOC);

        // Récupération du numéro de trajet pour l'email
        $stmtNum = $pdo->prepare("SELECT numero_trajet FROM infos_trajet WHERE id = ?");
        $stmtNum->execute([$trajet_id]);
        $info = $stmtNum->fetch(PDO::FETCH_ASSOC);
        $numero_trajet = $info['numero_trajet'] ?? 'inconnu';

        // Envoi d'un email au chauffeur si ses informations sont disponibles
        if ($chauffeur && !empty($chauffeur['email'])) {
            
            $mail = new PHPMailer(true);
            try {
                $mail->SMTPDebug = 0;  // Désactivation du debug SMTP
                $mail->Debugoutput = function($str, $level) { error_log("SMTP DEBUG: $str"); };

                 // Configuration du serveur SMTP
                 $mail->isSMTP();
                 $mail->Host       = 'smtp-relay.brevo.com';
                 $mail->SMTPAuth   = true;
                 $mail->Username   = '9b6d21001@smtp-brevo.com'; 
                 $mail->Password   = '6yIHW1pCNrvSFsjD';
                 $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                 $mail->Port       = 587;

                // Configuration de l'expéditeur et du destinataire
                $mail->setFrom('9b6d21001@smtp-brevo.com', 'ECO RIDE');
                $mail->addAddress($chauffeur['email'], $chauffeur['username']);
                $mail->isHTML(true);
                $mail->Subject = 'Nouvelle réservation sur votre trajet';

                // Corps de l'email en HTML
                $mail->Body = "
                    <p>Bonjour {$chauffeur['username']},</p>
                    <p>Un passager vient de réserver votre trajet numéro <strong>{$numero_trajet}</strong>.</p>
                    <p>Consultez vos notifications sur ECO RIDE pour plus de détails.</p>
                    <p>— L'équipe ECO RIDE</p>
                ";

                // Version texte de l'email
                $mail->AltBody = "Bonjour {$chauffeur['username']}, un passager a réservé votre trajet n°{$numero_trajet}.";

                // Envoi de l'email
                $mail->send();
                
            } catch (Exception $e) {
                // Journalisation en cas d'erreur d'envoi d'email
                error_log("Erreur envoi mail chauffeur : " . $mail->ErrorInfo);
            }
        }

        // Validation de la transaction
        $pdo->commit();

        // Redirection vers la page des trajets individuels avec un indicateur de succès
        header("Location: " . BASE_URL . "/pages/TrajetIndividuel.php?success=1");
        exit;
    } else {
        // Redirection vers la page de connexion si l'utilisateur n'est pas connecté
        header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php");
        exit;
    }
} catch (Exception $e) {
    // En cas d'erreur, annulation de la transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Erreur : " . $e->getMessage();
}