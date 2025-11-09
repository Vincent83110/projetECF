<?php 
// Inclusion des fichiers nécessaires pour l'authentification, notifications, fonctions, etc.
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/auth.php';   // Gestion de l'authentification utilisateur
include __DIR__ . '/../actions/notif.php'; 
include __DIR__ . '/../includes/function.php';          
include __DIR__ . '/../includes/headerProtection.php';  // Fonctions utilitaires
include __DIR__ . '/../includes/csrf.php';

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer l'ID du chauffeur connecté
    $chauffeurId = $_SESSION['user']['id'] ?? null;
    if (!$chauffeurId) {
        die("Utilisateur non connecté.");
    }

    // Récupérer les trajets du chauffeur (non terminés)
    $stmt = $pdo->prepare("SELECT * FROM infos_trajet WHERE id_utilisateur = :id AND (statut IS NULL OR statut != 'termine') ORDER BY date_depart DESC");
    $stmt->execute([':id' => $chauffeurId]);
    $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer le nom du chauffeur
    $stmtChauffeur = $pdo->prepare("SELECT username FROM utilisateurs WHERE id = :id");
    $stmtChauffeur->execute([':id' => $chauffeurId]);
    $chauffeur = $stmtChauffeur->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer les réservations du passager (non terminées)
    $passagerId = $_SESSION['user']['id'] ?? null;
    $stmtPassager = $pdo->prepare("
        SELECT 
            r.id AS reservation_id, 
            r.nombre_places, 
            r.trajet_id, 
            r.statut AS reservation_statut,
            i.*, 
            u.username AS conducteur_username,
            u.note AS note_conducteur
        FROM reservation r
        JOIN infos_trajet i ON r.trajet_id = i.id
        JOIN utilisateurs u ON i.id_utilisateur = u.id
        WHERE r.user_id = :id_utilisateur 
        AND r.statut != 'termine'
        ORDER BY r.id DESC
    ");

    $stmtPassager->execute([':id_utilisateur' => $passagerId]);
    $reservationsPassager = $stmtPassager->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer toutes les réservations avec les informations des passagers
    $trajetsAvecReservations = [];

    foreach ($trajets as $trajet) {
        // Récupérer les réservations de ce trajet
        $stmt2 = $pdo->prepare("SELECT * FROM reservation WHERE trajet_id = :id");
        $stmt2->execute([':id' => $trajet['id']]);
        $reservations = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $passagers = [];

        // Récupérer les noms des passagers pour chaque réservation
        foreach ($reservations as $reservation) {
            $userId = $reservation['user_id'];

            $stmtPassager = $pdo->prepare("SELECT username FROM utilisateurs WHERE id = :id");
            $stmtPassager->execute([':id' => $userId]);
            $passager = $stmtPassager->fetch(PDO::FETCH_ASSOC);

            $passagers[] = $passager ? $passager['username'] : 'Passager inconnu';
        }

        $trajetsAvecReservations[] = [
            'trajet' => $trajet,
            'passagers' => $passagers
        ];
    }

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/HistoriqueTrajet.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" 
    rel="stylesheet">
    <link rel="website icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title> Trajets </title>
</head>
<body>
    <header>
        <div class="space">
            <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <a href="<?= BASE_URL ?>/pages/accueil.php" class="menu-principal" id="menu-principal">Accueil</a>
               <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'utilisateur' && ($_SESSION['user']['statut'] === 'passager_chauffeur' || $_SESSION['user']['statut'] === 'chauffeur')): ?>
               <div>
                <div class="notif-container">
  <div class="bellNotif" id="toggleNotifications">
    <i class="fas fa-bell"></i>
    <?php if ($notificationCount > 0): ?>
    <span class="notif-badge"><?= $notificationCount ?></span>
  <?php endif; ?>
  </div>

  <?php if (!empty($notifications)): ?>
  <div class="notification" id="notificationMenu">
    <?php foreach ($notifications as $notif): ?>
      <div class="notif-item">
       <div class="notif-header">
        <div>
            <span><strong>Trajet n° <?= htmlspecialchars($notif['numero_trajet']) ?></strong></span>
        </div>
        <div>
            <a class="linkCovoit" href="<?= BASE_URL ?>/pages/pageCovoiturageIndividuelle.php?id=<?= $notif['trajet_id'] ?>">Détails...</a>
        </div>
       </div>  
        <span><?= htmlspecialchars($notif['username']) ?> à réservé votre trajet <?=  extraireVille(htmlspecialchars($notif['adresse_depart'])) ?> -> 
        <?= extraireVille(htmlspecialchars($notif['adresse_arrive'])) ?> du <?= formatDate(htmlspecialchars($notif['date_depart'])) ?></span>
        <form method="POST" action="<?= BASE_URL ?>/actions/traiter_demande.php">
            <?= csrf_input() ?>
          <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($notif['reservation_id']) ?>">
          <input type="hidden" name="notification_id" value="<?= htmlspecialchars($notif['id']) ?>">
          <div class="button-container">
            <button type="submit" name="action" value="accepter" class="buttonNotif">Accepter</button>
            <button type="submit" name="action" value="refuser" class="buttonNotif">Refuser</button>
          </div>
        </form>
        <div class="notif-container">
            <hr class="notif-separator">
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="notification" id="notificationMenu">
    <p>Aucune nouvelle demande</p>
  </div>
