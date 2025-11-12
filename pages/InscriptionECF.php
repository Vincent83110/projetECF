<?php 
// Inclusion des fichiers de sécurité et de protection CSRF
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
} 
        
include __DIR__ . '/../includes/HeaderProtection.php';  // Fonctions utilitaires
include __DIR__ . '/../includes/Csrf.php';
 ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/ConnexionInscrption.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" 
    rel="stylesheet">
    <link rel="website icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title>Inscription</title>
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
                  <span class="titleNav"><img src="<?= BASE_URL ?>/assets/images/iconConnect.svg" alt=""> Mon compte <img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu"></span>
                </div>
                <div class="InsideNav" id="InsideNav">
                  <a href="<?= BASE_URL ?>/pages/ConnexionUtilisateur.php" class="linkNav">Connexion</a>
                  <a href="<?= BASE_URL ?>/pages/InscriptionECF.php" class="linkNav">Inscription</a>
                  <hr>
                  <a href="<?= BASE_URL ?>/pages/ConnexionEmploye.php" class="linkNav">Compte Pro</a>
                </div>
              </div>
            </div>
        </div>
        </div>     
        <div id="main">
          <button class="openbtn" id="buttonAside"><img src="<?= BASE_URL ?>/assets/images/burger.svg" alt=""></button>
        </div>
        <div class="sidebar" id="mySidebar">
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="<?= BASE_URL ?>/pages/AccueilECF.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/ContactECF.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegalesECF.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= BASE_URL ?>/pages/ConnexionEmploye.php">Compte Pro</a>
          <a href="<?= BASE_URL ?>/pages/ConnexionUtilisateur.php">Connexion</a>
          <a href="<?= BASE_URL ?>/pages/InscriptionECF.php">Inscription</a>
        </div>
    </header>
<main id="pop">
    <!-- Formulaire d'inscription avec protection CSRF -->
    <form method="post" action="<?= BASE_URL ?>/actions/Inscription.php">
      <?= csrf_input() ?>
        <h1 class="title">Inscription</h1>
        <input class="text-block" name="username" type="text" id="pseudo" placeholder="Votre pseudo" required>
        <input class="text-block" name="email" type="email" id="E-mail" placeholder="Votre adresse mail" required>
        <!-- Champ mot de passe avec validation complexe -->
        <input class="text-block" name="password" type="password" id="mot-de-passe" placeholder="Votre mot de passe" autocomplete="new-password" minlength="8" 
        pattern="^(?=(.*\d){3})(?=.*[A-Za-z])(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*]).{8,}$"
        title="Le mot de passe doit contenir au moins 8 caractères, incluant des lettres majuscules et minuscules, 3 chiffres minimum et 1 caractère spécial !" required>
        <div class="checkbox-container">
            <input type="checkbox" id="showPassword">
            <label for="show-password">Afficher mot de passe</label>
        </div>
        <p class="mx-auto MotDePasse">Utilisez au moins 8 caractères, avec une combinaison de lettres majuscules, minuscules, 3 chiffres minimum et 1 caractère spécial (!,@,#,$,%,^,&,*).
            Évitez les mots ou informations évidentes comme votre nom, votre date de naissance ou des suites simples comme '123456'.
            Préférez une phrase secrète ou une combinaison unique difficile à deviner.</p>
        <hr class="mx-auto">
        <button type="submit" name="submit" aria-label="Connexion" class="buttonSubmit"> Inscription </button>
    </form>
        <script>
        const BASE_URL = "<?= BASE_URL ?>";
    </script>
    <script src="<?= BASE_URL ?>/assets/javascript/ShowPassword.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/Menu.js"></script>
 </main>
</body>
</html>