<?php
// Démarrage de la session
session_start();

// Importation des classes PHPMailer pour l'envoi d'emails
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Chargement de l'autoloader Composer
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération des données du formulaire
    $trajet_id = $_POST['trajet_id'] ?? null;
    $nombre_places = $_POST['nombre_places'] ?? 1;
    $user_id = $_SESSION['user']['id'] ?? null;

    // Vérification des données obligatoires
    if (!$trajet_id || !$user_id) {
        die("Données manquantes");
    }

    // 1. Insertion de la réservation avec retour de l'ID généré
    $stmtInsert = $pdo->prepare("INSERT INTO reservation (trajet_id, user_id, nombre_places, statut) VALUES (?, ?, ?, 'en attente') RETURNING id");
    $stmtInsert->execute([$trajet_id, $user_id, $nombre_places]);
    $lastReservationId = $stmtInsert->fetchColumn();

    // 2. Récupération de l'ID du chauffeur du trajet
    $stmtChauffeur = $pdo->prepare("SELECT id_utilisateur FROM infos_trajet WHERE id = ?");
    $stmtChauffeur->execute([$trajet_id]);
    $chauffeur = $stmtChauffeur->fetch(PDO::FETCH_ASSOC);
    $chauffeur_id = $chauffeur['id_utilisateur'] ?? null;

    // Si le chauffeur existe, création de notification et envoi d'email
    if ($chauffeur_id) {
        // 3. Création d'une notification pour le chauffeur
        $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, reservation_id, date_creation) VALUES (?, ?, NOW())");
        $stmtNotif->execute([$chauffeur_id, $lastReservationId]);

        // 4. Récupération des informations du chauffeur (email et nom)
        $stmtChauffeurInfo = $pdo->prepare("SELECT email, username FROM utilisateurs WHERE id = ?");
        $stmtChauffeurInfo->execute([$chauffeur_id]);
        $chauffeurInfo = $stmtChauffeurInfo->fetch(PDO::FETCH_ASSOC);

        // 5. Récupération du numéro de trajet
        $stmtTrajet = $pdo->prepare("SELECT numero_trajet FROM infos_trajet WHERE id = ?");
        $stmtTrajet->execute([$trajet_id]);
        $trajetInfo = $stmtTrajet->fetch(PDO::FETCH_ASSOC);

        // Envoi d'email au chauffeur si les informations existent
        if ($chauffeurInfo && !empty($chauffeurInfo['email'])) {
            $mail = new PHPMailer(true);

            try {
                // Configuration SMTP
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'pierrevincent720@gmail.com';
                $mail->Password   = 'mot_de_passe_app';  // À remplacer par le mot de passe d'application réel
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Configuration de l'email
                $mail->setFrom('pierrevincent720@gmail.com', 'ECO RIDE');
                $mail->addAddress('pierrevincent720@gmail.com', $chauffeurInfo['username']);

                $mail->isHTML(true);
                $mail->Subject = 'Nouvelle réservation sur votre trajet';

                // Corps de l'email en HTML
                $mail->Body = "
                    <p>Bonjour {$chauffeurInfo['username']},</p>
                    <p>Un passager vient de réserver votre trajet numéro <strong>{$trajetInfo['numero_trajet']}</strong>.</p>
                    <p>Consultez vos notifications pour plus de détails.</p>
                    <p>— L'équipe ECO RIDE</p>
                ";

                // Version texte simple de l'email
                $mail->AltBody = "Bonjour {$chauffeurInfo['username']}, un passager a réservé votre trajet n°{$trajetInfo['numero_trajet']}.";

                // Envoi de l'email
                $mail->send();

            } catch (Exception $e) {
                // Journalisation de l'erreur d'envoi d'email
                error_log("Erreur envoi mail chauffeur : " . $mail->ErrorInfo);
                // Optionnel : afficher l'erreur en développement
                // echo "Erreur d'envoi mail : " . $mail->ErrorInfo;
            }
        }
    }

    // Redirection vers l'accueil avec paramètre de succès
    header("Location: " . BASE_URL ."/pages/accueil.php?reservation=success");
    exit;

} catch (PDOException $e) {
    // Gestion des erreurs de base de données
    die("Erreur base : " . $e->getMessage());
}