<?php endif; ?>

</div>      
        </div>
        <?php endif; ?>
            </div>
            <div>
                <div class="nav">
                    <div class="ContainerTitleNavConnect" id="Nav">
                      <span class="titleNav"><img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu"><img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="userNav"></span>
                    </div>
                    <div class="InsideNav" id="InsideNav">
            <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'utilisateur'): ?>
    <a href="#" class="linkNav"><?= htmlspecialchars($user['username']) ?></a>
    <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Mon compte</a>
    <a href="<?= BASE_URL ?>/pages/TrajetIndividuel.php" class="linkNav">Mes trajets</a>
    <a href="<?= BASE_URL ?>/pages/historique.php" class="linkNav">Historique</a>
    <a href="<?= BASE_URL ?>/actions/logout.php" class="linkNav">Déconnexion</a>
<?php endif; ?>

                </div>
              </div>
            </div>
        </div>     
        <div id="main">
          <button class="openbtn" id="buttonAside"><img src="<?= BASE_URL ?>/assets/images/burger.svg" alt=""></button>
        </div>
        <div class="sidebar" id="mySidebar">
        <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'utilisateur'): ?>
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#"><?= htmlspecialchars($user['username']) ?></a>
          <a href="<?= BASE_URL ?>/pages/accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/TrajetIndividuel.php">Mes trajets</a>
          <a href="<?= BASE_URL ?>/pages/historique.php">Historique</a>
          <a href="<?= BASE_URL ?>/pages/contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= htmlspecialchars($lienCompte) ?>">Mon compte</a>
          <a href="<?= BASE_URL ?>/actions/logout.php">Déconnexion</a>
        <?php endif; ?>
          <div class="ReseauxRow">
            <a href="https://www.youtube.com/" class="gapReseauxPop" target="_blank"><i class="fab fa-youtube"></i></a>
            <a href="https://www.instagram.com/" class="gapReseauxPop" target="_blank"><i class="fab fa-instagram"></i></a>
            <a href="https://x.com/?lang=fr&mx=2" class="gapReseauxPop" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>
            <a href="https://fr.pinterest.com/" class="gapReseauxPop" target="_blank"><i class="fab fa-pinterest"></i></a>
          </div>
        </div>
    </header>
    <main id="pop">
    <div class="entete">
        <h1>Mes trajets</h1>
    </div>
    <?php if (isset($_SESSION['user']) && $_SESSION['user']['statut'] === 'passager_chauffeur'): ?>
      <div class="buttonChange">
      <button class="button1change">Passager</button>
      <button class="button2change">Chauffeur</button>
    </div>
    <div class="container">
        <div class="col1">
    <span class="textPassager">Passager</span>

    <?php if (!empty($reservationsPassager)): ?>
      <?php foreach ($reservationsPassager as $res): ?>
            <div class="text-div1">
                <div class="col1div1">
                    <div class="inCol1div1">
                        <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="imgDiv">
                        <div class="NoteName">
                            <span>★ <?= htmlspecialchars($res['note_conducteur'] === null ? '--' : entierOuDecimal(number_format($res['note_conducteur'], 2))) ?>/5</span>
                            <span><?= htmlspecialchars($res['conducteur_username'] ?? 'Conducteur inconnu') ?></span>
                        </div>
                    </div>
