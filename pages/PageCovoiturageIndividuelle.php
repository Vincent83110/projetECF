<?php
// Inclusion des fichiers nécessaires pour l'authentification, les notifications, les fonctions utilitaires, la configuration, la sécurité et la protection CSRF
require_once __DIR__ . '/../includes/Config.php';
include __DIR__ . '/../includes/Auth.php';   // Gestion de l'authentification utilisateur
include __DIR__ . '/../actions/Notif.php'; 
include __DIR__ . '/../includes/Function.php';          
include __DIR__ . '/../includes/HeaderProtection.php';  // Fonctions utilitaires
include __DIR__ . '/../includes/Csrf.php';

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $trajetId = null;

    // === RÉCUPÉRATION DU TRAJET ===
    // Vérification et récupération de l'ID du trajet via le numéro de trajet
    if (isset($_GET['numero_trajet'])) {
        $numero = $_GET['numero_trajet'];
        $stmt = $pdo->prepare("SELECT id FROM infos_trajet WHERE numero_trajet = ?");
        $stmt->execute([$numero]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Vérification que le trajet existe
        if (!$result) {
            die("Aucun trajet trouvé.");
        }

        $trajetId = $result['id'];
    } elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
        // Récupération directe par ID
        $trajetId = (int)$_GET['id'];
    } else {
        die("Identifiant de trajet invalide.");
    }

    // === AFFICHAGE DU TRAJET ===
    // Récupération des informations complètes du trajet
    $stmtTrajet = $pdo->prepare("SELECT * FROM infos_trajet WHERE id = :id");
    $stmtTrajet->execute([':id' => $trajetId]);
    $trajet = $stmtTrajet->fetch(PDO::FETCH_ASSOC);

    // Vérification que le trajet a été trouvé
    if (!$trajet) die("Trajet introuvable.");

    // Calcul du prix total en fonction du nombre de places demandées
    $places_demandees = isset($_GET['passengers']) ? (int)$_GET['passengers'] : 1;
    $prix_total = isset($trajet['prix']) ? $trajet['prix'] * $places_demandees : 0;

    $userId = $_SESSION['user']['id'] ?? null;

    // === TRAITEMENT PARTICIPATION ===
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $trajetId = $_POST['trajet_id'] ?? null;

        // Vérification des données requises
        if (!$userId || !$trajetId) {
            throw new Exception("Données manquantes.");
        }

        // Vérification du prix du trajet
        $stmt1 = $pdo->prepare("SELECT prix FROM infos_trajet WHERE id = :trajet_id");
        $stmt1->execute([':trajet_id' => $trajetId]);
        $t = $stmt1->fetch(PDO::FETCH_ASSOC);
        if (!$t) throw new Exception("Trajet introuvable.");

        $prix = (int)$t['prix'];
        $places_demandees = $_POST['passengers'] ?? 1;
        $prix_total = $prix * $places_demandees;

        // Récupération des crédits de l'utilisateur
        $stmt2 = $pdo->prepare("SELECT credits FROM utilisateurs WHERE user_id = :user_id");
        $stmt2->execute([':user_id' => $userId]);
        $user = $stmt2->fetch(PDO::FETCH_ASSOC);
        if (!$user) throw new Exception("Utilisateur introuvable.");

        $creditsActuels = (int)$user['credits'];
        // Vérification que l'utilisateur a assez de crédits
        if ($creditsActuels < $prix_total) throw new Exception("Crédits insuffisants.");

        // Mise à jour des crédits de l'utilisateur
        $nouveauxCredits = $creditsActuels - $prix_total;
        $stmt3 = $pdo->prepare("UPDATE utilisateurs SET credits = :credits WHERE user_id = :user_id");
        $stmt3->execute([':credits' => $nouveauxCredits, ':user_id' => $userId]);

        // Vérification du nombre de places disponibles
        $stmtCheckPlaces = $pdo->prepare("SELECT nombre_place FROM infos_trajet WHERE id = :trajet_id");
        $stmtCheckPlaces->execute([':trajet_id' => $trajetId]);
        $trajetPlaces = $stmtCheckPlaces->fetch(PDO::FETCH_ASSOC);

        if ($trajetPlaces && $trajetPlaces['nombre_place'] < $places_demandees) {
            throw new Exception("Pas assez de places disponibles.");
        }

        // Mise à jour du nombre de places disponibles
        $stmt4 = $pdo->prepare("UPDATE infos_trajet SET nombre_place = nombre_place - :places WHERE id = :trajet_id");
        $stmt4->execute([
            ':places' => $places_demandees,
            ':trajet_id' => $trajetId
        ]);

        // Retour d'une réponse JSON en cas de succès
        echo json_encode(['success' => true, 'credits_restants' => $nouveauxCredits]);
        exit;
    }

    // === RÉCUPÉRATION INFOS CONDUCTEUR ===
    $userConducteur = null;
    if (isset($trajet['id_utilisateur'])) {
        $stmtUser = $pdo->prepare("SELECT username, email FROM utilisateurs WHERE id = :id");
        $stmtUser->execute([':id' => $trajet['id_utilisateur']]);
        $userConducteur = $stmtUser->fetch(PDO::FETCH_ASSOC);
    }

    // === VÉHICULE ===
    $vehicule = null;
    if (!empty($trajet['id_vehicule'])) {
        $stmtVehicule = $pdo->prepare("SELECT * FROM vehicules WHERE id = :id");
        $stmtVehicule->execute([':id' => $trajet['id_vehicule']]);
        $vehicule = $stmtVehicule->fetch(PDO::FETCH_ASSOC);
    }

    // === PRÉFÉRENCES ===
    $stmtPreferences = $pdo->prepare("SELECT texte FROM preferences WHERE trajet_id = :trajet_id");
    $stmtPreferences->execute([':trajet_id' => $trajetId]);
    $preferences = $stmtPreferences->fetchAll(PDO::FETCH_COLUMN);

    // Après la récupération des infos du conducteur, ajoutez :
    if (isset($trajet['id_utilisateur'])) {
        $stmtNote = $pdo->prepare("SELECT note FROM utilisateurs WHERE id = :id");
        $stmtNote->execute([':id' => $trajet['id_utilisateur']]);
        $noteConducteur = $stmtNote->fetch(PDO::FETCH_ASSOC);
        
        // Si la note n'existe pas, on met une valeur par défaut
        $noteMoyenne = $noteConducteur['note'] ?? null;
    }

    $avis = [];
    if (isset($trajet['id_utilisateur'])) {
        // Définir $profil_id avant de l'utiliser
        $profil_id = $trajet['id_utilisateur'];
        
        // Récupération des avis pour le conducteur
        $stmtAvis = $pdo->prepare("
            SELECT 
                a.*,
                u.username AS auteur_username
            FROM avis a
            JOIN infos_trajet t ON a.id_trajet = t.id
            JOIN utilisateurs u ON a.id_utilisateur = u.id
            WHERE t.id_utilisateur = :id_chauffeur
            AND a.id_utilisateur != t.id_utilisateur
            ORDER BY a.id DESC
            LIMIT 3
        ");
        $stmtAvis->execute([':id_chauffeur' => $profil_id]);
        $avis = $stmtAvis->fetchAll(PDO::FETCH_ASSOC);
    }

    // === PARTICIPANTS ===
    $trajetId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    // Préparer la requête pour récupérer les utilisateurs liés à ce trajet
    $sql = "SELECT u.id, u.username
            FROM reservation r
            INNER JOIN utilisateurs u ON r.user_id = u.id
            WHERE r.trajet_id = :trajet_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['trajet_id' => $trajetId]);

    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Gestion des erreurs avec code HTTP 400
    http_response_code(400);
    echo "Erreur : " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/PageCovoiturageIndividuelle.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" 
    rel="stylesheet">
    <link rel="icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title> Page Covoiturage Individuel </title>
