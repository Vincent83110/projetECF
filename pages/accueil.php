<?php 
// Inclusions des fichiers nécessaires pour l'authentification, notifications, fonctions et sécurité
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/auth.php';          // Gestion de l'authentification utilisateur
include __DIR__ . '/../actions/notif.php';         // Système de notifications
include __DIR__ . '/../includes/function.php';      // Fonctions utilitaires
include __DIR__ . '/../includes/headerProtection.php'; // Protection des en-têtes HTTP

// Vérification si un utilisateur est connecté pour récupérer sa note
if (isset($_SESSION['user']['id'])) {
    $userId = $_SESSION['user']['id'];
    
    // Requête pour récupérer la note moyenne de l'utilisateur connecté
    $sql = "SELECT note FROM utilisateurs WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $userId]); // Exécution avec le paramètre ID utilisateur
    $noteUtilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Stockage de la note dans une variable accessible
    $noteMoyenne = $noteUtilisateur['note'] ?? null; // Utilisation de l'opérateur null coalescent
} else {
    $noteMoyenne = null; // Pas de note si utilisateur non connecté
}

// Requête pour récupérer les derniers trajets avec les notes des conducteurs
$sqlTrajets = "SELECT t.*, u.note AS note_conducteur
               FROM infos_trajet t
               LEFT JOIN utilisateurs u ON t.id_utilisateur = u.id
               ORDER BY t.date_depart DESC
               LIMIT 5
              ";

$stmtTrajets = $pdo->query($sqlTrajets);
$trajets = $stmtTrajets->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/accueil.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" 
    rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title> Accueil </title>
</head>
<body>
    <header>
        <div class="space2">
            <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <!-- Lien vers la page d'accueil -->
              <a href="<?= BASE_URL ?>/pages/accueil.php" class="navigation" id="menu-principal">Accueil</a>
              <!-- Affichage conditionnel des notifications pour les utilisateurs normaux -->
               <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'utilisateur' && ($_SESSION['user']['statut'] === 'passager_chauffeur' || $_SESSION['user']['statut'] === 'chauffeur')): ?>
               <div>
                <!-- Conteneur des notifications -->
                <div class="notif-container">
  <!-- Icône de cloche pour afficher/masquer les notifications -->
  <div class="bellNotif" id="toggleNotifications">
    <i class="fas fa-bell"></i>
    <!-- Badge indiquant le nombre de notifications non lues -->
    <?php if ($notificationCount > 0): ?>
    <span class="notif-badge"><?= $notificationCount ?></span>
  <?php endif; ?>
  </div>

  <!-- Liste des notifications si elles existent -->
  <?php if (!empty($notifications)): ?>
  <div class="notification" id="notificationMenu">
    <!-- Boucle sur chaque notification -->
    <?php foreach ($notifications as $notif): ?>
      <div class="notif-item">
       <div class="notif-header">
        <div>
            <!-- Numéro du trajet concerné -->
            <span><strong>Trajet n° <?= htmlspecialchars($notif['numero_trajet']) ?></strong></span>
        </div>
        <div>
            <!-- Lien vers les détails du covoiturage -->
            <a class="linkCovoit" href="<?= BASE_URL ?>/pages/pagecovoituragesIndividuel.php?id=<?= $notif['trajet_id'] ?>">Détails...</a>
        </div>
       </div>  
        <!-- Détails de la réservation -->
        <span><?= htmlspecialchars($notif['username']) ?> à réservé votre trajet <?=  extraireVille(htmlspecialchars($notif['adresse_depart'])) ?> -> 
        <?= extraireVille(htmlspecialchars($notif['adresse_arrive'])) ?> du <?= formatDate(htmlspecialchars($notif['date_depart'])) ?></span>
        <!-- Formulaire pour accepter/refuser la demande -->
        <form method="POST" action="<?= BASE_URL ?>/actions/traiter_demande.php">
          <?= csrf_input() ?>
          <!-- Champs cachés pour identifier la réservation et notification -->
          <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($notif['reservation_id']) ?>">
          <input type="hidden" name="notification_id" value="<?= htmlspecialchars($notif['id']) ?>">
          <div class="button-container">
            <!-- Boutons d'action -->
            <button type="submit" name="action" value="accepter" class="buttonNotif">Accepter</button>
            <button type="submit" name="action" value="refuser" class="buttonNotif">Refuser</button>
          </div>
        </form>
        <div class="notif-container">
            <!-- Séparateur entre les notifications -->
            <hr class="notif-separator">
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <!-- Message si aucune notification -->
  <div class="notification" id="notificationMenu">
    <p>Aucune nouvelle demande</p>
  </div>
<?php endif; ?>

</div>      
        </div>
        <?php endif; ?>
            </div>
            <div>
                <!-- Navigation utilisateur -->
                <div class="nav">
                    <div class="ContainerTitleNavConnect" id="Nav">
                      <!-- Icône du menu utilisateur avec flèche et avatar -->
                      <span class="titleNav"><img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu"><img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="userNav"></span>
                    </div>
                    <!-- Sous-menu de navigation -->
                    <div class="InsideNav" id="InsideNav">
            <!-- Affichage conditionnel selon le rôle de l'utilisateur -->
            <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe'): ?>
    <!-- Menu pour les employés -->
    <a href="#" class="linkNav"><?= htmlspecialchars($employe['prenom']) ?></a>
    <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Mon compte</a>
    <a href="<?= BASE_URL ?>/actions/logout.php" class="linkNav">Déconnexion</a>

