<?php
// Inclusion de la protection des en-têtes
require_once __DIR__ . '/includes/Config.php';
include __DIR__ . '/includes/HeaderProtection.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/Accueil.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" 
    rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="website icon" type="image/png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title> Accueil </title>
</head>
<body>
    <header>
        <div class="space">
            <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <!-- Lien vers la page d'accueil ECF -->
              <a href="<?= BASE_URL ?>/AccueilECF.php" class="navigation" id="menu-principal">Accueil</a>
            </div>
            <div>
                <!-- Navigation utilisateur non connecté -->
                <div class="nav">
                    <div class="ContainerTitleNav" id="Nav">
                      <!-- Menu déroulant "Mon compte" -->
                      <span class="titleNavHtml"><img src="<?= BASE_URL ?>/assets/images/iconConnect.svg" alt=""> Mon compte <img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="fleche" class="ImgFlecheMenu"></span>
                    </div>
                    <!-- Sous-menu avec options de connexion/inscription -->
                    <div class="InsideNav" id="InsideNav">
                  <!-- Lien vers la connexion utilisateur -->
                  <a href="<?= BASE_URL ?>/pages/ConnexionUtilisateur.php" class="linkNav">Connexion</a>
                  <!-- Lien vers l'inscription ECF -->
                  <a href="<?= BASE_URL ?>/pages/InscriptionECF.php" class="linkNav">Inscription</a>
                  <hr>
                  <!-- Lien vers la connexion employé (compte pro) -->
                  <a href="<?= BASE_URL ?>/pages/ConnexionEmploye.php" class="linkNav">Compte Pro</a>
                </div>
              </div>
            </div>
        </div>    
        <!-- Bouton menu mobile -->
        <div id="main">
          <button class="openbtn" id="buttonAside"><img src="<?= BASE_URL ?>/assets/images/burger.svg" alt=""></button>
        </div>
        <!-- Menu latéral responsive -->
        <div class="sidebar" id="mySidebar">
          <!-- Bouton de fermeture -->
          <a href="#" class="closebtn" id="closebtn">×</a>
          <!-- Liens de navigation -->
          <a href="<?= BASE_URL ?>/pages/AccueilECF.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/ContactECF.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegalesECF.php">Mentions Legales</a>
          <hr class="color">
          <!-- Liens de compte -->
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
    <!-- Image d'en-tête -->
    <div class="full-width-image">
        <img src="<?= BASE_URL ?>/assets/images/VoitureElectrique.jpg" alt="pas d'image" class="imgAccueil">
    </div>
    <!-- Contenu principal identique à Accueil.php -->
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
    <!-- Section des cadres d'information -->
    <div class="frames">
        <div class="div-frame1 divs-framess">
        <button class="arrow-left1" aria-label="Precedent"><img src="<?= BASE_URL ?>/assets/images/fleche-gauche.svg" alt="" class="fleche-gauche"></button>
            <div class="FrameRow">
                <img src="<?= BASE_URL ?>/assets/images/Frame1.jpeg" alt="VoitureElectriqueSurFeuille" id="Frame1" class="Frame1">
                <div class="text-block">
                    <p>Tout commence lors d'un embouteillage interminable sur le périphérique parisien. José, ingénieur passionné d'écologie, observe les innombrables voitures autour de lui, la plupart transportant une seule personne. Il se demande : "Pourquoi ne pas optimiser ces trajets en réunissant des personnes qui vont dans la même direction ?". Ce moment d'introspection devient le point de départ d'EcoRide, une idée ambitieuse pour réduire l'impact environnemental des déplacements tout en favorisant des économies pour tous. </p>
                </div>
                <button class="seeMore">Voir plus</button>
            </div>
        <button class="arrow-right1" aria-label="Suivant"><img src="<?= BASE_URL ?>/assets/images/fleche-droite.svg" alt="" class="fleche-droite"></button>
        </div>

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
<!-- Pied de page avec liens ECF -->
<footer> 
    <div>
        <!-- Liens vers les pages légales ECF -->
        <a href="<?= BASE_URL ?>/pages/MentionsLegalesECF.php" class="mentions-legales">mentions légales</a>
        <a href="<?= BASE_URL ?>/pages/ContactECF.php" class="mentions-legales"> contact </a>
    </div>
    <div>
        <!-- Liens réseaux sociaux -->
        <a href="https://www.youtube.com/" class="gapReseaux" target="_blank"><i class="fab fa-youtube"></i></a>
        <a href="https://www.instagram.com/" class="gapReseaux" target="_blank"><i class="fab fa-instagram"></i></a>
        <a href="https://x.com/?lang=fr&mx=2" class="gapReseaux" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>
        <a href="https://fr.pinterest.com/" class="gapReseaux" target="_blank"><i class="fab fa-pinterest"></i></a>
    </div>
</footer>
<!-- Scripts JavaScript -->

<script src="<?= BASE_URL ?>/assets/javascript/Menu.js"></script>
<script src="<?= BASE_URL ?>/assets/javascript/Accueil.js"></script>
<script src="<?= BASE_URL ?>/assets/javascript/Autocomplete.js"></script>
</body>
</html>