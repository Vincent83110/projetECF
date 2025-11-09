<?php
// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/auth.php';          // Gestion de l'authentification utilisateur
include __DIR__ . '/../includes/function.php';      // Fonctions utilitaires
include __DIR__ . '/../includes/headerProtection.php'; // Protection des en-têtes HTTP
include __DIR__ . '/../includes/csrf.php';
try {
    // Connexion à la base de données
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération des informations employé si connecté en tant qu'employé
    if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe') {
        $id = $_SESSION['user']['id'];
        $stmt = $pdo->prepare("SELECT * FROM employe WHERE id = ?");
        $stmt->execute([$id]);
        $employe = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Configuration de la pagination
    $avisParPage = 8; // Nombre d'avis par page
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
    $offset = ($page - 1) * $avisParPage;

    // Récupération des avis avec pagination
    $stmt = $pdo->prepare("
        SELECT a.id, a.commentaire, a.note, a.id_utilisateur, a.id_trajet, u.username
        FROM avis a
        JOIN utilisateurs u ON a.id_utilisateur = u.id
        WHERE a.commentaire IS NOT NULL 
          AND a.commentaire <> '' 
          AND a.statut = 'invalide'
        ORDER BY a.id DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $avisParPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $avisList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcul du nombre total de pages
    $totalStmt = $pdo->query("
        SELECT COUNT(*) FROM avis 
        WHERE commentaire IS NOT NULL 
          AND commentaire <> '' 
          AND statut = 'invalide'
    ");
    $totalAvis = $totalStmt->fetchColumn();
    $totalPages = ceil($totalAvis / $avisParPage);

} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CSS avec versionnement pour éviter le cache -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/CompteEmploye.css?v=<?= time() ?>">
    <!-- Préchargement des polices -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Icônes Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Police Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" 
    rel="stylesheet">
    <!-- Favicon -->
    <link rel="website icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title> Avis utilisateurs </title>
</head>
<body>
    <header>
        <div class="space">
            <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <a href="<?= BASE_URL ?>/pages/accueil.php" class="menu-principal">Accueil</a>
            </div>
            <div>
                <div class="nav">
                    <div class="ContainerTitleNavConnect" id="Nav">
                      <!-- Icône du menu utilisateur -->
                      <span class="titleNav"><img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu"><img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="userNav"></span>
                    </div>
                    <!-- Sous-menu de navigation -->
                    <div class="InsideNav" id="InsideNav">
            <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe'): ?>
    <!-- Menu spécifique pour les employés -->
    <a href="#" class="linkNav"><?= htmlspecialchars($employe['prenom']) ?></a>
    <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Mon compte</a>
    <a href="<?= BASE_URL ?>/actions/logout.php" class="linkNav">Déconnexion</a>
<?php endif; ?>

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
        <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe'): ?>
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#"><?= htmlspecialchars($employe['prenom']) ?></a>
          <a href="<?= BASE_URL ?>/pages/accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= htmlspecialchars($lienCompte) ?>">Compte Pro</a>
          <a href="<?= BASE_URL ?>/actions/logout.php">Déconnexion</a>
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
        <!-- Titre de la page -->
        <div class="row1">
            <span class="highSpan">Tous les Avis</span>
        </div>
        <div class="back">
          <!-- Barre de recherche -->
          <div class="backSearchBar">
            <form id="searchForm">
                <div class="search-bar">
                    <!-- Champ de recherche par pseudo chauffeur -->
                    <input class="pseudo" type="search" name="username" id="pseudo" placeholder="Pseudo chauffeur">
                    <!-- Champ de recherche par numéro de covoiturage -->
                    <input class="numberCovoit" type="search" name="numero_trajet" id="numberCovoit" placeholder="Numéro de covoiturage">
                    <!-- Bouton de recherche -->
                    <button type="submit" aria-label="Rechercher" class="ButtonRounded">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
        <!-- Affichage des avis -->
        <div class="avisAffiche">
  <div class="middle">       
    <!-- Boucle sur chaque avis -->
    <?php foreach ($avisList as $avis): ?>
      <div class="avis-container">
        <div class="avis-header">
          <div class="infos">
            <!-- Avatar utilisateur -->
            <img src="<?= BASE_URL ?>/assets/images/profil.svg" class="user" alt="profil">
            <!-- Lien vers le profil de l'utilisateur -->
            <?php if (!empty($avis['id_utilisateur']) && !empty($avis['username'])): ?>
    <a href="<?= BASE_URL ?>/actions/compte.php?id=<?= htmlspecialchars($avis['id_utilisateur']) ?>&username=<?= htmlspecialchars($avis['username']) ?>">
        <?= htmlspecialchars($avis['username']) ?>
    </a>
<?php else: ?>
    utilisateur inconnu
<?php endif; ?>

          </div>
          <!-- Affichage de la note -->
          <div class="avis-note">★ <?= htmlspecialchars(entierOuDecimal($avis['note'])) ?>/5</div>
        </div>
        <!-- Commentaire de l'avis -->
        <div class="commentaire"><?= nl2br(htmlspecialchars($avis['commentaire'])) ?></div>
        <div class="voir-plus-align">
            <div>
                <!-- Bouton "Voir plus" pour les commentaires longs -->
                <a href="#" class="voir-plus">Voir plus</a>
                <!-- Lien vers les détails du trajet -->
                <?php if (!empty($avis['id_trajet'])): ?>
    <a href="<?= BASE_URL ?>/pages/pageCovoiturageIndividuelle.php?id=<?= htmlspecialchars($avis['id_trajet']) ?>" class="trajet-info">Détails ...</a>
<?php else: ?>
    <span class="trajet-info">Pas de trajet associé</span>
<?php endif; ?>
            </div>
        <!-- Formulaire pour valider ou supprimer l'avis -->
        <form action="<?= BASE_URL ?>/actions/ValideAvis.php" method="post">
          <?= csrf_input() ?>
    <input type="hidden" name="id_avis" value="<?= htmlspecialchars($avis['id']) ?>">
    <div class="button-container">
        <button type="submit" name="action" value="valider" class="buttonAvis">Valider</button>
        <button type="submit" name="action" value="supprimer" class="buttonAvis">Supprimer</button>
    </div>
</form>


        </div>
      </div>
    <?php endforeach; ?>
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="<?= $i === $page ? 'active' : '' ?>" href="?page=<?= $i ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

  </div>
  </div>
    </main>
    <!-- Pied de page -->
    <footer> 
        <div>
            <!-- Liens légaux -->
            <a href="<?= BASE_URL ?>/pages/MentionsLegales.php" class="mentions-legales">mentions légales</a>
            <a href="<?= BASE_URL ?>/pages/contact.php" class="mentions-legales"> contact </a>
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
     <script>
        const BASE_URL = "<?= BASE_URL ?>";
    </script>
<script src="<?= BASE_URL ?>/assets/javascript/AvisEmployeTotal.js"></script>
<script src="<?= BASE_URL ?>/assets/javascript/menu.js"></script>

</body>
</html>