<?php elseif (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
    <!-- Menu pour les administrateurs -->
    <a href="#" class="linkNav">Admin</a>
    <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Compte Admin</a>
    <a href="<?= BASE_URL ?>/actions/logoutAdmin.php" class="linkNav">Déconnexion</a>

<?php else: ?>
    <!-- Menu pour les utilisateurs normaux -->
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
        <!-- Bouton pour ouvrir le menu latéral (mobile) -->
        <div id="main">
          <button class="openbtn" id="buttonAside"><img src="<?= BASE_URL ?>/assets/images/burger.svg" alt=""></button>
        </div>
        <!-- Menu latéral responsive -->
        <div class="sidebar" id="mySidebar">
          <!-- Affichage conditionnel selon le rôle -->
          <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe'): ?>
          <!-- Menu pour employés -->
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#" class="linkNav"><?= htmlspecialchars($employe['prenom']) ?></a>
          <a href="<?= BASE_URL ?>/pages/accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= htmlspecialchars($lienCompte) ?>">Compte Pro</a>
          <a href="<?= BASE_URL ?>/actions/logout.php">Déconnexion</a>
          
        <?php elseif (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
            <!-- Menu pour administrateurs -->
            <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#">Admin</a>
          <a href="<?= BASE_URL ?>/pages/accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= htmlspecialchars($lienCompte) ?>">Compte Admin</a>
          <a href="<?= BASE_URL ?>/actions/logoutAdmin.php">Déconnexion</a>
       
        <?php else: ?>
          <!-- Menu pour utilisateurs normaux -->
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

          <!-- Liens vers les réseaux sociaux -->
          <div class="ReseauxRow">
            <a href="https://www.youtube.com/" class="gapReseauxPop" target="_blank"><i class="fab fa-youtube"></i></a>
            <a href="https://www.instagram.com/" class="gapReseauxPop" target="_blank"><i class="fab fa-instagram"></i></a>
            <a href="https://x.com/?lang=fr&mx=2" class="gapReseauxPop" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>
            <a href="https://fr.pinterest.com/" class="gapReseauxPop" target="_blank"><i class="fab fa-pinterest"></i></a>
          </div>
        </div>
    </header>
    <!-- Image d'en-tête pleine largeur -->
    <div class="full-width-image">
        <img src="<?= BASE_URL ?>/assets/images/VoitureElectrique.jpg" alt="pas d'image" class="imgAccueil">
    </div>
    <!-- Contenu principal -->
    <main id="pop">
   <!-- Formulaire de recherche de covoiturage -->
   <form method="get" action="<?= BASE_URL ?>/pages/Covoiturages.php">
    <div class="search-bar">
        <!-- Champ de départ avec autocomplétion -->
        <input class="departure" id="departure" type="text" name="departure" placeholder="Départ" autocomplete="off" required>
        <!-- Champ d'arrivée avec autocomplétion -->
        <input class="arrive" id="destination" type="text" name="destination" placeholder="Arrivée" autocomplete="off" required>
        <!-- Sélecteur de date -->
        <input class="date" type="date" name="date" required>
        <!-- Nombre de passagers avec limites -->
        <input class="number" type="number" name="passengers" placeholder="Nombre de passagers" min="1" max="8" step="1" required>
        <!-- Bouton de recherche -->
        <button type="submit" aria-label="Rechercher" class="buttonSearch">
            <i class="fas fa-search"></i>
        </button>
    </div>
</form>

<!-- Zone de suggestions pour l'autocomplétion -->
<div class="suggestions-sur-container">
    <div class="suggestions-container">
        <!-- Liste des suggestions pour le départ -->
        <ul id="suggestions-departure" class="autocomplete-suggestions1"></ul>
        <!-- Liste des suggestions pour la destination -->
        <ul id="suggestions-destination" class="autocomplete-suggestions2"></ul>
    </div>
</div>

    <!-- Section des cadres d'information sur l'entreprise -->
    <div class="frames">
        <!-- Premier cadre -->
        <div class="div-frame1 divs-framess">
        <!-- Bouton de navigation précédent -->
        <button class="arrow-left1" aria-label="Precedent"><img src="<?= BASE_URL ?>/assets/images/fleche-gauche.svg" alt="" class="fleche-gauche"></button>
            <div class="FrameRow">
                <!-- Image du cadre -->
                <img src="<?= BASE_URL ?>/assets/images/Frame1.jpeg" alt="VoitureElectriqueSurFeuille" id="Frame1" class="Frame1">
                <div class="text-block">
                    <!-- Texte descriptif -->
                    <p>Tout commence lors d'un embouteillage interminable sur le périphérique parisien. José, ingénieur passionné d'écologie, observe les innombrables voitures autour de lui, la plupart transportant une seule personne. Il se demande : "Pourquoi ne pas optimiser ces trajets en réunissant des personnes qui vont dans la même direction ?". Ce moment d'introspection devient le point de départ d'EcoRide, une idée ambitieuse pour réduire l'impact environnemental des déplacements tout en favorisant des économies pour tous. </p>
                </div>
                <!-- Bouton "Voir plus" pour mobile -->
                <button class="seeMore">Voir plus</button>
            </div>
        <!-- Bouton de navigation suivant -->
        <button class="arrow-right1" aria-label="Suivant"><img src="<?= BASE_URL ?>/assets/images/fleche-droite.svg" alt="" class="fleche-droite"></button>
        </div>

        <!-- Deuxième cadre (structure similaire) -->
        <div class="div-frame2 divs-framess">
        <button class="arrow-left2" aria-label="Precedent"><img src="<?= BASE_URL ?>/assets/images/fleche-gauche.svg" alt="" class="fleche-gauche"></button>
            <div class="FrameRow">
                <img src="<?= BASE_URL ?>/assets/images/Frame2.jpeg" alt="HommeDebout" id="Frame2" class="Frame2">
                <div class="text-block">
                    <p>José décide de transformer cette vision en réalité. Avec l'aide de quelques amis partageant les mêmes valeurs écologiques, il crée EcoRide, une startup dédiée au covoiturage. L'objectif est clair : développer une plateforme simple et efficace permettant de connecter les conducteurs et les passagers pour des trajets en voiture. Mais pas question de rester une simple alternative : EcoRide ambitionne de devenir la référence pour les voyageurs responsables. </p>
                </div>
                <button class="seeMore">Voir plus</button>
            </div>
        <button class="arrow-right2" aria-label="Suivant"><img src="<?= BASE_URL ?>/assets/images/fleche-droite.svg" alt="" class="fleche-droite"></button>
        </div>

        <!-- Troisième cadre -->
        <div class="div-frame3 divs-framess">
        <button class="arrow-left3" aria-label="Precedent"><img src="<?= BASE_URL ?>/assets/images/fleche-gauche.svg" alt="" class="fleche-gauche"></button>
            <div class="FrameRow">
                <img src="<?= BASE_URL ?>/assets/images/Frame3.jpeg" alt="Voyageurs" id="Frame3" class="Frame3">
                <div class="text-block">
                    <p>Comme toute aventure entrepreneuriale, les débuts d'EcoRide furent jalonnés d'obstacles. Trouver des financements, convaincre les premiers utilisateurs, et développer une application web performante furent autant de défis à relever. Mais l'équipe ne se laissa pas décourager. Grâce à leur persévérance et à leur passion pour l'écologie, ils surmontèrent chaque difficulté, améliorant sans cesse leur concept et leur plateforme. </p>
                </div>
                <button class="seeMore">Voir plus</button>
            </div>
        <button class="arrow-right3" aria-label="Suivant"><img src="<?= BASE_URL ?>/assets/images/fleche-droite.svg" alt="" class="fleche-droite"></button>
        </div>

        <!-- Quatrième cadre -->
        <div class="div-frame4 divs-framess">
        <button class="arrow-left4" aria-label="Precedent"><img src="<?= BASE_URL ?>/assets/images/fleche-gauche.svg" alt="" class="fleche-gauche"></button>
            <div class="FrameRow">
                <img src="<?= BASE_URL ?>/assets/images/Frame4.jpeg" alt="VoitureElectriqueForet" id="Frame4" class="Frame4">
                <div class="text-block">
                    <p>EcoRide a rapidement attiré l'attention, non seulement des particuliers, mais aussi des entreprises cherchant des solutions de mobilité durable pour leurs employés. Aujourd'hui, la startup est en passe de devenir la plateforme incontournable pour les déplacements responsables en France. José et son équipe rêvent d'un avenir où chaque trajet en voiture sera partagé, où les routes seront plus fluides, et où l'impact environnemental de la mobilité sera significativement réduit. </p>
                </div>
                <button class="seeMore">Voir plus</button>
            </div>
        <button class="arrow-right4" aria-label="Suivant"><img src="<?= BASE_URL ?>/assets/images/fleche-droite.svg" alt="" class="fleche-droite"></button>
        </div>
    </div>
</main>
<!-- Pied de page -->
<footer> 
    <div>
        <!-- Liens vers les pages légales -->
        <a href="<?= BASE_URL ?>/pages/MentionsLegales.php" class="mentions-legales">mentions légales</a>
        <a href="<?= BASE_URL ?>/pages/contact.php" class="mentions-legales"> contact </a>
    </div>
    <div>
        <!-- Liens vers les réseaux sociaux -->
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
<script src="<?= BASE_URL ?>/assets/javascript/menu.js"></script>
<script src="<?= BASE_URL ?>/assets/javascript/accueil.js"></script>
<script src="<?= BASE_URL ?>/assets/javascript/notif.js"></script>
<script src="<?= BASE_URL ?>/assets/javascript/autocomplete.js"></script>
</body>
</html> 