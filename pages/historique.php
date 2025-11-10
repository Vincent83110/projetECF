<?php 
// Inclusion des fichiers nécessaires pour l'authentification, les notifications, les fonctions utilitaires, la sécurité et la configuration
require_once __DIR__ . '/../includes/Config.php';
include __DIR__ . '/../includes/Auth.php';   // Gestion de l'authentification utilisateur
include __DIR__ . '/../actions/Notif.php'; 
include __DIR__ . '/../includes/Function.php';          
include __DIR__ . '/../includes/HeaderProtection.php';  // Fonctions utilitaires
include __DIR__ . '/../includes/Csrf.php';
// Récupération des trajets confirmés depuis les cookies
$trajetsConfirmes = [];
if (isset($_COOKIE['trajets_confirmes'])) {
    $trajetsConfirmes = json_decode($_COOKIE['trajets_confirmes'], true);
}

$historiqueTrajets = [];
$userId = $_SESSION['user']['id'] ?? null;

// Récupération des trajets terminés où l'utilisateur est conducteur
if ($userId) {
    $stmt = $pdo->prepare("SELECT * FROM infos_trajet WHERE id_utilisateur = :id AND statut = 'termine' ORDER BY date_depart DESC");
    $stmt->execute([':id' => $userId]);
    $historiqueTrajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ajout du nom du conducteur et sa note à chaque trajet
    foreach ($historiqueTrajets as &$trajet) {
        $stmtUser = $pdo->prepare("SELECT username, note FROM utilisateurs WHERE id = :id");
        $stmtUser->execute([':id' => $trajet['id_utilisateur']]);
        $userInfo = $stmtUser->fetch(PDO::FETCH_ASSOC);
        
        $trajet['conducteur'] = $userInfo['username'] ?? 'Inconnu';
        $trajet['conducteur_note'] = $userInfo['note'] ?? null;
    }
    unset($trajet); // Destruction de la référence
}

$userId = $_SESSION['user']['id'] ?? null;

// Récupération des réservations passées de l'utilisateur (en tant que passager)
$stmt2 = $pdo->prepare("
    SELECT r.*, i.*, u.username AS conducteur_username, 
           u.note AS conducteur_note
    FROM reservation r
    JOIN infos_trajet i ON r.trajet_id = i.id
    JOIN utilisateurs u ON i.id_utilisateur = u.id
    WHERE r.user_id = :id_utilisateur 
    AND r.statut = 'termine'
    ORDER BY r.id DESC
");
$stmt2->execute([':id_utilisateur' => $userId]);
$reservationsPassees = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Nettoyage de la base de données : suppression des trajets terminés en trop (garder seulement les 10 derniers)
$pdo->prepare("
    DELETE FROM infos_trajet 
    WHERE id_utilisateur = :id 
    AND statut = 'termine'
    AND id NOT IN (
        SELECT id FROM (
            SELECT id 
            FROM infos_trajet 
            WHERE id_utilisateur = :id 
            AND statut = 'termine' 
            ORDER BY date_depart DESC 
            LIMIT 10
        ) as t
    )
")->execute([':id' => $userId]);

// Nettoyage des réservations terminées en trop (garder seulement les 10 dernières)
$pdo->prepare("
    DELETE FROM reservation 
    WHERE user_id = :id_utilisateur 
    AND statut = 'termine'
    AND id NOT IN (
        SELECT id FROM (
            SELECT id 
            FROM reservation 
            WHERE user_id = :id_utilisateur 
            AND statut = 'termine' 
            ORDER BY id DESC 
            LIMIT 10
        ) as r
    )
")->execute([':id_utilisateur' => $userId]);

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
    <title> Historique </title>
</head>
<body>
    <header>
        <div class="space">
            <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <a href="<?= BASE_URL ?>/pages/Accueil.php" class="menu-principal" id="menu-principal">Accueil</a>
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
        </div>
        <?php endif; ?>
            </div>
            <div>
                <div class="nav">
                    <div class="ContainerTitleNavConnect" id="Nav">
                      <span class="titleNav"><img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu"><img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="userNav"></span>
                    </div>
                    <div class="InsideNav" id="InsideNav">
            <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] ==='utilisateur'): ?>
    <a href="#" class="linkNav"><?= htmlspecialchars($user['username']) ?></a>
    <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Mon compte</a>
    <a href="<?= BASE_URL ?>/pages/TrajetIndividuel.php" class="linkNav">Mes trajets</a>
    <a href="<?= BASE_URL ?>/pages/Historique.php" class="linkNav">Historique</a>
    <a href="<?= BASE_URL ?>/actions/Logout.php" class="linkNav">Déconnexion</a>
<?php endif; ?>

                </div>
              </div>
            </div>
        </div>     
        <div id="main">
          <button class="openbtn" id="buttonAside"><img src="<?= BASE_URL ?>/assets/images/burger.svg" alt=""></button>
        </div>
        <div class="sidebar" id="mySidebar">
        <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] ==='utilisateur'): ?>
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#"><?= htmlspecialchars($user['username']) ?></a>
          <a href="<?= BASE_URL ?>/pages/Accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/TrajetIndividuel.php">Mes trajets</a>
          <a href="<?= BASE_URL ?>/pages/Historique.php">Historique</a>
          <a href="<?= BASE_URL ?>/pages/Contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= htmlspecialchars($lienCompte) ?>">Mon compte</a>
          <a href="<?= BASE_URL ?>/actions/Logout.php">Déconnexion</a>
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
        <h1>Historique</h1>
    </div>
    <?php if (isset($_SESSION['user']) && $_SESSION['user']['statut'] === 'passager_chauffeur'): ?>
    <div class="buttonChange">
      <button class="button1change">Passager</button>
      <button class="button2change">Chauffeur</button>
    </div>
    <div class="container">
        <div class="col1">
    <span class="textPassager">Passager</span>
    <?php foreach ($reservationsPassees as $res): ?>
        <div class="text-div1">
            <div class="col1div1">
                <div class="inCol1div1">
                    <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="profil" class="imgDiv">
                    <div class="NoteName">
                        <span>★ <?= htmlspecialchars($res['conducteur_note'] === null ? '--' : entierOuDecimal(number_format($res['conducteur_note'], 2))) ?>/5</span>
                        <span class="name"><?= htmlspecialchars($res['conducteur_username']) ?></span>
                    </div>
                </div>
            <div class="containerWait2">
    <?php 
       $statut = strtolower(trim($res['statut'] ?? ''));