<div class="containerWait">
    <?php 
        $statut = strtolower(trim($res['reservation_statut'] ?? ''));
    ?>
    <?php if ($statut === 'en_attente'): ?>
        <span class="wait">En attente de réponse ...</span>
    <?php elseif ($statut === 'acceptée'): ?>
        <span class="wait">Réservation confirmée</span>
    <?php elseif ($statut === 'en_cours'): ?>
        <span class="wait">en cours ...</span>
    <?php elseif ($statut === 'annulée'): ?>
        <span class="wait">Réservation annulée</span>
    <?php elseif ($statut === 'termine'): ?>
        <span class="wait">Trajet terminé</span>
    <?php else: ?>
        <span class="wait">Statut inconnu : <?= htmlspecialchars($statut) ?></span>
    <?php endif; ?>
    
</div>


                    <div class="containerSubmit">
    <form method="POST" action="<?= BASE_URL ?>/actions/annulerReservationPassager.php" class="formAnnulation">
        <?= csrf_input() ?>
        <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($res['reservation_id']) ?>">
        <input type="hidden" name="prix" value="<?= htmlspecialchars($res['prix']) ?>">

        <button type="button" class="buttonSubmit openPopupBtn">Annuler</button>

        <!-- Popup modale -->
        <div class="modal-overlay" style="display:none;">
            <div class="modal">
                <p>Voulez-vous vraiment annuler cette réservation ?</p>
                <div class="modal-buttons">
                    <button type="submit" class="confirmBtn">Oui</button> <!-- Ce bouton soumet le formulaire -->
                    <button type="button" class="cancelBtn">Non</button>
                </div>
            </div>
        </div>
    </form>
</div>
                </div>
                <div class="col2div1">
                    <div class="inCol1div2">
                        <div class="DateTrajet">
                            <span><?= htmlspecialchars(formatDate($res['date_depart'])) ?></span>
                            <?= strtolower($res['trajet_ecologique']) === 'oui' ? '<span class="trajet"><img src="'. BASE_URL .'/assets/images/trajet-ecologique.svg" alt="écolo" class="imgEcolo"> Trajet écolo</span>' : '' ?>
                        </div>
                        <div>
                            <a class="linkCovoit" href="<?= BASE_URL ?>/pages/pageCovoiturageIndividuelle.php?id=<?= $res['id'] ?>">Détails...</a>
                        </div>
                    </div>
                    <div class="div1">
                        <div class="town">
                            <span class="townDiv1"><?= htmlspecialchars(extraireVille($res['adresse_depart'])) ?></span>
                            <span class="townDiv2"><?= htmlspecialchars(extraireVille($res['adresse_arrive'])) ?></span>
                        </div>
                        <div class="time">
                            <hr class="hrCol2div1">
                            <span><?= getDuree($res['date_depart'], $res['heure_depart'], $res['date_arrive'], $res['heure_arrive']) ?></span>
                            <hr class="hrCol2div1">
                        </div>
                        <div class="departureDestination">
                            <span>Départ : <?= htmlspecialchars(formatTime($res['heure_depart'])) ?></span>
                            <span>Arrivé : <?= htmlspecialchars(formatTime($res['heure_arrive'])) ?></span>
                        </div>
                    </div>
                    <div>
                        <div class="NumberCredit">
                            <div>
                                <span>N° <?= htmlspecialchars($res['numero_trajet']) ?></span>
                            </div>
                            <div class="PlaceCredit">
                                <span><?= $res['nombre_place'] ?> place<?= $res['nombre_place'] > 1 ? 's' : '' ?></span>
                                <span><?= $res['prix'] ?> crédit<?= $res['prix'] > 1 ? 's' : '' ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
      <?php endforeach; ?>
    <?php else: ?>
        <p>Aucune réservation trouvé.</p>
    <?php endif; ?>
</div>

        <div class="ligne">
        </div>
        <div class="col2">
          <span class="textChauffeur">Chauffeur</span>
        <?php
$trajetsConfirmesCookie = [];
if (isset($_COOKIE['trajets_confirmes'])) {
    $trajetsConfirmesCookie = json_decode($_COOKIE['trajets_confirmes'], true);
}
?>

