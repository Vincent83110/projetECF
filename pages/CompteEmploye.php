<?php 
require_once __DIR__ . '/../includes/Config.php';
include __DIR__ . '/../includes/Auth.php';          // Gestion de l'authentification utilisateur
include __DIR__ . '/../includes/Function.php';         // Système de notifications

$employe = null;
$est_visiteur = false;

// Vérification si un admin consulte un profil employé
if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin' && isset($_GET['user_id'])) {
    $id = $_GET['user_id']; // ID de l'employé consulté
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("SELECT * FROM employe WHERE user_id = ?");
    $stmt->execute([$id]);
    $employe = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $est_visiteur = true; // L'admin consulte un autre profil

// Vérification si un employé consulte son propre compte
} elseif (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe') {
    $id = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'];
    $stmt = $pdo->prepare("SELECT * FROM employe WHERE user_id = ?");
    $stmt->execute([$id]);
    $employe = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $est_visiteur = false; // L'employé consulte son propre profil
}

if (!$employe) {
    die("Aucun employé trouvé ou accès refusé.");
}

// Récupération du username pour la suppression
$username_employe = '';
if ($est_visiteur) {
    $stmtUser = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmtUser->execute([$id]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $username_employe = $userData['username'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/CompteEmploye.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="website icon" type="image/png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title>Compte employé(e)</title>
</head>
<body>
    <header>
        <!-- Structure du header avec navigation -->
        <div class="space">
            <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <a href="<?= BASE_URL ?>/pages/Accueil.php" class="menu-principal">Accueil</a>
            </div>
            <div>
                <div class="nav">
                    <div class="ContainerTitleNavConnect" id="Nav">
                      <span class="titleNav">
                          <img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu">
                          <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="userNav">
                      </span>
                    </div>
                    <div class="InsideNav" id="InsideNav">
                        <!-- Menu de navigation selon le rôle -->
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
        
        <!-- Menu burger pour mobile -->
        <div id="main">
          <button class="openbtn" id="buttonAside"><img src="<?= BASE_URL ?>/assets/images/burger.svg" alt=""></button>
        </div>
        
        <!-- Sidebar -->
        <div class="sidebar" id="mySidebar">
            <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe'): ?>
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
        <!-- Section principale du profil employé -->
        <div class="row1">
            <span class="highSpan">Compte Employé(e)</span>
        </div>
        <div class="back">
            <div class="containerDiv1">
                <div class="div1">
                    <div>
                        <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="" class="ImageProfil">
                    </div>
                    <div class="col1">
                        <div>
                            <span class="textName"><?= htmlspecialchars($employe['prenom']) ?> <?= htmlspecialchars($employe['nom']) ?></span>
                        </div>
                        <div>
                            <span class="textStatut"><?= htmlspecialchars($employe['fonction']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="center">
                <div class="containerDiv2">
                    <!-- Informations de contact -->
                    <div class="div2">
                        <span class="textEmail">E-mail : <a href="#" class="LinkEmail"><?= htmlspecialchars($employe['email']) ?></a></span>
                    </div>
                    <div class="div2">
                        <span class="textNum">numéro : <?= htmlspecialchars($employe['telephone']) ?></span>
                    </div>
                    <div class="div2">
                        <span class="textHire">Embaucher depuis le : <?= htmlspecialchars(formatDate($employe['date_embauche'])) ?></span>
                    </div>
                    
                    <!-- Lien de modification du profil (uniquement pour l'employé lui-même) -->
                    <?php if ($_SESSION['user']['role'] === 'employe') : ?>
                    <div>
                        <a href="<?= BASE_URL ?>/pages/ModifierProfilEmploye.php" class="textModif">Modifier mon profil</a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Accès à l'espace employé -->
                    <div class="div2">
                        <?php if ($_SESSION['user']['role'] === 'employe') : ?>
                            <div class="div2">
                                <a href="<?= BASE_URL ?>/pages/EspaceEmploye.php
                                " class="textSpace">Espace employé</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <hr>
                    
                    <!-- Lien de suppression du compte -->
                    <?php if ($_SESSION['user']['role'] === 'admin') : ?>
                        <?php if ($est_visiteur) : ?>
                            <!-- L'admin supprime un employé qu'il consulte -->
                            <div class="div2">
                                <a href="<?= BASE_URL ?>/actions/DeleteAccount.php?user_id=<?= urlencode($employe['user_id']) ?>" class="textDel">Supprimer le compte</a>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($_SESSION['user']['role'] === 'employe') : ?>
                        <!-- L'employé supprime son propre compte -->
                        <div class="div2">
                            <a href="<?= BASE_URL ?>/actions/DeleteAccount.php" class="textDel">Supprimer le compte</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <footer> 
        <!-- Pied de page -->
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
</body>
</html>