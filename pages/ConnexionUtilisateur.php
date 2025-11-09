<?php
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/csrf.php';
include __DIR__ . '/../includes/headerProtection.php';

// Gestion des redirections depuis les emails
$redirect = $_GET['redirect'] ?? null;
$trajet_id = $_GET['trajet_id'] ?? null;
$user = $_GET['user'] ?? null;

// Construction de l'URL d'action avec paramètres si présents
$action_url = BASE_URL . "/actions/connexion.php";
if ($redirect && $trajet_id && $user) {
    $action_url .= "?redirect=" . urlencode($redirect) . "&trajet_id=" . urlencode($trajet_id) . "&user=" . urlencode($user);
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/ConnexionInscrption.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="website icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title>Connexion utilisateur</title>
</head>
<body>
    <header>
        <div class="space">
            <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <a href="<?= BASE_URL ?>/accueilECF.php" class="menu-principal">Accueil</a>
            </div>
            <div>
              <div class="nav">
                <div class="ContainerTitleNav" id="Nav">
                  <span class="titleNav">
                      <img src="<?= BASE_URL ?>/assets/images/iconConnect.svg" alt=""> 
                      Mon compte 
                      <img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu">
                  </span>
                </div>
                <div class="InsideNav" id="InsideNav">
                  <!-- Menu pour utilisateurs non connectés -->
                  <a href="<?= BASE_URL ?>/pages/ConnexionUtilisateur.php" class="linkNav">Connexion</a>
                  <a href="<?= BASE_URL ?>/pages/InscriptionECF.php" class="linkNav">Inscription</a>
                  <hr>
                  <a href="<?= BASE_URL ?>/pages/ConnexionEmploye.php" class="linkNav">Compte Pro</a>
                </div>
              </div>
            </div>
        </div>
        </div>     
        
        <!-- Menu burger pour mobile -->
        <div id="main">
          <button class="openbtn" id="buttonAside"><img src="<?= BASE_URL ?>/assets/images/burger.svg" alt=""></button>
        </div>
        
        <!-- Sidebar pour non connectés -->
        <div class="sidebar" id="mySidebar">
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="<?= BASE_URL ?>/accueilECF.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/contactECF.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegalesECF.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= BASE_URL ?>/pages/ConnexionEmploye.php">Compte Pro</a>
          <a href="<?= BASE_URL ?>/pages/ConnexionUtilisateur.php">Connexion</a>
          <a href="<?= BASE_URL ?>/pages/InscriptionECF.php">Inscription</a>
          
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
        <!-- Formulaire de connexion -->
        <form method="post" action="<?= htmlspecialchars($action_url) ?>">
            <?= csrf_input() ?>
            <h1 class="title">Connexion</h1>
            
            <!-- Champ email -->
            <input class="text-block" type="email" name="email" id="E-mail" placeholder="Votre adresse mail" required>
            
            <!-- Champ mot de passe -->
            <input class="text-block" type="password" name="password" id="mot-de-passe" placeholder="Votre mot de passe" autocomplete="off" required>
            
            <!-- Case à cocher pour afficher/masquer le mot de passe -->
            <div class="checkbox-container">
                <input type="checkbox" id="showPassword">
                <label for="show-password">Afficher mot de passe</label>
            </div>
            
            <hr class="hrForm">
            
            <!-- Bouton de soumission -->
            <button type="submit" aria-label="Connexion" class="buttonSubmit"> Connexion </button>
        </form>
            <script>
        const BASE_URL = "<?= BASE_URL ?>";
    </script>
        <!-- Script pour afficher/masquer le mot de passe -->
        <script src="<?= BASE_URL ?>/assets/javascript/ShowPassword.js"></script>
        <script src="<?= BASE_URL ?>/assets/javascript/menu.js"></script>
    </main>
</body>
</html>