<?php if (!empty($trajets)): ?>
    <?php foreach ($trajets as $trajet): ?>
        <?php
        // Si le trajet est confirmé, on le saute
        if (in_array($trajet['id'], $trajetsConfirmesCookie)) {
            continue;
        }

        // Récupération du conducteur
        $stmtUser = $pdo->prepare("SELECT username, email, note FROM utilisateurs WHERE id = :id");
        $stmtUser->execute([':id' => $trajet['id_utilisateur']]);
        $userConducteur = $stmtUser->fetch(PDO::FETCH_ASSOC);
        ?>
            <div class="text-div1">
                <div class="col1div1">
                    <div class="inCol1div1">
                        <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="imgDiv">
                        <div class="NoteName">
                            <span>★ <?= $userConducteur['note'] === null ? '--' : (entierOuDecimal(number_format($userConducteur['note'], 2))) ?>/5</span>
                            <span><?= htmlspecialchars($userConducteur['username'] ?? 'Conducteur inconnu') ?></span>
                        </div>
                    </div>
                    <div class="containerWait">
                        <span class="wait"></span>
                    </div>
                <div class="containerForm">
                <?php if ($trajet['statut'] !== 'en_cours'): ?>
                    <form method="POST" action="<?= BASE_URL ?>/actions/annulerReservationChauffeur.php" class="formAnnulation">
                        <?= csrf_input() ?>
    <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id']) ?>">
    <input type="hidden" name="action" value="annuler">

    <div class="containerSubmit">
        <button type="button" class="buttonSubmit openPopupBtnAnnuler">Annuler</button>
    </div>

    <div class="modal-overlay" style="display:none;">
        <div class="modal">
            <p>Voulez-vous vraiment annuler ce trajet ?</p>
            <div class="modal-buttons">
                <button type="submit" class="confirmBtn">Oui</button>
                <button type="button" class="cancelBtn">Non</button>
            </div>
        </div>
    </div>
</form>
<?php endif; ?>
<div class="bloc-action-trajet">
    <?php $statut = $trajet['statut'] ?? null; ?>

    <?php if (empty($statut)): ?>
        <!-- Formulaire pour lancer ce trajet -->
        <form method="POST" action="<?= BASE_URL ?>/actions/LancerTrajetChauffeur.php" class="formAnnulation">
            <?= csrf_input() ?>
            <input type="hidden" name="numero_trajet" value="<?= htmlspecialchars($trajet['numero_trajet']) ?>">
            <input type="hidden" name="action" value="lancer">
            <div class="containerSubmit">
                <button type="button" class="buttonSubmit openPopupBtnLancer">Lancer</button>
            </div>
            <div class="modal-overlay" style="display:none;">
                <div class="modal">
                    <p>Voulez-vous vraiment lancer ce trajet ?</p>
                    <div class="modal-buttons">
                        <button type="submit" class="confirmBtn">Oui</button>
                        <button type="button" class="cancelBtn">Non</button>
                    </div>
                </div>
            </div>
        </form>

    <?php elseif ($statut === 'en_cours'): ?>
        <!-- Statut en cours + bouton confirmer -->
        <div class="containerWait">
            <span class="wait">En cours ...</span>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/actions/confirmeTrajet.php">
            <?= csrf_input() ?>
            <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id']) ?>">
            <div class="containerSubmit">
                <button type="submit" class="buttonSubmit">Confirmer</button>
            </div>
        </form>
    <?php endif; ?>