</head>
<body>
    <header>
        <div class="space">
            <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <?php if ($estConnecte): ?>
                <a href="<?= BASE_URL ?>/pages/Accueil.php" class="menu-principal" id="menu-principal">Accueil</a>
              <?php else: ?>
                <a href="<?= BASE_URL ?>/AccueilECF.php" class="menu-principal" id="menu-principal">Accueil</a>
              <?php endif; ?>
               <div>
            <?php if ($estConnecte && $_SESSION['user']['role'] === 'utilisateur'): ?>
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
            <a class="linkCovoit" href="<?= BASE_URL ?>/pages/PageCovoiturageIndividuelle.php?id=<?= $notif['trajet_id'] ?>">Détails...</a>
        </div>
       </div>  
        <span><?= htmlspecialchars($notif['username']) ?> à réservé votre trajet <?=  extraireVille(htmlspecialchars($notif['adresse_depart'])) ?> -> 
        <?= extraireVille(htmlspecialchars($notif['adresse_arrive'])) ?> du <?= formatDate(htmlspecialchars($notif['date_depart'])) ?></span>
        <form method="POST" action="<?= BASE_URL ?>/actions/TraiterDemande.php">
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
<?php endif; ?>

        </div>
            </div>
      <?php if ($estConnecte): ?>
                <div class="nav">
                    <div class="ContainerTitleNavConnect" id="Nav">
                      <span class="titleNav"><img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu"><img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="userNav"></span>
                    </div>
                    <div class="InsideNav" id="InsideNav">
            <?php elseif (!$estConnecte): ?>
        <div>
                <div class="nav2">
                    <div class="ContainerTitleNav" id="Nav">
                      <span class="titleNavHtml"><img src="<?= BASE_URL ?>/assets/images/iconConnect.svg" alt=""> Mon compte <img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu"></span>
                    </div>
                    <div class="InsideNav" id="InsideNav">
            <?php endif; ?>
            <?php if ($estConnecte && $role === 'employe'): ?>
    <a href="#" class="linkNav"><?= htmlspecialchars($employe['prenom'] ?? 'Employé') ?></a>
    <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Mon compte</a>
    <a href="<?= BASE_URL ?>/actions/Logout.php" class="linkNav">Déconnexion</a>

