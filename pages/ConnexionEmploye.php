<?php
// Démarrage de session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}

// Inclusion des fichiers de protection et sécurité
include __DIR__ . '/../includes/HeaderProtection.php';
include __DIR__ . '/../includes/Csrf.php';

// Traitement du formulaire si soumis en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../actions/Connexion.php'; // Inclure le script de traitement
    exit; // Empêche l'exécution du reste de la page après traitement
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="website icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title>Connexion employé(e)</title>
</head>
<body>
    <header>
        <div class="space">
            <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <a href="<?= BASE_URL ?>/AccueilECF.php" class="menu-principal">Accueil</a>
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
                  <!-- Menu de navigation pour non connectés -->
                  <a href="<?= BASE_URL ?>/pages/ConnexionUtilisateur.php" class="linkNav">Connexion</a>
                  <a href="<?= BASE_URL ?>/pages/InscriptionECF.php" class="linkNav">Inscription</a>
                  <hr>
                  <a href="<?= BASE_URL ?>/pages/ConnexionEmploye.php" class="linkNav">Compte Pro</a>
                </div>
              </div>
            </div>
        </div>     
        
        <!-- Menu burger pour mobile -->
        <div id="main">
          <button class="openbtn" id="buttonAside"><img src="<?= BASE_URL ?>/assets/images/burger.svg" alt=""></button>
        </div>
        
        <!-- Sidebar / Menu latéral -->
        <div class="sidebar" id="mySidebar">
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="<?= BASE_URL ?>/AccueilECF.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/ContactECF.php">Contact</a>
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
        <!-- Formulaire de connexion employé -->
        <form method="post" action="<?= BASE_URL ?>/actions/Connexion.php">
            <?= csrf_input() ?> <!-- Token CSRF pour la sécurité -->
            <h1 class="title">Connexion Pro</h1>
            
            <!-- Affichage des erreurs de connexion -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message"><?= htmlspecialchars($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <!-- Champ email -->
            <input class="text-block" type="email" name="email" id="E-mail" 
                   placeholder="Votre adresse mail" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                   
            <!-- Champ mot de passe -->
            <input class="text-block" type="password" name="password" id="mot-de-passe" 
                   placeholder="Votre mot de passe" autocomplete="off" required>
                   
            <!-- Case à cocher pour afficher/masquer le mot de passe -->
            <div class="checkbox-container">
                <input type="checkbox" id="showPassword">
                <label for="showPassword">Afficher mot de passe</label>
            </div>
            
            <hr class="hrForm">
            
            <!-- Bouton de soumission -->
            <button type="submit" aria-label="Connexion" class="buttonSubmit">Connexion</button>
        </form>
        
        <!-- Script pour afficher/masquer le mot de passe -->
        <script>
            document.getElementById('showPassword').addEventListener('change', function(e) {
                const passwordField = document.getElementById('mot-de-passe');
                passwordField.type = e.target.checked ? 'text' : 'password';
            });
        </script>
    <script>
        const BASE_URL = "<?= BASE_URL ?>";
    </script>
        <script src="<?= BASE_URL ?>/assets/javascript/Menu.js"></script>
    </main>
</body>
</html>