<?php 
require_once __DIR__ . '/../includes/Config.php';
include __DIR__ . '/../includes/HeaderProtection.php'; // Protection des en-têtes HTTP
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/Contact.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="website icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title>Contact</title>
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
                  <!-- Menu pour utilisateurs non connectés -->
                  <a href="<?= BASE_URL ?>/pages/ConnexionUtilisateur.php" class="linkNav">Connexion</a>
                  <a href="<?= BASE_URL ?>/pages/inscriptionECF.php" class="linkNav">Inscription</a>
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
        <!-- Pied de page -->
        <div>
            <a href="<?= BASE_URL ?>/pages/MentionsLegalesECF.php" class="mentions-legales">mentions légales</a>
            <a href="<?= BASE_URL ?>/pages/contact.html" class="mentions-legales"> contact </a>
        </div>
        <div>
            <!-- Liens réseaux sociaux -->
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
</body>
</html>