<?php elseif ($estConnecte && $role === 'admin'): ?>
    <a href="#" class="linkNav">Admin</a>
    <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Compte Admin</a>
    <a href="<?= BASE_URL ?>/actions/LogoutAdmin.php" class="linkNav">Déconnexion</a>

<?php elseif ($estConnecte): ?>
    <a href="#" class="linkNav"><?= htmlspecialchars($user['username'] ?? 'Utilisateur') ?></a>
    <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Mon compte</a>
    <a href="<?= BASE_URL ?>/pages/TrajetIndividuel.php" class="linkNav">Mes trajets</a>
    <a href="<?= BASE_URL ?>/pages/Historique.php" class="linkNav">Historique</a>
    <a href="<?= BASE_URL ?>/actions/Logout.php" class="linkNav">Déconnexion</a>
<?php elseif (!$estConnecte): ?>
                <a href="<?= BASE_URL ?>/pages/ConnexionUtilisateur.php" class="linkNav">Connexion</a>
                  <a href="<?= BASE_URL ?>/pages/InscriptionECF.php" class="linkNav">Inscription</a>
                  <hr>
                  <a href="<?= BASE_URL ?>/pages/ConnexionEmploye.php" class="linkNav">Compte Pro</a>
                </div>
              </div>
            </div>
<?php endif; ?>
  </div>
