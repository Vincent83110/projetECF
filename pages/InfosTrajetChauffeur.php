<?php 
// Inclusion des fichiers nécessaires pour l'authentification, les informations du compte chauffeur, les notifications, les fonctions utilitaires, la sécurité et la configuration
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/auth.php';   // Gestion de l'authentification utilisateur
include __DIR__ . '/../actions/notif.php'; 
include __DIR__ . '/../actions/infosCompteChauffeur.php'; 
include __DIR__ . '/../includes/function.php';          
include __DIR__ . '/../includes/headerProtection.php';  // Fonctions utilitaires
include __DIR__ . '/../includes/csrf.php';

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Initialisation des crédits de l'utilisateur s'ils ne sont pas définis
    if (!isset($_SESSION['credits'])) {
    $_SESSION['credits'] = 20; // 20 crédits de départ
}
    $userId = $_SESSION['user']['id'] ?? null;

    // Récupération des véhicules et préférences de l'utilisateur connecté
    if ($userId) {
        // Récupération des véhicules
        $stmt = $pdo->prepare("SELECT * FROM vehicules WHERE user_id = :user_id AND deleted = FALSE");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupération des préférences
        $stmt = $pdo->prepare("SELECT * FROM preferences WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    }

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
    exit;
}

?>


<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/InfosTrajetChauffeur.css?v=<?= time() ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" 
  rel="stylesheet">
  <link rel="website icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title> Infos Trajet Chauffeur </title>
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
                      <span class="titleNav"><img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu"><img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="user"></span>
                    </div>
                    <div class="InsideNav" id="InsideNav">
             <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] ==='utilisateur'): ?>
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
          <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] ==='utilisateur'): ?>
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#" class="linkNav"><?= htmlspecialchars($user['username']) ?></a>
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
    <div class="divTitle">
        <span class="textTitle">Informations prochain(s) trajet(s)</span>
    </div>

    <div class="back">
        <form id="infos-chauffeur" method="post" action="<?= BASE_URL ?>/actions/ajoutTrajet.php">
            <?= csrf_input() ?>
            <div class="mainPrincipal">
                <div class="form-container">
                  <label class="form-label" for="">Adresse départ</label>
                  <input type="search" name="trajets[0][adresse_depart]" class="form-input adresse-depart" autocomplete="off" placeholder="Ex: 10 rue de la paix" required data-valid="false"/>
                <div class="suggestions suggestions-depart"></div>
            </div>

            <div class="form-container">
              <label class="form-label" for="">Adresse arrivée</label>
              <input type="search" name="trajets[0][adresse_arrive]" class="form-input adresse-arrivee" autocomplete="off" placeholder="Ex: 10 rue de la république" required data-valid="false"/>
              <div class="suggestions suggestions-arrivee"></div>
            </div>


                <div class="containerDivlabel3">
                    <div class="divLablel3-1">
                        <label for="label3-1" class="textLabel">Heure de départ</label>
                        <input type="time" id="label3-1" name="trajets[0][heure_depart]" class="label3-1" required>
                    </div>
                    <div class="divLablel3-2">
                        <label for="label3-2" class="textLabel">Heure d'arrivée</label>
                        <input type="time" id="label3-2" name="trajets[0][heure_arrive]" class="label3-2" required>
                    </div>
                </div>

                <div class="containerDivlabel4">
                    <div class="divLablel4-1">
                        <label for="label4-1" class="textLabel">Date de départ</label>
                        <input type="date" id="label4-1" name="trajets[0][date_depart]" class="label4-1" required>
                    </div>
                    <div class="divLablel4-2">
                        <label for="label4-2" class="textLabel">Date d'arrivée</label>
                        <input type="date" id="label4-2" name="trajets[0][date_arrive]" class="label4-2" required>
                    </div>
                </div>
              <div class="containerDivlabel5">
                <div class="divLablel5">
                    <label for="label5" class="textLabel">Prix</label>
                    <input type="number" id="label5" name="trajets[0][prix]" class="label5" required min="0" placeholder="10">
                    <span class="credit">Crédits</span>
                </div>

                <div class="divLabel6">
                    <label for="label6" class="textLabel">Nombre de places </label>
                    <input type="number" id="label6" name="trajets[0][nombre_place]" class="label6" min="1" max="8" placeholder="5">
                </div>
              </div>
                <div class="containerDivlabel6">
                    <div class="divLablel7-1">
                        <label for="label7-1" class="textLabel">Véhicule</label>
