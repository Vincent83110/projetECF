<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/csrf.php';

// Importation des bibliothèques pour l'envoi d'emails
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Vérification que la méthode est bien POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

// Vérification CSRF pour prévenir les attaques
if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
    die("Erreur CSRF : action non autorisée !");
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trajetId = $_POST['trajet_id'] ?? null;

    if ($trajetId) {
        
        try {
            // Connexion à la base de données
            $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Début de la transaction pour assurer l'intégrité des données
            $pdo->beginTransaction();

            // 1. Récupération des informations du trajet
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
            $placesData = $stmtPlaces->fetch(PDO::FETCH_ASSOC);
            $totalPlaces = $placesData['total_places'];

            // 3. Calcul du montant total à créditer au conducteur
            $montantTotal = $trajet['prix'] * $totalPlaces;

            // 4. Marquer le trajet comme confirmé par le chauffeur
            $stmtUpdateTrajet = $pdo->prepare("
            UPDATE infos_trajet 
            SET statut = 'termine',
            statut_paiement_chauffeur = 'en_attente'
            WHERE id = :id
            ");
            $stmtUpdateTrajet->execute([':id' => $trajetId]);

            // 5. SUPPRESSION DES PRÉFÉRENCES LIÉES AU TRAJET TERMINÉ
            $stmtDeletePreferences = $pdo->prepare("DELETE FROM preferences WHERE trajet_id = :trajet_id");
            $stmtDeletePreferences->execute([':trajet_id' => $trajetId]);
            $deletedRows = $stmtDeletePreferences->rowCount();

            // 6. Mise à jour du statut des réservations
            $stmtUpdateReservations = $pdo->prepare("
                UPDATE reservation SET statut = 'termine' 
                WHERE trajet_id = :trajet_id AND statut = 'en_cours'
            ");
            $stmtUpdateReservations->execute([':trajet_id' => $trajetId]);


            // 7. Récupération des passagers pour envoi d'emails
            $stmtRes = $pdo->prepare("
                SELECT u.email, u.username
                FROM reservation r
                JOIN utilisateurs u ON r.user_id = u.id
                WHERE r.trajet_id = :trajetId
            ");
            $stmtRes->execute([':trajetId' => $trajetId]);
            $reservations = $stmtRes->fetchAll(PDO::FETCH_ASSOC);

            // 8. Récupération du nom du chauffeur
            $stmtChauffeur = $pdo->prepare("
                SELECT u.username
                FROM utilisateurs u
                JOIN infos_trajet i ON i.id_utilisateur = u.id
                WHERE i.id = :id
            ");
            $stmtChauffeur->execute([':id' => $trajetId]);
            $chauffeur = $stmtChauffeur->fetch(PDO::FETCH_ASSOC);
            $chauffeurUsername = $chauffeur['username'] ?? 'conducteur';

            // Validation de la transaction - toutes les opérations ont réussi
            $pdo->commit();

            // 9. Envoi des emails de notification aux passagers
            $commentLinkBase = "http://localhost/" . BASE_URL . "/pages/ConnexionUtilisateur.php?redirect=PageAvis.php&trajet_id=" . 
                             urlencode($trajetId) . "&user=" . urlencode($chauffeurUsername);

            foreach ($reservations as $user) {
                try {
                    $mail = new PHPMailer(true);
                    // Configuration SMTP pour Gmail
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'pierrevincent720@gmail.com';
                    $mail->Password   = 'tnhv khps ljpg inua';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    // Configuration de l'email
                    $mail->setFrom('pierrevincent720@gmail.com', 'ECO RIDE');
                    $mail->addAddress($user['email'], $user['username']);
                    $mail->isHTML(true);
                    $mail->Subject = 'Votre trajet est terminé - Merci de laisser un avis';
                    
                    // Corps de l'email en HTML
                    $mail->Body = '
                        <div style="font-family: Arial, sans-serif; color: #333; padding: 20px;">
                            <h2 style="color: #2e86de;">Bonjour ' . htmlspecialchars($user['username']) . ',</h2>
                            <p>Votre trajet <strong>n°' . htmlspecialchars($trajetId) . '</strong> est terminé.</p>
                            <p>Vous pouvez maintenant laisser un commentaire et une note au conducteur.</p>
                            <a href="' . $commentLinkBase . '" 
                               style="background-color:#2e86de;color:#fff;padding:10px 15px;text-decoration:none;border-radius:5px;">
                               Donner mon avis
                            </a>
                            <p style="margin-top:20px;">Merci de faire confiance à notre plateforme 🙏</p>
                            <hr>
                            <p style="font-size:12px;color:#999;">Ce mail est automatique. Merci de ne pas y répondre.</p>
                        </div>';
                    
                    // Version texte simple de l'email
                    $mail->AltBody = "Bonjour " . $user['username'] . ",\nVotre trajet n°" . $trajetId . " est terminé. Donnez votre avis ici : " . $commentLinkBase;

                    $mail->send();
                } catch (Exception $e) {
                    $errorMsg = "Erreur envoi mail à {$user['email']}: " . $e->getMessage();
                    error_log($errorMsg);
                }
            }

            // Redirection vers la page historique après traitement
            header("Location: " .BASE_URL. "/pages/historique.php");
            exit;

        } catch (PDOException $e) {
            // Gestion des erreurs PDO
            $errorMsg = "Erreur PDO: " . $e->getMessage();

            
            // Annulation de la transaction en cas d'erreur
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            die($errorMsg);
        } catch (Exception $e) {
            // Gestion des autres exceptions
            $errorMsg = "Erreur: " . $e->getMessage();
            die($errorMsg);
        }
    } else {
        die("Erreur: trajet_id non fourni");
    }
} else {
    die("Erreur: Méthode non autorisée");
}