?>

<?php if ($statut === 'en_attente'): ?>
    <span class="wait">En attente de réponse ...</span>
<?php elseif ($statut === 'acceptée' || $statut === 'accepte'): ?>
    <span class="wait">Réservation confirmée</span>
<?php elseif ($statut === 'en_cours'): ?>
    <span class="wait">en cours ...</span>
<?php elseif ($statut === 'annulée' || $statut === 'annule'): ?>
    <span class="wait">Réservation annulée</span>
<?php elseif ($statut === 'termine'): ?>
    <span class="wait">Trajet terminé</span>
<?php else: ?>
    <span class="wait">Statut: <?= htmlspecialchars($statut) ?></span>
<?php endif; ?>
    
</div>

            </div>
             <div class="col2div1">
                    <div class="inCol1div2">
                        <div class="DateTrajet">
                            <span><?= htmlspecialchars(formatDate($res['date_depart'])) ?></span>
                            <?= strtolower($res['trajet_ecologique']) === 'oui' ? '<span class="trajet"><img src="'. BASE_URL .'/assets/images/trajet-ecologique.svg" alt="écolo" class="imgEcolo"> Trajet écolo</span>' : '' ?>
                        </div>
                        <div>
                            <a class="linkCovoit" href="<?= BASE_URL ?>/pages/PageCovoiturageIndividuelle.php?id=<?= $res['id'] ?>">Détails...</a>
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
</div>
        <div class="ligne">
        </div>
          <div class="col2">
    <span class="textChauffeur">Chauffeur</span>
    <?php if (!empty($historiqueTrajets)): ?>
        <?php foreach ($historiqueTrajets as $trajet): ?>
            <div class="text-div1">
                <div class="col1div1">
                    <div class="inCol1div1">
                        <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="profil" class="imgDiv">
                        <div class="NoteName">
                        <span>★ <?= htmlspecialchars($trajet['conducteur_note'] === null ? '--' : entierOuDecimal(number_format($trajet['conducteur_note'], 2))) ?>/5</span>
                            <span><?= htmlspecialchars($trajet['conducteur'] ?? 'Inconnu') ?></span>
                        </div>
                    </div>
                      <div class="containerWait2">
    <?php 
       $statut = strtolower(trim($trajet['statut'] ?? ''));
?>

<?php if ($statut === 'en_attente'): ?>
    <span class="wait">En attente de réponse ...</span>
<?php elseif ($statut === 'acceptée' || $statut === 'accepte'): ?>
    <span class="wait">Réservation confirmée</span>
<?php elseif ($statut === 'en_cours'): ?>
    <span class="wait">en cours ...</span>
<?php elseif ($statut === 'annulée' || $statut === 'annule'): ?>
    <span class="wait">Réservation annulée</span>
<?php elseif ($statut === 'termine'): ?>
    <span class="wait">Trajet terminé</span>
<?php else: ?>
    <span class="wait">Statut: <?= htmlspecialchars($statut) ?></span>
<?php endif; ?>
    