<select name="trajets[0][id_vehicule]" id="label7-1" class="Cars" required>
    <option value="">-- Choisir un véhicule --</option>
    <?php if (!empty($vehicules)): ?>
        <?php foreach ($vehicules as $vehicule): ?>
            <option value="<?= htmlspecialchars($vehicule['id']) ?>">
                <?= htmlspecialchars($vehicule['marque']) ?> <?= htmlspecialchars($vehicule['modele']) ?>
            </option>
        <?php endforeach; ?>
    <?php else: ?>
        <option value="">Aucun véhicule disponible</option>
    <?php endif; ?>
</select>
                  </div>
                </div>
                
                <div class="popup" id="popupForm">
                    <div class="popup-content">
                        <h2>Confirmation</h2>
                        <p> Êtes-vous sûr de vouloir soumettre ces informations et payer 
                        <span id="credit-count">2</span> crédits ?
                        </p>
                        <div class="spaceButton">
                          <button type="button" id="confirmSubmit" class="buttonOpen">Oui</button>
                          <button type="button" class="buttonCancel" id="cancel-popup">Non</button>
                        </div>
                    </div>
                </div>
    </div>
  <div class="mainPrincipal2" id="trajets-container"></div>
<div class="containerDiv3">
    <div class="div3">
        <label class="textLabel">Préférences (appliquées à tous les trajets)</label>
        <div class="container-align2">
            <div>
                <input type="checkbox" id="pref_non_fumeurs" name="preferences[]" value="non fumeurs">
                <label for="pref_non_fumeurs" class="textLabel">Non-fumeurs</label>
            </div>
            <div>
                <input type="checkbox" id="pref_pas_animaux" name="preferences[]" value="pas d'animaux">
                <label for="pref_pas_animaux" class="textLabel">Pas d'animaux</label>
            </div>
            <!-- Préférences personnalisées existantes -->
            <?php if (!empty($preferences)): ?>
    <?php foreach ($preferences as $pref): ?>
        <?php if (isset($pref['preference'])): ?>
            <div class="preference-item">
                <input type="checkbox" id="pref_<?= $pref['id'] ?>" name="preferences[]" value="<?= htmlspecialchars($pref['preference']) ?>">
                <label for="pref_<?= $pref['id'] ?>" class="textLabel"><?= htmlspecialchars($pref['preference']) ?></label>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
            <!-- Nouvelle préférence personnalisée -->
            <div class="new-preference-form">
                <input type="text" id="new-preference-input" name="preferences[]" placeholder="Nouvelle préférence">
                <button type="button" class="ajout-preference-perso">+</button>
            </div>
        </div>
    </div>

    <!-- Boutons d'action -->
    <div class="spaceButton">
        <button href="#" id="add-trajet-btn" class="linkAjout">
            <img src="<?= BASE_URL ?>/assets/images/plus2.svg" alt="pas d'image"> Ajouter un trajet
        </button>
        <div>
            <button type="button" class="buttonSubmit open-popup">Confirmer</button>
        </div>
    </div>
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
        const vehiculesDisponibles = <?= json_encode($vehicules) ?>;
    </script>
    <script>
        const BASE_URL = "<?= BASE_URL ?>";
    </script>
    <script src="<?= BASE_URL ?>/assets/javascript/menu.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/notif.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/infosTrajetChauffeur.js"></script>
</body>
</html>