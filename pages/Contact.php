<?php 
// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../includes/Config.php';
include __DIR__ . '/../includes/Auth.php';          // Gestion de l'authentification utilisateur
include __DIR__ . '/../actions/Notif.php';         // Système de notifications
include __DIR__ . '/../includes/Function.php';      // Fonctions utilitaires
include __DIR__ . '/../includes/HeaderProtection.php'; // Protection des en-têtes HTTP
include __DIR__ . '/../includes/Csrf.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/Contact.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="website icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title>Contact</title>
</head>
<body>
    <header>
        <div class="space2">
            <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <a href="<?= BASE_URL ?>/pages/Accueil.php" class="menu-principal" id="menu-principal">Accueil</a>
              
              <!-- Section notifications pour utilisateurs -->
                <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'utilisateur' && ($_SESSION['user']['statut'] === 'passager_chauffeur' || $_SESSION['user']['statut'] === 'chauffeur')): ?>
              <div>
                <div class="notif-container">
                    <!-- Icône de notification avec badge -->
                    <div class="bellNotif" id="toggleNotifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($notificationCount > 0): ?>
                            <span class="notif-badge"><?= $notificationCount ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Affichage des notifications -->
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
                                    <!-- Formulaire pour traiter les demandes -->
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
            
            <!-- Navigation utilisateur -->
            <div>
                <div class="nav">
                    <div class="ContainerTitleNavConnect" id="Nav">
                      <span class="titleNav">
                          <img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu">
                          <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="userNav">
                      </span>
                    </div>
                    <div class="InsideNav" id="InsideNav">
                        <!-- Menu selon le type d'utilisateur -->
                        <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe'): ?>
                            <a href="#" class="linkNav"><?= htmlspecialchars($employe['prenom']) ?></a>
                            <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Mon compte</a>
                            <a href="<?= BASE_URL ?>/actions/Logout.php" class="linkNav">Déconnexion</a>
                        <?php elseif (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                            <a href="#" class="linkNav">Admin</a>
                            <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Compte Admin</a>
                            <a href="<?= BASE_URL ?>/actions/LogoutAdmin.php" class="linkNav">Déconnexion</a>
                        <?php else: ?>
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
        
        <!-- Menu burger et sidebar (structure standard) -->
        <div id="main">
          <button class="openbtn" id="buttonAside"><img src="<?= BASE_URL ?>/assets/images/burger.svg" alt=""></button>
        </div>
        <div class="sidebar" id="mySidebar">
            <!-- Sidebar selon le type d'utilisateur -->
            <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe'): ?>
          <!-- Menu pour employés -->
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#" class="linkNav"><?= htmlspecialchars($employe['prenom']) ?></a>
          <a href="<?= BASE_URL ?>/pages/Accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/Contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= htmlspecialchars($lienCompte) ?>">Compte Pro</a>
          <a href="<?= BASE_URL ?>/actions/Logout.php">Déconnexion</a>
          
        <?php elseif (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
            <!-- Menu pour administrateurs -->
            <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#">Admin</a>
          <a href="<?= BASE_URL ?>/pages/Accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/Contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= htmlspecialchars($lienCompte) ?>">Compte Admin</a>
          <a href="<?= BASE_URL ?>/actions/LogoutAdmin.php">Déconnexion</a>
            <?php else: ?>
                <!-- Sidebar utilisateur standard -->
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
            
            <!-- Liens réseaux sociaux -->
            <div class="ReseauxRow">
                <a href="https://www.youtube.com/" class="gapReseauxPop" target="_blank"><i class="fab fa-youtube"></i></a>
                <a href="https://www.instagram.com/" class="gapReseauxPop" target="_blank"><i class="fab fa-instagram"></i></a>
                <a href="https://x.com/?lang=fr&mx=2" class="gapReseauxPop" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>
                <a href="https://fr.pinterest.com/" class="gapReseauxPop" target="_blank"><i class="fab fa-pinterest"></i></a>
            </div>
        </div>
    </header>

    <main id="pop">
        <!-- Contenu principal de la page contact -->
        <h1 class="titleMargin">CONTACT</h1> 
        <article>
            <h2 class="titleMargin">Contactez-nous</h2>
            <p class="textMargin">
                Chez EcoRide, nous sommes toujours heureux d'entendre vos idées, questions et suggestions. 
                Nous croyons que chaque contribution compte pour rendre le covoiturage plus écologique et accessible à tous. 
                N'hésitez pas à nous contacter via les moyens suivants :
            </p>
            
            <!-- Liste des moyens de contact -->
            <ol>
                <li class="textMargin">
                    <h4>Par email</h4>
                    Pour toute demande générale ou question relative à notre plateforme, vous pouvez nous envoyer un email à l'adresse suivante : contact@ecoride.com
                </li>
                <li class="textMargin">
                    <h4>Par téléphone</h4>
                    Si vous préférez un contact plus direct, appelez-nous au : +33 1 23 45 67 89<br>
                    Du lundi au vendredi, de 9h à 18h.
                </li>
                <li class="textMargin">
                    <h4>Sur les réseaux sociaux</h4>
                    Suivez-nous et contactez-nous via nos pages sociales pour des mises à jour, des nouvelles et des réponses rapides :
                    <ul>
                        <li>Facebook : @EcoRide</li>
                        <li>Twitter : @EcoRide_Fr</li>
                        <li>Instagram : @EcoRide</li>
                    </ul>
                </li>
                <li class="textMargin">
                    <h4>Adresse physique</h4>
                    Vous pouvez aussi nous rendre visite à notre siège situé à :<br>
                    EcoRide - 123 Rue de l'Innovation, 13001 Marseille, France
                </li>
            </ol>
        </article>
    </main>

    <footer> 
        <!-- Pied de page standard -->
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

    <!-- Scripts JavaScript -->
         <script>
        const BASE_URL = "<?= BASE_URL ?>";
    </script>
    <script src="<?= BASE_URL ?>/assets/javascript/Menu.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/Notif.js"></script>
</body>
</html>