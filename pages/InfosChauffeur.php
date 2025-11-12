<?php 
// Inclusion des fichiers nécessaires pour l'authentification, les notifications, les fonctions utilitaires, la sécurité et la protection CSRF
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}

include __DIR__ . '/../includes/Auth.php';   // Gestion de l'authentification utilisateur
include __DIR__ . '/../actions/Notif.php'; 
include __DIR__ . '/../includes/Function.php';          
include __DIR__ . '/../includes/HeaderProtection.php';  // Fonctions utilitaires
include __DIR__ . '/../includes/Csrf.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/InfosChauffeur.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" 
    rel="stylesheet">
    <link rel="icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title> Infos chauffeur </title>
</head>
<body>
    <header>
        <div class="space">
            <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <a href="<?= BASE_URL ?>/pages/Accueil.php" class="menu-principal" id="menu-principal">Accueil</a>
               <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'utilisateur' && ($_SESSION['user']['statut'] === 'passager_chauffeur' || $_SESSION['user']['statut'] === 'chauffeur')): ?>
               <div>
                <!-- Container des notifications -->
                <div class="notif-container">
  <div class="bellNotif" id="toggleNotifications">
    <i class="fas fa-bell"></i>
    <!-- Affichage du badge de notification si il y a des notifications -->
    <?php if ($notificationCount > 0): ?>
    <span class="notif-badge"><?= $notificationCount ?></span>
  <?php endif; ?>
  </div>

  <!-- Menu déroulant des notifications -->
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
        <!-- Formulaire pour traiter les demandes de réservation -->
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
                <div class="nav">
                    <div class="ContainerTitleNavConnect" id="Nav">
                      <span class="titleNav"><img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu"><img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="user"></span>
                    </div>
                    <div class="InsideNav" id="InsideNav">
            <!-- Navigation conditionnelle selon le rôle de l'utilisateur -->
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
          <!-- Sidebar conditionnelle pour les utilisateurs normaux -->
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
  <div class="containerForm">
    <div class="container-title">
      <h1 class="title">Informations Chauffeur</h1>
    </div>

    <!-- Formulaire pour ajouter des véhicules -->
    <form method="post" action="<?= BASE_URL ?>/actions/AjoutVehicule.php">
      <?= csrf_input() ?>
      <div class="vehicules-container">
        <!-- Premier véhicule (initial) -->
        <div class="bloc-vehicule">
          <div class="container1">
            <div class="text-container">
              <div class="div1">
                <label class="text-label">Plaque d'immatriculation</label>
                <input type="text" class="input1" name="plaque_immatriculation[]" placeholder="GR-156-RF" required />
              </div>
              <div class="div1">
                <label class="text-label">Date première immatriculation</label>
                <input type="date" class="input1" name="date_immatriculation[]" required />
              </div>
              <div class="container-align">
                <div class="div2">
                  <label class="text-label2">Marque</label>
                  <input type="text" class="input2" name="marque[]" placeholder="Tesla" required />
                </div>
                <div class="div2">
                  <label class="text-label2">Modèle</label>
                  <input type="text" class="input2" name="modele[]" placeholder="Model Y" required />
                </div>
                <div class="div2">
                  <label class="text-label2">Couleur</label>
                  <input type="text" class="input2" name="couleur[]" placeholder="Bleue" required />
                </div>
              </div>
            </div>
          </div>
          <div class="container2">
            <div class="text-container2">
              <div class="number">
                <label class="text-label2">Nombre de places</label>
                <input type="number" class="input3" name="capacite[]" placeholder="5" min="2" max="8"  placeholder="5" required />
              </div>
              <div>
                <select name="energie[]" id="energie" class="input4" required>
                  <option value="" disabled selected>Type d'énergie</option>
                  <option value="essence">Essence</option>
                  <option value="diesel">Diesel</option>
                  <option value="electrique">Électrique</option>
                  <option value="hybride">Hybride</option>
                  <option value="autre">Autre</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>
        <div class="containerAjoutAlign">
            <div class="containerInsideAjout">
                <div class="containerAjout">
                    <!-- Bouton pour ajouter un véhicule supplémentaire -->
                    <a href="#" class="ajout-vehicule">
                        <img src="<?= BASE_URL ?>/assets/images/plus2.svg" alt="ajouter" class="widthPlus2" />
                        <span>Ajouter un véhicule</span>
                    </a>
                </div>
                <div class="containerSubmit">
                    <button type="submit" class="buttonSubmit">Confirmer</button>
                </div>
            </div>
        </div>
    </div>
</form>
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
    <script src="<?= BASE_URL ?>/assets/javascript/InfosChauffeur.js"></script>

</body>
</html>