</div>
</div>
                </div>
                <div class="col2div1">
                    <div class="inCol1div2">
                        <div class="DateTrajet">
                            <span><?= htmlspecialchars(formatDate($trajet['date_depart'])) ?></span>
                            <span><?= strtolower($trajet['trajet_ecologique']) === 'oui' ? '<span class="trajet"><img src="'. BASE_URL .'/assets/images/trajet-ecologique.svg" alt="écolo" class="imgEcolo"> Trajet écolo</span>' : '' ?></span>
                        </div>
                        <div>
                            <a class="linkCovoit" href="<?= BASE_URL ?>/pages/pageCovoiturageIndividuelle.php?id=<?= $trajet['id'] ?>">Détails...</a>
                        </div>
                    </div>
                    <div class="div1">
                        <div class="town">
                            <span class="townDiv1"><?= htmlspecialchars(extraireVille($trajet['adresse_depart'])) ?></span>
                            <span class="townDiv2"><?= htmlspecialchars(extraireVille($trajet['adresse_arrive'])) ?></span>
                        </div>
                        <div class="time">
                            <hr class="hrCol2div1">
                            <span><?= getDuree($trajet['date_depart'], $trajet['heure_depart'], $trajet['date_arrive'], $trajet['heure_arrive']) ?></span>
                            <hr class="hrCol2div1">
                        </div>
                        <div class="departureDestination">
                            <span>Départ : <?= htmlspecialchars(formatTime($trajet['heure_depart'])) ?></span>
                            <span>Arrivé : <?= htmlspecialchars(formatTime($trajet['heure_arrive'])) ?></span>
                        </div>
                    </div>
                    <div>
                        <div class="NumberCredit">
                            <div>
                                <span>N° <?= htmlspecialchars($trajet['numero_trajet']) ?></span>
                            </div>
                            <div class="PlaceCredit">
                                <span><?= $trajet['nombre_place'] ?> place<?= $trajet['nombre_place'] > 1 ? 's' : '' ?></span>
                                <span><?= $trajet['prix'] ?> crédit<?= $trajet['prix'] > 1 ? 's' : '' ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
      <?php endforeach; ?>
    <?php else: ?>
        <p>Aucun trajet trouvé.</p>
    <?php endif; ?>
        </div>
    </div>
<?php elseif (isset($_SESSION['user']) && $_SESSION['user']['statut'] === 'passager'): ?>
    <span class="textChauffeur2">Passager</span>
    <div class="container2">
      <?php if (!empty($reservationsPassager)): ?>
      <?php foreach ($reservationsPassager as $res): ?>
            <div class="text-div1">
                <div class="col1div1">
                    <div class="inCol1div1">
                        <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="imgDiv">
                        <div class="NoteName">
                            <span>★ <?= htmlspecialchars($res['note_conducteur'] === null ? '--' : entierOuDecimal(number_format($res['note_conducteur'], 2))) ?>/5</span>
                            <span><?= htmlspecialchars($res['conducteur_username'] ?? 'Conducteur inconnu') ?></span>
                        </div>
                    </div>
                    <?php
?>
<div class="containerWait">
    <?php 
        $statut = strtolower(trim($res['reservation_statut'] ?? ''));
    ?>
    <?php if ($statut === 'en_attente'): ?>
        <span class="wait">En attente de réponse ...</span>
    <?php elseif ($statut === 'acceptée'): ?>
        <span class="wait">Réservation confirmée</span>
    <?php elseif ($statut === 'en_cours'): ?>
        <span class="wait">en cours ...</span>
    <?php elseif ($statut === 'annulée'): ?>
        <span class="wait">Réservation annulée</span>
    <?php elseif ($statut === 'termine'): ?>
        <span class="wait">Trajet terminé</span>
    <?php else: ?>
        <span class="wait">Statut inconnu : <?= htmlspecialchars($statut) ?></span>
    <?php endif; ?>
    
</div>


                    <div class="containerSubmit">
    <form method="POST" action="<?= BASE_URL ?>/actions/annulerReservationPassager.php" class="formAnnulation">
        <?= csrf_input() ?>
        <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($res['reservation_id']) ?>">
        <input type="hidden" name="prix" value="<?= htmlspecialchars($res['prix']) ?>">

        <button type="button" class="buttonSubmit openPopupBtn">Annuler</button>

        <!-- Popup modale -->
        <div class="modal-overlay" style="display:none;">
            <div class="modal">
                <p>Voulez-vous vraiment annuler cette réservation ?</p>
                <div class="modal-buttons">
                    <button type="submit" class="confirmBtn">Oui</button> <!-- Ce bouton soumet le formulaire -->
                    <button type="button" class="cancelBtn">Non</button>
                </div>
            </div>
        </div>
    </form>