</div>

        </div>     
        <div id="main">
          <button class="openbtn" id="buttonAside"><img src="<?= BASE_URL ?>/assets/images/burger.svg" alt=""></button>
        </div>
        <div class="sidebar" id="mySidebar">
          <?php if ($estConnecte && $role === 'employe'): ?>
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#" class="linkNav"><?= htmlspecialchars($employe['prenom'] ?? 'Employé') ?></a>
          <a href="<?= BASE_URL ?>/pages/Accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/Contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= htmlspecialchars($lienCompte) ?>">Compte Pro</a>
          <a href="<?= BASE_URL ?>/actions/Logout.php">Déconnexion</a>
          
        <?php elseif ($estConnecte && $role === 'admin'): ?>
            <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#">Admin</a>
          <a href="<?= BASE_URL ?>/pages/Accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/Contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= htmlspecialchars($lienCompte) ?>">Compte Admin</a>
          <a href="<?= BASE_URL ?>/actions/LogoutAdmin.php">Déconnexion</a>
       
        <?php elseif ($estConnecte): ?>
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#"><?= htmlspecialchars($user['username'] ?? 'Utilisateur') ?></a>
          <a href="<?= BASE_URL ?>/pages/Accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/TrajetIndividuel.php">Mes trajets</a>
          <a href="<?= BASE_URL ?>/pages/Historique.php">Historique</a>
          <a href="<?= BASE_URL ?>/pages/Contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= htmlspecialchars($lienCompte) ?>">Mon compte</a>
          <a href="<?= BASE_URL ?>/actions/Logout.php">Déconnexion</a>
        <?php else: ?>
          <!-- Sidebar non connecté -->
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="<?= BASE_URL ?>/pages/Accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/Contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= BASE_URL ?>/pages/ConnexionUtilisateur.php">Connexion</a>
          <a href="<?= BASE_URL ?>/pages/Inscription.php">Inscription</a>
          <a href="<?= BASE_URL ?>/pages/ConnexionEmploye.php">Compte Pro</a>
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
        <form action="<?= BASE_URL ?>/actions/Reservation.php" method="post">
            <?= csrf_input() ?>
        <div class="entete">
            <h1><span><?= formatDate($trajet['date_depart']) ?> - <?= formatDate($trajet['date_arrive']) ?>  <span class="numeroTrajet">N°<?= $trajet['numero_trajet'] ?></span></span> </h1>
        </div>
        <div class="container">
            <div class="col1">
                <div class="profil">
                    <div class="imageProfil">
                        <img src="<?= BASE_URL ?>/assets/images/profil.svg" class="ImageProfil" alt="">
                    </div>
                    <div class="infosProfil">
                        <span class="note">★ <?= $noteMoyenne !== null ? htmlspecialchars(entierOuDecimal(number_format($noteMoyenne, 2))) : '--' ?>/5</span>
                    </span>
                        <div class="NameEmail">
                            <a href="<?= BASE_URL ?>/actions/Compte.php?id=<?= htmlspecialchars($trajet['id_utilisateur']) ?>" class="usernameCovoit"><?= htmlspecialchars($userConducteur['username'] ?? 'Conducteur inconnu') ?></a>
                            <span class="email"><?= htmlspecialchars($userConducteur['email'] ?? 'Non disponible') ?></span>
                        </div>
                    </div>
                </div>
                <div class="div1">
                    <div class="town">
                        <span><?= extraireVille($trajet['adresse_depart']) ?></span>
                        <span><?= extraireVille($trajet['adresse_arrive']) ?></span>
                    </div>
                    <div class="time">
                        <hr>
                        <span><?= getDuree($trajet['date_depart'], $trajet['heure_depart'], $trajet['date_arrive'], $trajet['heure_arrive']) ?></span>
                        <hr>
                    </div>
                    <div class="departureDestination">
                        <span>Départ : <?= formatTime($trajet['heure_depart']) ?></span>
                        <span>Arrivé : <?= formatTime($trajet['heure_arrive']) ?></span>
                    </div>
                </div>
                <div class="div2">
                    <div class="containerTrajet">
                        <div class="trajet">
                       <?php if ($trajet['trajet_ecologique'] === 'Oui') : ?>
    <img src="<?= BASE_URL ?>/assets/images/trajet-ecologique.svg" alt="">
    <span>
        Trajet écologique
        <?php if ($vehicule): ?>
            - <?= htmlspecialchars($vehicule['marque']) ?> <?= htmlspecialchars($vehicule['modele']) ?>
            - <?= htmlspecialchars($vehicule['energie']) ?>
        <?php endif; ?>
    </span>
<?php elseif ($vehicule): ?>
    <span>
        <?= htmlspecialchars($vehicule['marque']) ?> <?= htmlspecialchars($vehicule['modele']) ?>
        - <?= htmlspecialchars($vehicule['energie']) ?>
    </span>
<?php endif; ?>


                        </div>
                        <span class="placePop"><?= $trajet['nombre_place'] ?> place<?= $trajet['nombre_place'] > 1 ? 's' : ''?> disponible<?= $trajet['nombre_place'] > 1 ? 's' : ''?></span>
                    </div>
                    <hr>
                    <?php if (!empty($preferences)): ?>
                        <div class="containerPreferences">
                            <?php foreach ($preferences as $pref): ?>
                                <span><?= htmlspecialchars($pref) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text_div1">Aucune préférence définie.</p>
                    <?php endif; ?>

                    <hr>
                    <div>
                        <?php if (!empty($avis)): ?>
    <?php foreach ($avis as $a): ?>
        <div>
            <span class="text_div1">
                <?= !empty($a['texte']) ? htmlspecialchars($a['texte']) : '' ?>
            </span>
        </div>
    <?php endforeach; ?>
    <a href="<?= BASE_URL ?>/pages/PageAvis.php?user=<?= urlencode($userConducteur['username'] ?? '') ?>" class="avisCovoit">plus d'avis ...</a>
