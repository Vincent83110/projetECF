<?php 
// Inclusion des fichiers nécessaires pour l'authentification, les fonctions utilitaires, les notifications et la sécurité
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}

include __DIR__ . '/../actions/Notif.php'; 
include __DIR__ . '/../includes/Auth.php'; 
include __DIR__ . '/../includes/Csrf.php';
include __DIR__ . '/../includes/Function.php';          
include __DIR__ . '/../includes/HeaderProtection.php';  // Fonctions utilitaires
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/MentionsLegales.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" 
    rel="stylesheet">
    <link rel="website icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title> Mentions légales </title>
</head>
<body>
    <header>
        <div class="space2">
            <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <a href="<?= BASE_URL ?>/pages/Accueil.php" class="menu-principal">Accueil</a>
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
            <div>
                <div class="nav">
                    <div class="ContainerTitleNavConnect" id="Nav">
                      <span class="titleNav"><img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu"><img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="userNav"></span>
                    </div>
                    <div class="InsideNav" id="InsideNav">
            <!-- Navigation conditionnelle selon le rôle de l'utilisateur -->
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
        <div id="main">
          <button class="openbtn" id="buttonAside"><img src="<?= BASE_URL ?>/assets/images/burger.svg" alt=""></button>
        </div>
        <div class="sidebar" id="mySidebar">
          <!-- Sidebar conditionnelle pour les employés -->
          <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe'): ?>
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#" class="linkNav"><?= htmlspecialchars($employe['prenom']) ?></a>
          <a href="<?= BASE_URL ?>/pages/Accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/Contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= htmlspecialchars($lienCompte) ?>">Compte Pro</a>
          <a href="<?= BASE_URL ?>/actions/Logout.php">Déconnexion</a>
          
        <!-- Sidebar conditionnelle pour les administrateurs -->
        <?php elseif (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
            <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#">Admin</a>
          <a href="<?= BASE_URL ?>/pages/Accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/Contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= htmlspecialchars($lienCompte) ?>">Compte Admin</a>
          <a href="<?= BASE_URL ?>/actions/LogoutAdmin.php">Déconnexion</a>
       
        <!-- Sidebar conditionnelle pour les utilisateurs normaux -->
        <?php else: ?>
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
    <h1 class="h1Margin">Mentions Légales</h1> 
        <article>
            <ol>
                <li class="liMargin"> Éditeur du Site Le site web et l'application "EcoRide" sont édités par la société EcoRide, une startup immatriculée en France sous le numéro de SIRET [à renseigner], dont le siège social est situé à 15 rue de la république .
                Directeur de la publication : Monsieur José dupont, Directeur Technique.
                Contact :
                    <ul>
                        <li>Email : contact@ecoride.fr</li>
                        <li>Téléphone :04.90.98.39.63</li>
                    </ul>
                </li>
                <li class="liMargin">
                Hébergeur Le site web et l'application sont hébergés par [Nom de l'hébergeur], situé à [adresse de l'hébergeur].
                <ul>
                    <li>Téléphone : [à renseigner]</li>
                    <li>Site web : [à renseigner]</li>
                </ul>
                </li>
                <li class="liMargin">
                Objet du Site EcoRide propose une plateforme de covoiturage écologique permettant aux utilisateurs de partager leurs trajets en voiture dans le respect de l'environnement et des économies collaboratives. Le service est exclusivement destiné à la gestion des déplacements en voitures.
                </li>
                <li class="liMargin">
                Propriété intellectuelle Tous les contenus présents sur le site web et l'application EcoRide (textes, images, graphismes, logos, vidéos, éléments logiciels, etc.) sont la propriété exclusive d'EcoRide ou de ses partenaires et sont protégés par le droit d'auteur et/ou les autres lois relatives à la propriété intellectuelle. Toute reproduction, représentation, modification, publication ou adaptation de tout ou partie des éléments du site est interdite sans l'accord écrit préalable d'EcoRide.
                </li>
                <li class="liMargin">
                Données personnelles EcoRide collecte et traite les données personnelles de ses utilisateurs conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés.
                Responsable du traitement : EcoRide.
                Les données collectées sont uniquement utilisées pour les finalités suivantes :
                    <ul>
                        <li>Gestion des trajets et des comptes utilisateurs.</li>
                        <li>Amélioration de la plateforme.</li>
                        <li>Envoi de communications relatives aux services EcoRide.</li>
                    </ul>
                Les utilisateurs disposent d'un droit d'accès, de rectification, d'opposition, de suppression et de portabilité de leurs données. Pour exercer ces droits, veuillez contacter :
                    <ul>
                        <li>Email : dpo@ecoride.fr</li>
                    </ul>
                </li>
                <li class="liMargin">
                Responsabilité EcoRide ne peut être tenu responsable des dommages directs ou indirects pouvant résulter de l'utilisation du site ou de l'application, notamment en cas d'erreur, de problème technique ou de non-disponibilité temporaire des services.
                Les utilisateurs sont responsables des informations qu'ils fournissent et des interactions qu'ils effectuent sur la plateforme. EcoRide ne peut garantir la véracité des informations partagées par les utilisateurs.
                </li>
                <li class="liMargin">
                Modifications des mentions légales EcoRide se réserve le droit de modifier les présentes mentions légales à tout moment. Les modifications entreront en vigueur dès leur publication sur le site.
                </li>
                <li class="liMargin">
                Loi applicable et juridiction compétente Les présentes mentions légales sont régies par la loi française. En cas de litige, les tribunaux compétents seront ceux du ressort du siège social d'EcoRide, sauf disposition contraire imposée par la loi.
                </li>
                <li class="liMargin">
                Contact Pour toute question concernant les mentions légales ou le fonctionnement de la plateforme, veuillez contacter :
                    <ul>
                        <li>Email : contact@ecoride.fr</li>
                        <li>Téléphone : [à renseigner]</li>
                    </ul>
                </li>
            </ol>
        </article>
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
</body>
</html>