</div>
                </div>
                <div class="col2div1">
                    <div class="inCol1div2">
                        <div class="DateTrajet">
                            <span><?= htmlspecialchars(formatDate($res['date_depart'])) ?></span>
                            <?= strtolower($res['trajet_ecologique']) === 'oui' ? '<span class="trajet"><img src="'. BASE_URL .'/assets/images/trajet-ecologique.svg" alt="écolo" class="imgEcolo"> Trajet écolo</span>' : '' ?>
                        </div>
                        <div>
                            <a class="linkCovoit" href="<?= BASE_URL ?>/pages/pageCovoiturageIndividuelle.php?id=<?= $res['id'] ?>">Détails...</a>
                        </div>
                    </div>
                    <div class="div1">
                        <div class="town">
                            <span class="townDiv1"><?= htmlspecialchars(extraireVille($res['adresse_depart'])) ?></span>
                            <span class="townDiv2"><?= htmlspecialchars(extraireVille($res['adresse_arrive'])) ?></span>
                        </div>
                        <div class="time">
                            <hr class="hrCol2div1">
                            <span><?= getDuree($res['date_depart'], $res['heure_depart'], $res['date_arrive'], $res['heure_arrive']) ?></span>
                            <hr class="hrCol2div1">
                        </div>
                        <div class="departureDestination">
                            <span>Départ : <?= htmlspecialchars(formatTime($res['heure_depart'])) ?></span>
                            <span>Arrivé : <?= htmlspecialchars(formatTime($res['heure_arrive'])) ?></span>
                        </div>
                    </div>
                    <div>
                        <div class="NumberCredit">
                            <div>
                                <span>N° <?= htmlspecialchars($res['numero_trajet']) ?></span>
                            </div>
                            <div class="PlaceCredit">
                                <span><?= $res['nombre_place'] ?> place<?= $res['nombre_place'] > 1 ? 's' : '' ?></span>
                                <span><?= $res['prix'] ?> crédit<?= $res['prix'] > 1 ? 's' : '' ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
      <?php endforeach; ?>
    <?php else: ?>
        <p>Aucune réservation trouvé.</p>
    <?php endif; ?>
</div>
        </div>
    </div>
     <?php elseif (isset($_SESSION['user']) && $_SESSION['user']['statut'] === 'chauffeur'): ?>
  <span class="textChauffeur2">Chauffeur</span>
  <div class="container3">
        <?php
$trajetsConfirmesCookie = [];
if (isset($_COOKIE['trajets_confirmes'])) {
    $trajetsConfirmesCookie = json_decode($_COOKIE['trajets_confirmes'], true);
}
?>

<?php if (!empty($trajets)): ?>
    <?php foreach ($trajets as $trajet): ?>
        <?php
        // Si le trajet est confirmé, on le saute
        if (in_array($trajet['id'], $trajetsConfirmesCookie)) {
            continue;
        }

        // Récupération du conducteur
        $stmtUser = $pdo->prepare("SELECT username, email, note FROM utilisateurs WHERE id = :id");
        $stmtUser->execute([':id' => $trajet['id_utilisateur']]);
        $userConducteur = $stmtUser->fetch(PDO::FETCH_ASSOC);
        ?>
            <div class="text-div1">
                <div class="col1div1">
                    <div class="inCol1div1">
                        <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="imgDiv">
                        <div class="NoteName">
                            <span>★ <?= $userConducteur['note'] === null ? '--' : (entierOuDecimal(number_format($userConducteur['note'], 2))) ?>/5</span>
                            <span><?= htmlspecialchars($userConducteur['username'] ?? 'Conducteur inconnu') ?></span>
                        </div>
                    </div>
                    <div class="containerWait">
                        <span class="wait"></span>
                    </div>
                <div class="containerForm">
                <?php if ($trajet['statut'] !== 'en_cours'): ?>
                    <form method="POST" action="<?= BASE_URL ?>/actions/annulerReservationChauffeur.php" class="formAnnulation">
                        <?= csrf_input() ?>
    <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id']) ?>">
    <input type="hidden" name="action" value="annuler">

    <div class="containerSubmit">
        <button type="button" class="buttonSubmit openPopupBtnAnnuler">Annuler</button>
    </div>

    <div class="modal-overlay" style="display:none;">
        <div class="modal">
            <p>Voulez-vous vraiment annuler ce trajet ?</p>
            <div class="modal-buttons">
                <button type="submit" class="confirmBtn">Oui</button>
                <button type="button" class="cancelBtn">Non</button>
            </div>
        </div>
    </div>