<?php else: ?>
    <p class="text_div1">Aucun avis trouvé.</p>
<?php endif; ?>
                    </div>
                </div>
                <div class="divList">
    <span>Liste des participant(e)s</span>
    <hr>

    <?php if (!empty($participants)): ?>
        <ul>
            <?php foreach ($participants as $p): ?>
                <li class="list"><a href="<?= BASE_URL ?>/actions/Compte.php?id=<?= urlencode($p['id']); ?>" class="lienCovoit">
                    <?= htmlspecialchars($p['username']); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <span>Aucun participant pour ce trajet.</span>
    <?php endif; ?>
</div>

            </div>
            <div class="col2">
                <div class="div3">
                    <div class="containerDiv1Profil">
                        <div class="div1Indiv3">
                            <div class="town">
                                <span><?= extraireVille($trajet['adresse_depart']) ?></span>
                                <span><?= extraireVille($trajet['adresse_arrive']) ?></span>
                            </div>
                            <div class="time">
                                <hr>
                                <span><?= getDuree($trajet['date_depart'], $trajet['heure_depart'], $trajet['date_arrive'], $trajet['heure_arrive']) ?></span>
                                <hr>
                            </div>
                            <div class="departureDestination">
                                <span>Départ : <?= formatTime($trajet['heure_depart']) ?></span>
                                <span>Arrivée : <?= formatTime($trajet['heure_arrive']) ?></span>
                            </div>
                        </div>
                        <div class="containerProfil">
                            <div class="containerImg">
                                <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="ImageProfil2">
                            </div>
                            <div class="containerNote">
                                <span class="note">★ <?= $noteMoyenne !== null ? htmlspecialchars(entierOuDecimal(number_format($noteMoyenne, 2))) : '--' ?>/5</span>
                            </div>
                        </div>
                    </div>
                    <hr class="hrDiv3-1">
                    <div class="departureDestinationDiv3">
                        <div>
                            <span>Adresse de départ : </span>
                            <span><?= $trajet['adresse_depart'] ?></span>
                        </div>
                        <div>
                            <span>Adresse d'arrivée : </span>
                            <span><?= $trajet['adresse_arrive'] ?></span>
                        </div>
                    </div>
                    <hr class="hrDiv3-2">
                    <div class="creditTrajet">
                        <div class="spaceDiv3">
                             <span class="placePop"><?= $trajet['nombre_place'] ?> place<?= $trajet['nombre_place'] > 1 ? 's' : ''?> disponible<?= $trajet['nombre_place'] > 1 ? 's' : ''?></span>
                            <span><?= $trajet['prix']?> crédit<?=$trajet['prix'] > 1 ? 's' : ''?></span>
                        </div>
                       <?php $userId = $_SESSION['user']['id'] ?? null; if ((isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['admin', 'employe'])) || $trajet['statut'] === 'termine' || $trajet['id_utilisateur'] === $userId): ?>
                        <button type="button" style="display: none">Participez</button>
                        <span>trajet indisponible</span>
                        <?php else: ?>
                        <div class="buttonContainer">
                            <button type="button" class="buttonSubmit open-popup">Participez</button>
                        </div>
                        <?php endif; ?>
                <div class="popup" id="popupForm">
                    <div class="popup-content">
                        <h2>Confirmation</h2>
                        <span>
                            Êtes-vous sûr de vouloir participer et payer <?= $prix_total ?> crédit<?= $prix_total > 1 ? 's' : '' ?>
                            pour <?= $places_demandees ?> place<?= $places_demandees > 1 ? 's' : '' ?> ?
                        </span>
                        <div class="spaceButton">
                            <button type="submit" class="buttonOpen">Oui</button>
                            <button type="button" class="buttonCancel" id="cancel-popup">Non</button>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>
        </div>
            <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id']) ?>">
            <input type="hidden" name="nombre_places" value="<?= $places_demandees ?>">
        </form>
    </main>

    <footer> 
        <div>
            <a href="<?= BASE_URL ?>/pages/MentionsLegales.php" class="mentions-legales">mentions légales</a>
            <a href="<?= BASE_URL ?>/pages/Contact.php" class="mentions-legales"> contact </a>
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
   <script src="<?= BASE_URL ?>/assets/javascript/PageCovoiturageIndividuelle.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/Menu.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/Notif.js"></script>
</body>
</html>