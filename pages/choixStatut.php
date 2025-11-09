<?php 
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/auth.php';          // Gestion de l'authentification utilisateur
include __DIR__ . '/../actions/notif.php';         // Système de notifications
include __DIR__ . '/../includes/function.php';      // Fonctions utilitaires
include __DIR__ . '/../includes/headerProtection.php'; // Protection des en-têtes HTTP
include __DIR__ . '/../includes/csrf.php';

// Vérifier que l'utilisateur est connecté et a le bon rôle
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'utilisateur') {
    header("Location: " . BASE_URL . "/actions/connexion.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Choisissez votre statut sur Eco Ride - Chauffeur, Passager ou les deux">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/choixStatut.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" 
    rel="stylesheet">
    <link rel="website icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title>Choix du Statut</title>
</head>
<body>
    <header>
        <div class="space">
            <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <a href="<?= BASE_URL ?>/pages/accueil.php" class="menu-principal" id="menu-principal">Accueil</a>
               <div>
                <div class="notif-container">
                  <div class="bellNotif" id="toggleNotifications" aria-label="Notifications">
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
            </div>
            <div>
                <nav class="nav" aria-label="Navigation utilisateur">
                    <div class="ContainerTitleNavConnect" id="Nav">
                      <span class="titleNav">
                        <img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="Menu déroulant" class="ImgFlecheMenu">
                        <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="Icône de profil" class="user">
                      </span>
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
                </nav>
            </div>
        </div>     
        <div id="main">
          <button class="openbtn" id="buttonAside" aria-label="Ouvrir le menu">
            <img src="<?= BASE_URL ?>/assets/images/burger.svg" alt="Menu">
          </button>
        </div>
        <aside class="sidebar" id="mySidebar" aria-label="Menu latéral">
          <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'utilisateur'): ?>
          <a href="#" class="closebtn" id="closebtn" aria-label="Fermer le menu">×</a>
          <a href="#"><?= htmlspecialchars($user['username']) ?></a>
          <a href="<?= BASE_URL ?>/pages/accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/TrajetIndividuel.php">Mes trajets</a>
          <a href="<?= BASE_URL ?>/pages/historique.php">Historique</a>
          <a href="<?= BASE_URL ?>/pages/contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Légales</a>
          <hr class="color">
          <a href="<?= htmlspecialchars($lienCompte) ?>">Mon compte</a>
          <a href="<?= BASE_URL ?>/actions/logout.php">Déconnexion</a>
          <?php endif; ?>
          <div class="ReseauxRow">
            <a href="https://www.youtube.com/" class="gapReseauxPop" target="_blank" aria-label="YouTube">
              <i class="fab fa-youtube"></i>
            </a>
            <a href="https://www.instagram.com/" class="gapReseauxPop" target="_blank" aria-label="Instagram">
              <i class="fab fa-instagram"></i>
            </a>
            <a href="https://x.com/?lang=fr&mx=2" class="gapReseauxPop" target="_blank" aria-label="X (Twitter)">
              <i class="fa-brands fa-x-twitter"></i>
            </a>
            <a href="https://fr.pinterest.com/" class="gapReseauxPop" target="_blank" aria-label="Pinterest">
              <i class="fab fa-pinterest"></i>
            </a>
          </div>
        </aside>
    </header>
    
    <main id="pop">
        <div class="containers-choices">
        <form action="<?= BASE_URL ?>/actions/statut.php" method="POST">
          <?= csrf_input() ?>
            <button type="submit"  name="statut" value="chauffeur" class="link-choice">
                <div class="container-choice">Souhaitez-vous devenir seulement Chauffeur ? <img src="Images/fleche-choix.svg" alt="" class="fleche"></div>
            </button>
        </form>
        <form action="<?= BASE_URL ?>/actions/statut.php" method="POST">
          <?= csrf_input() ?>
            <button type="submit"  name="statut" value="passager" class="link-choice">
                <div class="container-choice">Souhaitez-vous devenir seulement Passager ? <img src="Images/fleche-choix.svg" alt="" class="fleche"></div>
            </button>
        </form>    
        <form action="<?= BASE_URL ?>/actions/statut.php" method="POST">
          <?= csrf_input() ?>
            <button type="submit"  name="statut" value="passager_chauffeur" class="link-choice">
                <div class="container-choice">Souhaitez-vous devenir Passager-Chauffeur ? <img src="Images/fleche-choix.svg" alt="" class="fleche"></div>
            </button>
        </form>
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
    <script src="<?= BASE_URL ?>/assets/javascript/notif.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/menu.js"></script>
</body>
</html>