</div>
                </div>
                
                 <div class="col2div1">
                    <div class="inCol1div2">
                        <div class="DateTrajet">
                            <span><?= htmlspecialchars(formatDate($trajet['date_depart'])) ?></span>
                            <span><?= strtolower($trajet['trajet_ecologique']) === 'oui' ? '<span class="trajet"><img src="'. BASE_URL .'/assets/images/trajet-ecologique.svg" alt="écolo" class="imgEcolo"> Trajet écolo</span>' : '' ?></span>
                        </div>
                        <div>
                            <a class="linkCovoit" href="<?= BASE_URL ?>/pages/PageCovoiturageIndividuelle.php?id=<?= $trajet['id'] ?>">Détails...</a>
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
        <p>Aucun trajet confirmé récemment.</p>
    <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['user']) && $_SESSION['user']['statut'] === 'passager'): ?>
    <div class="col1">
    <span class="textPassager">Passager</span>
    <?php foreach ($reservationsPassees as $res): ?>
        <div class="text-div1">
            <div class="col1div1">
                <div class="inCol1div1">
                    <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="profil" class="imgDiv">
                    <div class="NoteName">
                        <span>★ <?= htmlspecialchars($res['conducteur_note'] === null ? '--' : entierOuDecimal(number_format($res['conducteur_note'], 2))) ?>/5</span>
                        <span class="name"><?= htmlspecialchars($res['conducteur_username']) ?></span>
                    </div>
                </div>
            <div class="containerWait2">
    <?php 
       $statut = strtolower(trim($res['statut'] ?? ''));
?>

<?php if ($statut === 'en_attente'): ?>
    <span class="wait">En attente de réponse ...</span>
<?php elseif ($statut === 'acceptée' || $statut === 'accepte'): ?>
    <span class="wait">Réservation confirmée</span>
<?php elseif ($statut === 'en_cours'): ?>
    <span class="wait">en cours ...</span>
<?php elseif ($statut === 'annulée' || $statut === 'annule'): ?>
    <span class="wait">Réservation annulée</span>
<?php elseif ($statut === 'termine'): ?>
    <span class="wait">Trajet terminé</span>
<?php else: ?>
    <span class="wait">Statut: <?= htmlspecialchars($statut) ?></span>
<?php endif; ?>
    
</div>

            </div>
             <div class="col2div1">
                    <div class="inCol1div2">
                        <div class="DateTrajet">
                            <span><?= htmlspecialchars(formatDate($res['date_depart'])) ?></span>
                            <?= strtolower($res['trajet_ecologique']) === 'oui' ? '<span class="trajet"><img src="'. BASE_URL .'/assets/images/trajet-ecologique.svg" alt="écolo" class="imgEcolo"> Trajet écolo</span>' : '' ?>
                        </div>
                        <div>
                            <a class="linkCovoit" href="<?= BASE_URL ?>/pages/PageCovoiturageIndividuelle.php?id=<?= $res['id'] ?>">Détails...</a>
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
</div>
    <?php endif; ?>
       <?php if (isset($_SESSION['user']) && $_SESSION['user']['statut'] === 'chauffeur'): ?>
 <div class="col2">
    <span class="textChauffeur">Chauffeur</span>
    <?php if (!empty($historiqueTrajets)): ?>
        <?php foreach ($historiqueTrajets as $trajet): ?>
            <div class="text-div1">
                <div class="col1div1">
                    <div class="inCol1div1">
                        <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="profil" class="imgDiv">
                        <div class="NoteName">
                            <span>★ <?= htmlspecialchars($trajet['conducteur_note'] === null ? '--' : entierOuDecimal(number_format($trajet['conducteur_note'], 2))) ?>/5</span>
                            <span><?= htmlspecialchars($trajet['conducteur'] ?? 'Inconnu') ?></span>
                        </div>
                    </div>
                      <div class="containerWait2">
    <?php 
       $statut = strtolower(trim($trajet['statut'] ?? ''));
?>

<?php if ($statut === 'en_attente'): ?>
    <span class="wait">En attente de réponse ...</span>
<?php elseif ($statut === 'acceptée' || $statut === 'accepte'): ?>
    <span class="wait">Réservation confirmée</span>
<?php elseif ($statut === 'en_cours'): ?>
    <span class="wait">en cours ...</span>
<?php elseif ($statut === 'annulée' || $statut === 'annule'): ?>
    <span class="wait">Réservation annulée</span>
<?php elseif ($statut === 'termine'): ?>
    <span class="wait">Trajet terminé</span>
<?php else: ?>
    <span class="wait">Statut: <?= htmlspecialchars($statut) ?></span>
<?php endif; ?>
    
</div>
                </div>
                
                 <div class="col2div1">
                    <div class="inCol1div2">
                        <div class="DateTrajet">
                            <span><?= htmlspecialchars(formatDate($trajet['date_depart'])) ?></span>
                            <span><?= strtolower($trajet['trajet_ecologique']) === 'oui' ? '<span class="trajet"><img src="'. BASE_URL .'/assets/images/trajet-ecologique.svg" alt="écolo" class="imgEcolo"> Trajet écolo</span>' : '' ?></span>
                        </div>
                        <div>
                            <a class="linkCovoit" href="<?= BASE_URL ?>/pages/PageCovoiturageIndividuelle.php?id=<?= $trajet['id'] ?>">Détails...</a>
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
        <p>Aucun trajet confirmé récemment.</p>
    <?php endif; ?>
    </div>
    <?php endif; ?>

    </div>
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
    <script src="<?= BASE_URL ?>/assets/javascript/Menu.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/Notif.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/Historique.js"></script>
</body>
</html>