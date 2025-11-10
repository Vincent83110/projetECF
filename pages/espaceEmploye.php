<?php
// Inclusion des fichiers nécessaires pour l'authentification, la sécurité et la configuration
require_once __DIR__ . '/../includes/Config.php';
include __DIR__ . '/../includes/Auth.php';          // Gestion de l'authentification utilisateur
include __DIR__ . '/../includes/HeaderProtection.php';      // Fonctions utilitaires
include __DIR__ . '/../includes/Csrf.php';

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérification si l'utilisateur est connecté et a le rôle 'employe'
    if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe') {
        $id = $_SESSION['user']['id'];
        // Préparation et exécution de la requête pour récupérer les informations de l'employé
        $stmt = $pdo->prepare("SELECT * FROM employe WHERE id = ?");
        $stmt->execute([$id]);
        $employe = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupération des 3 derniers avis invalides avec commentaires non vides
    $stmt = $pdo->query("
        SELECT a.id, a.commentaire, a.note, u.username
        FROM avis a
        JOIN utilisateurs u ON a.id_utilisateur = u.id
        WHERE a.commentaire IS NOT NULL 
        AND a.commentaire <> '' 
        AND a.statut = 'invalide'
        ORDER BY a.id DESC
        LIMIT 3
    ");
    $avisList = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Gestion des erreurs de connexion à la base de données
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Liens CSS et polices -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/CompteEmploye.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" 
    rel="stylesheet">
    <link rel="website icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title> espace employé(e) </title>
</head>
<body>
    <header>
        <div class="space">
            <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <a href="<?= BASE_URL ?>/pages/Accueil.php" class="menu-principal">Accueil</a>
            </div>
            <div>
                <div class="nav">
                    <div class="ContainerTitleNavConnect" id="Nav">
                      <!-- Icônes du menu de navigation -->
                      <span class="titleNav"><img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu"><img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="userNav"></span>
                    </div>
                    <div class="InsideNav" id="InsideNav">
            <!-- Affichage conditionnel des liens selon le rôle de l'utilisateur -->
            <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe'): ?>
    <a href="#" class="linkNav"><?= htmlspecialchars($employe['prenom']) ?></a>
    <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Mon compte</a>
    <a href="<?= BASE_URL ?>/actions/Logout.php" class="linkNav">Déconnexion</a>

<?php elseif (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
    <a href="#" class="linkNav">Admin</a>
    <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Compte Admin</a>
    <a href="<?= BASE_URL ?>/actions/LogoutAdmin.php" class="linkNav">Déconnexion</a>
<?php endif; ?>

                </div>
              </div>
            </div>
        </div>    
        <!-- Menu latéral responsive -->
        <div id="main">
          <button class="openbtn" id="buttonAside"><img src="<?= BASE_URL ?>/assets/images/burger.svg" alt=""></button>
        </div>
        <div class="sidebar" id="mySidebar">
        <!-- Contenu conditionnel du menu latéral selon le rôle -->
        <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe'):?>
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#"><?= htmlspecialchars($employe['prenom']) ?></a>
          <a href="<?= BASE_URL ?>/pages/Accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/Contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= htmlspecialchars($lienCompte) ?>">Compte Pro</a>
          <a href="<?= BASE_URL ?>/actions/Logout.php">Déconnexion</a>

        <?php elseif (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#">Admin</a>
          <a href="<?= BASE_URL ?>/pages/Accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/Contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= htmlspecialchars($lienCompte) ?>">Compte Admin</a>
          <a href="<?= BASE_URL ?>/actions/LogoutAdmin.php">Déconnexion</a>

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
    <main id="pop">
        <div class="row1">
            <span class="highSpan">Espace Employé(e)</span>
        </div>
        <div class="back">
          <!-- Barre de recherche -->
          <div class="backSearchBar">
            <form id="searchForm">
                <div class="search-bar">
                    <input class="pseudo" type="search" name="username" id="pseudo" placeholder="Pseudo chauffeur">
                    <input class="numberCovoit" type="search" name="numero_trajet" id="numberCovoit" placeholder="Numéro de covoiturage">
                    <button type="submit" aria-label="Rechercher" class="ButtonRounded">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
            <div class="containerDivPrincipal">
                <div class="divPrincipal">
                    <div>
                        <span class="textDivPrincipal">Avis utilisateurs</span>
                        <hr class="hrDivPrincipal">
                    </div>
                   <div class="avisAffiche">
  <div class="middle">    
    <!-- Message si aucun avis n'est disponible -->
    <?php if (empty($avisList)): ?>
  <p class="no-avis">Aucun avis à afficher pour le moment.</p>
<?php endif; ?>
   
    <!-- Boucle d'affichage des avis -->
    <?php foreach ($avisList as $avis): ?>
      <div class="avis-container">
        <div class="avis-header">
          <div class="infos">
            <img src="<?= BASE_URL ?>/assets/images/profil.svg" class="user" alt="profil">
            <span class="avis-nom"><?= htmlspecialchars($avis['username']) ?></span>
          </div>
          <div class="avis-note">★ <?= htmlspecialchars($avis['note']) ?>/5</div>
        </div>
        <div class="commentaire"><?= nl2br(htmlspecialchars($avis['commentaire'])) ?></div>
        <a href="#" class="voir-plus">Voir plus</a>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<!-- Lien vers la page complète des avis si des avis existent -->
<?php if (!empty($avisList)): ?>
                    <div>
                        <a href="<?= BASE_URL ?>/pages/AvisEmployesTotal.php" class="LinkAvis">Plus d'avis ...</a>
                    </div>
<?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <footer> 
        <div>
            <!-- Liens du footer -->
            <a href="<?= BASE_URL ?>/pages/MentionsLegales.php" class="mentions-legales">mentions légales</a>
            <a href="<?= BASE_URL ?>/pages/Contact.php" class="mentions-legales"> contact </a>
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
    <script src="<?= BASE_URL ?>/assets/javascript/Menu.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/EspaceEmploye.js"></script>
</body>
</html>