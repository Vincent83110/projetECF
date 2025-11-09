<?php 
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/auth.php';          // Gestion de l'authentification utilisateur
include __DIR__ . '/../actions/notif.php';         // Système de notifications
include __DIR__ . '/../includes/function.php';      // Fonctions utilitaires
include __DIR__ . '/../includes/csrf.php';

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Vérification si l'utilisateur est connecté et récupération de la note du conducteur si besoin
if ($estConnecte && isset($trajet['id_utilisateur'])) {
    $stmtNote = $pdo->prepare("SELECT note FROM utilisateurs WHERE id = :id");
    $stmtNote->execute([':id' => $trajet['id_utilisateur']]);
    $noteConducteur = $stmtNote->fetch(PDO::FETCH_ASSOC);
    
    // Si la note n'existe pas, on met une valeur par défaut
    $noteMoyenne = $noteConducteur['note'] ?? null;
}

$stmt = $pdo->prepare("SELECT * FROM infos_trajet WHERE statut IS NULL");
$stmt->execute();
$trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/covoiturages.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="website icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title>Covoiturages</title>
</head>
<body>
    <header>
        <div class="space">
           <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <!-- Lien d'accueil selon l'état de connexion -->
              <?php if ($estConnecte): ?>
                <a href="<?= BASE_URL ?>/pages/accueil.php" class="menu-principal" id="menu-principal">Accueil</a>
              <?php else: ?>
                <a href="<?= BASE_URL ?>/accueilECF.php" class="menu-principal" id="menu-principal">Accueil</a>
              <?php endif; ?>
              
              <!-- Section notifications pour utilisateurs connectés -->
              <div>
                 <?php if ($estConnecte): ?>
                    <div class="notif-container">
                        <!-- Icône de notification avec badge -->
                        <div class="bellNotif" id="toggleNotifications">
                            <i class="fas fa-bell"></i>
                            <?php if ($estConnecte && $notificationCount > 0): ?>
                                <span class="notif-badge"><?= $notificationCount ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Affichage des notifications -->
                        <?php if ($estConnecte && !empty($notifications)): ?>
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
                                        <!-- Formulaire pour traiter les demandes -->
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
                        <?php elseif ($estConnecte): ?>
                            <div class="notification" id="notificationMenu">
                                <p>Aucune nouvelle demande</p>
                            </div>
                        <?php endif; ?>
                    </div> 
                <?php endif; ?>     
            </div>
            </div>
            
            <!-- Navigation selon l'état de connexion -->
            <?php if ($estConnecte): ?>
                <!-- Navigation pour utilisateurs connectés -->
                <div class="nav">
                    <div class="ContainerTitleNavConnect" id="Nav">
                      <span class="titleNav">
                          <img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu">
                          <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="userNav">
                      </span>
                    </div>
                    <div class="InsideNav" id="InsideNav">
            <?php elseif (!$estConnecte): ?>
                <!-- Navigation pour utilisateurs non connectés -->
                <div>
                    <div class="nav2">
                        <div class="ContainerTitleNav" id="Nav">
                          <span class="titleNavHtml">
                              <img src="<?= BASE_URL ?>/assets/images/iconConnect.svg" alt=""> 
                              Mon compte 
                              <img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu">
                          </span>
                        </div>
                        <div class="InsideNav" id="InsideNav">
            <?php endif; ?>
            
            <!-- Contenu du menu selon le rôle -->
            <?php if ($estConnecte && $role === 'employe'): ?>
                <a href="#" class="linkNav"><?= htmlspecialchars($employe['prenom'] ?? 'Employé') ?></a>
                <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Mon compte</a>
                <a href="<?= BASE_URL ?>/actions/logout.php" class="linkNav">Déconnexion</a>
            <?php elseif ($estConnecte && $role === 'admin'): ?>
                <a href="#" class="linkNav">Admin</a>
                <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Compte Admin</a>
                <a href="<?= BASE_URL ?>/actions/logoutAdmin.php" class="linkNav">Déconnexion</a>
            <?php elseif ($estConnecte): ?>
                <a href="#" class="linkNav"><?= htmlspecialchars($user['username'] ?? 'Utilisateur') ?></a>
                <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Mon compte</a>
                <a href="<?= BASE_URL ?>/pages/TrajetIndividuel.php" class="linkNav">Mes trajets</a>
                <a href="<?= BASE_URL ?>/pages/historique.php" class="linkNav">Historique</a>
                <a href="<?= BASE_URL ?>/actions/logout.php" class="linkNav">Déconnexion</a>
            <?php elseif (!$estConnecte): ?>
                <!-- Menu pour non connectés -->
                <a href="<?= BASE_URL ?>/pages/ConnexionUtilisateur.php" class="linkNav">Connexion</a>
                <a href="<?= BASE_URL ?>/pages/InscriptionECF.php" class="linkNav">Inscription</a>
                <hr>
                <a href="<?= BASE_URL ?>/pages/ConnexionEmploye.php" class="linkNav">Compte Pro</a>
                    </div>
                  </div>
                </div>
            <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Formulaire de filtres pour version mobile -->
          <form method="get" action="#" class="etoilesSide filters">
            <div>
              <button class="cross">x</button>
              <div>
                <div class="space2">
                  <h1 class="TitleFiltres">Filtres</h1>
                  <button type="button" id="applyFilters2" class="buttonApply">Appliquer</button>
                  <!-- Filtre trajet écologique -->
                  <div class="containerFiltre">
                    <div class="alignCheck">
                      <label for="checkbox_eco1" class="filtres">Trajet écologique</label>
                    </div>
                    <div class="alignCheck">
                      <input type="checkbox" id="checkbox_eco1" name="eco">
                    </div>
                  </div>
                  <hr>
                  
                  <!-- Filtre durée du voyage -->
                  <span class="filtres">Durée du voyage :</span>
                  <div class="containerInputTime">
                    <div class="spaceLabelInput">
                        <label for="minTime1">Min  :</label>
                        <input type="time" id="minTime1" class="InputTime" name="min_time">
                    </div>
                    <div class="spaceLabelInput">
                        <label for="maxTime1">Max :</label>
                        <input type="time" id="maxTime1" class="InputTime" name="max_time">
                    </div>
                  </div>
                  <hr>
                  
                  <!-- Filtre prix -->
                  <span class="filtres">Prix : </span>
                  <div class="containerInputPrice">
                    <div class="spaceLabelInput">
                        <label for="minPrice1">Min  :</label>
                        <input type="number" id="minPrice1" class="InputPrice" min="0" name="min_price">
                    </div>
                    <div class="spaceLabelInput">
                        <label for="maxPrice1">Max :</label>
                        <input type="number" id="maxPrice1" class="InputPrice" min="0" name="max_price">
                    </div>
                  </div>
                  <hr>
                  
                  <!-- Filtre note minimale -->
                  <span class="filtres"> Note minimal :</span>
                </div>
                <!-- Système d'étoiles pour la note -->
                <div class="etoiles">
                    <input type="radio" name="note" id="star1Media" value="1">
                    <label for="star1Media"><img src="<?= BASE_URL ?>/assets/images/starOff.svg" class="etoile" alt="none"></label>
                    
                    <input type="radio" name="note" id="star2Media" value="2">
                    <label for="star2Media"><img src="<?= BASE_URL ?>/assets/images/starOff.svg" class="etoile" alt="none"></label>
                    
                    <input type="radio" name="note" id="star3Media" value="3">
                    <label for="star3Media"><img src="<?= BASE_URL ?>/assets/images/starOff.svg" class="etoile" alt="none"></label>
                    
                    <input type="radio" name="note" id="star4Media" value="4">
                    <label for="star4Media"><img src="<?= BASE_URL ?>/assets/images/starOff.svg" class="etoile" alt="none"></label>
                    
                    <input type="radio" name="note" id="star5Media" value="5">
                    <label for="star5Media"><img src="<?= BASE_URL ?>/assets/images/starOff.svg" class="etoile" alt="none"></label>
                </div>
                </div>
            </div>
          </form>
        </div>     
        
        <!-- Menu burger et sidebar -->
        <div id="main">
          <button class="openbtn" id="buttonAside"><img src="<?= BASE_URL ?>/assets/images/burger.svg" alt=""></button>
        </div>
        <div class="sidebar" id="mySidebar">
          <!-- Sidebar selon le type d'utilisateur -->
          <?php if ($estConnecte && $role === 'employe'): ?>
            <!-- Sidebar employé -->
          <?php elseif ($estConnecte && $role === 'admin'): ?>
            <!-- Sidebar admin -->
          <?php elseif ($estConnecte): ?>
            <!-- Sidebar utilisateur connecté -->
            <a href="#" class="closebtn" id="closebtn">×</a>
            <a href="#"><?= htmlspecialchars($user['username'] ?? 'Utilisateur') ?></a>
            <a href="<?= BASE_URL ?>/pages/accueil.php">Accueil</a>
            <a href="<?= BASE_URL ?>/pages/TrajetIndividuel.php">Mes trajets</a>
            <a href="<?= BASE_URL ?>/pages/historique.php">Historique</a>
            <a href="<?= BASE_URL ?>/pages/contact.php">Contact</a>
            <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
            <hr class="color">
            <a href="<?= htmlspecialchars($lienCompte) ?>">Mon compte</a>
            <a href="<?= BASE_URL ?>/actions/logout.php">Déconnexion</a>
          <?php else: ?>
            <!-- Sidebar non connecté -->
            <a href="#" class="closebtn" id="closebtn">×</a>
            <a href="<?= BASE_URL ?>/pages/accueil.php">Accueil</a>
            <a href="<?= BASE_URL ?>/pages/contact.php">Contact</a>
            <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
            <hr class="color">
            <a href="<?= BASE_URL ?>/pages/ConnexionUtilisateur.php">Connexion</a>
            <a href="<?= BASE_URL ?>/pages/Inscription.php">Inscription</a>
            <a href="<?= BASE_URL ?>/pages/ConnexionEmploye.php">Compte Pro</a>
          <?php endif; ?>
          
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
        <!-- Conteneur principal -->
        <div class="containerPrincipal">
            <!-- Colonne 1 : Filtres -->
            <div class="col1Covoit">
                <h1>Filtres</h1>
                <div>
                    <!-- Formulaire de filtres -->
                    <form method="get" id="filtersForm" onsubmit="return false;">
                        <button type="button" id="applyFilters" class="buttonApply">Appliquer</button>

                        <div class="space2">
                            <!-- Filtre trajet écologique -->
                            <div class="containerFiltre">
                                <label for="checkbox_eco2" class="filtres">Trajet écologique</label>
                                <input type="checkbox" id="checkbox_eco2" class="checkbox" name="eco">
                            </div>
                            <hr>
                            
                            <!-- Filtre durée du voyage -->
                            <span class="filtres">Durée du voyage :</span>
                            <div class="containerInputTime">
                                <div>
                                    <label for="minTime">Min :</label>
                                    <input type="time" id="minTime" class="InputTime" name="min_time">
                                </div>
                                <div>
                                    <label for="maxTime">Max :</label>
                                    <input type="time" id="maxTime" class="InputTime" name="max_time">
                                </div>
                            </div>
                            <hr>
                            
                            <!-- Filtre crédits -->
                            <span class="filtres">Crédits :</span>
                            <div class="containerInputPrice">
                                <div>
                                    <label for="minPrice">Min :</label>
                                    <input type="number" id="minPrice" class="InputPrice" min="0" name="min_price">
                                </div>
                                <div>
                                    <label for="maxPrice">Max :</label>
                                    <input type="number" id="maxPrice" class="InputPrice" min="0" name="max_price">
                                </div>
                            </div>
                            <hr>
                            
                            <!-- Filtre note minimale -->
                            <span class="filtres">Note minimale :</span>
                            <div>
                                <!-- Système d'étoiles pour la note -->
                                <input type="radio" name="note" id="star1" value="1">
                                <label for="star1"><img src="<?= BASE_URL ?>/assets/images/starOff.svg" class="etoile" alt="none"></label>

                                <input type="radio" name="note" id="star2" value="2">
                                <label for="star2"><img src="<?= BASE_URL ?>/assets/images/starOff.svg" class="etoile" alt="none"></label>

                                <input type="radio" name="note" id="star3" value="3">
                                <label for="star3"><img src="<?= BASE_URL ?>/assets/images/starOff.svg" class="etoile" alt="none"></label>

                                <input type="radio" name="note" id="star4" value="4">
                                <label for="star4"><img src="<?= BASE_URL ?>/assets/images/starOff.svg" class="etoile" alt="none"></label>

                                <input type="radio" name="note" id="star5" value="5">
                                <label for="star5"><img src="<?= BASE_URL ?>/assets/images/starOff.svg" class="etoile" alt="none"></label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Colonne 2 : Barre de recherche -->
            <div class="col2Covoit">
                <!-- Formulaire de recherche -->
                <form method="get" id="searchForm">
                    <div class="search-bar">
                        <input class="departure" type="text" id="departure" placeholder="Départ" name="departure" autocomplete="off" required>
                        <input class="arrived" type="text" id="destination" placeholder="Arrivée" name="destination" autocomplete="off" required>
                        <input class="date" type="date" id="date" name="date" required>
                        <input class="number" type="number" id="passengers" name="passengers" placeholder="Nombre de passagers" min="1" max="8" step="1" required>
                        <button class="buttonSearch" type="submit" aria-label="Rechercher">
                            <i class="fas fa-search "></i>
                        </button>
                    </div>
                </form>

                <!-- Zone de suggestions d'adresses -->
                <div class="suggestions-sur-container">
                    <div class="suggestions-container">
                        <ul id="suggestions-departure" class="autocomplete-suggestions1"></ul>
                        <ul id="suggestions-destination" class="autocomplete-suggestions2"></ul>
                    </div>
                </div>

                <!-- Bouton filtres pour mobile -->
                <div class="lienButton">
                  <a href="#" class="button">Filtres</a>
                </div>
            </div>
            
            <!-- Colonne 3 : Résultats des covoiturages -->
            <div class="col3Covoit" id="col3Covoit">
                <!-- Les résultats des covoiturages seront injectés ici par JavaScript -->
            </div>
            
            <!-- Contrôles de pagination -->
            <div class="containerPagination">
                <div class="pagination-controls">
                    <button id="prevPage" class="buttonBefore" disabled>Précédent</button>
                    <span id="pageInfo">Page 1</span>
                    <button id="nextPage" class="buttonAfter" disabled>Suivant</button>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Scripts JavaScript -->
    <script>
        const BASE_URL = "<?= BASE_URL ?>";
        const trajets = <?= json_encode($trajets) ?>;
    </script>
    <script src="<?= BASE_URL ?>/assets/javascript/menu.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/notif.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/covoiturages.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/autocomplete.js"></script>
</body>
</html>