</form>
<?php endif; ?>
<div class="bloc-action-trajet">
    <?php $statut = $trajet['statut'] ?? null; ?>

    <?php if (empty($statut)): ?>
        <!-- Formulaire pour lancer ce trajet -->
        <form method="POST" action="<?= BASE_URL ?>/actions/LancerTrajetChauffeur.php" class="formAnnulation">
            <?= csrf_input() ?>
            <input type="hidden" name="numero_trajet" value="<?= htmlspecialchars($trajet['numero_trajet']) ?>">
            <input type="hidden" name="action" value="lancer">
            <div class="containerSubmit">
                <button type="button" class="buttonSubmit openPopupBtnLancer">Lancer</button>
            </div>
            <div class="modal-overlay" style="display:none;">
                <div class="modal">
                    <p>Voulez-vous vraiment lancer ce trajet ?</p>
                    <div class="modal-buttons">
                        <button type="submit" class="confirmBtn">Oui</button>
                        <button type="button" class="cancelBtn">Non</button>
                    </div>
                </div>
            </div>
        </form>

    <?php elseif ($statut === 'en_cours'): ?>
        <!-- Statut en cours + bouton confirmer -->
        <div class="containerWait">
            <span class="wait">En cours ...</span>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/actions/confirmeTrajet.php">
            <?= csrf_input() ?>
            <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id']) ?>">
            <div class="containerSubmit">
                <button type="submit" class="buttonSubmit">Confirmer le trajet</button>
            </div>
        </form>
    <?php endif; ?>
</div>
</div>
                </div>
                <div class="col2div1">
                    <div class="inCol1div2">
                        <div class="DateTrajet">
                            <span><?= htmlspecialchars(formatDate($trajet['date_depart'])) ?></span>
                            <span><?= strtolower($trajet['trajet_ecologique']) === 'oui' ? '<span class="trajet"><img src="'. BASE_URL .'/assets/images/trajet-ecologique.svg" alt="écolo" class="imgEcolo"> Trajet écolo</span>' : '' ?></span>
                        </div>
                        <div>
                            <a class="linkCovoit" href="<?= BASE_URL ?>/pages/pageCovoiturageIndividuelle.php?id=<?= $trajet['id'] ?>">Détails...</a>
                        </div>
                    </div>
                    <div class="div1">
                        <div class="town">
                            <span class="townDiv1"><?= htmlspecialchars(extraireVille($trajet['adresse_depart'])) ?></span>
                            <span class="townDiv2"><?= htmlspecialchars(extraireVille($trajet['adresse_arrive'])) ?></span>
                        </div>
                        <div class="time">
                            <hr class="hrCol2div1">
                            <span><?= getDuree($trajet['date_depart'], $trajet['heure_depart'], $trajet['date_arrive'], $trajet['heure_arrive']) ?></span>
                            <hr class="hrCol2div1">
                        </div>
                        <div class="departureDestination">
                            <span>Départ : <?= htmlspecialchars(formatTime($trajet['heure_depart'])) ?></span>
                            <span>Arrivé : <?= htmlspecialchars(formatTime($trajet['heure_arrive'])) ?></span>
                        </div>
                    </div>
                    <div>
                        <div class="NumberCredit">
                            <div>
                                <span>N° <?= htmlspecialchars($trajet['numero_trajet']) ?></span>
                            </div>
                            <div class="PlaceCredit">
                                <span><?= $trajet['nombre_place'] ?> place<?= $trajet['nombre_place'] > 1 ? 's' : '' ?></span>
                                <span><?= $trajet['prix'] ?> crédit<?= $trajet['prix'] > 1 ? 's' : '' ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
      <?php endforeach; ?>
    <?php else: ?>
        <p>Aucun trajet trouvé.</p>
    <?php endif; ?>
    <?php endif; ?>
</div>


    </main>
    <footer> 
        <div>
            <a href="<?= BASE_URL ?>/pages/MentionsLegales.php" class="mentions-legales">mentions légales</a>
            <a href="<?= BASE_URL ?>/pages/contact.php" class="mentions-legales"> contact </a>
        </div>
        <div>
            <a href="https://www.youtube.com/" class="gapReseaux" target="_blank"><i class="fab fa-youtube"></i></a>
            <a href="https://www.instagram.com/" class="gapReseaux" target="_blank"><i class="fab fa-instagram"></i></a>
            <a href="https://x.com/?lang=fr&mx=2" class="gapReseaux" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>
            <a href="https://fr.pinterest.com/" class="gapReseaux" target="_blank"><i class="fab fa-pinterest"></i></a>
        </div>
    </footer>
        <script>
        const BASE_URL = "<?= BASE_URL ?>";
    </script>
    <script src="<?= BASE_URL ?>/assets/javascript/menu.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/notif.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/TrajetIndividuel.js"></script>
</body>
</html>