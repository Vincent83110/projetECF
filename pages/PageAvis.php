<?php
// Inclusion des fichiers nécessaires pour l'authentification, les notifications, les fonctions utilitaires, la configuration, la sécurité et la protection CSRF
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}

include __DIR__ . '/../includes/Auth.php';   // Gestion de l'authentification utilisateur
include __DIR__ . '/../actions/Notif.php'; 
include __DIR__ . '/../includes/Function.php';          
include __DIR__ . '/../includes/HeaderProtection.php';  // Fonctions utilitaires
include __DIR__ . '/../includes/Csrf.php';

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Vérification de la session utilisateur
    if (!isset($_SESSION['user']['id'])) {
        (header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php"));
    }

    // 2. Récupération INFALLIBLE de l'utilisateur connecté
    $currentUserId = $_SESSION['user']['id'];
    
    // Requête qui fonctionne même sans entrée dans 'utilisateurs'
    $query = "SELECT 
                u.id, 
                COALESCE(u.username, u.email) AS username,
                u.email,
                u.role,
                COALESCE(ut.note, 0) AS note_moyenne,
                COALESCE(ut.photo, 'default.jpg') AS photo,
                COALESCE(ut.statut, 'actif') AS statut
              FROM users u
              LEFT JOIN utilisateurs ut ON u.id = ut.user_id
              WHERE u.id = :id
              LIMIT 1";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $currentUserId]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    // Gestion des cas où l'utilisateur n'est pas trouvé dans la table users
    if (!$currentUser) {
        // Dernière tentative pour les employés/admins
        if (isset($_SESSION['user']['role']) && in_array($_SESSION['user']['role'], ['employe', 'admin'])) {
            $query = "SELECT 
                        id, 
                        prenom || ' ' || nom AS username,
                        email,
                        'employe' AS role,
                        0 AS note_moyenne,
                        'default.jpg' AS photo,
                        'actif' AS statut
                      FROM employe
                      WHERE user_id = :id
                      LIMIT 1";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([':id' => $currentUserId]);
            $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Création d'un tableau minimal pour éviter les erreurs si l'utilisateur n'est toujours pas trouvé
        if (!$currentUser) {
            $currentUser = [
                'id' => $currentUserId,
                'username' => $_SESSION['user']['username'] ?? 'Utilisateur',
                'email' => $_SESSION['user']['email'] ?? '',
                'role' => $_SESSION['user']['role'] ?? 'utilisateur',
                'note_moyenne' => 0,
                'photo' => 'default.jpg',
                'statut' => 'actif'
            ];
        }
    }

    // 3. Récupération du profil à afficher
    $chauffeurUsername = trim($_GET['user'] ?? '');
    $profil = null;

    // Recherche du profil spécifié par nom d'utilisateur
    if (!empty($chauffeurUsername)) {
        // Recherche par username/email
        $query = "SELECT 
                    u.id, 
                    COALESCE(u.username, u.email) AS username,
                    u.email,
                    u.role,
                    COALESCE(ut.note, 0) AS note_moyenne,
                    COALESCE(ut.photo, 'default.jpg') AS photo,
                    COALESCE(ut.statut, 'actif') AS statut
                  FROM users u
                  LEFT JOIN utilisateurs ut ON u.id = ut.user_id
                  WHERE LOWER(u.username) = LOWER(:identifiant)
                     OR LOWER(u.email) = LOWER(:identifiant)
                  LIMIT 1";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([':identifiant' => $chauffeurUsername]);
        $profil = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Si pas de profil spécifié ou non trouvé, afficher le profil courant
    if (!$profil) {
        $profil = $currentUser;
        $isViewingOwnProfile = true;
    } else {
        $isViewingOwnProfile = ($profil['id'] == $currentUser['id']);
    }

    // 4. Gestion des avis (version ultra-robuste)

// Récupérer le nom d'utilisateur depuis l'URL
$username_chauffeur = $_GET['user'] ?? '';

// Vérification de la présence du nom d'utilisateur
if (empty($username_chauffeur)) {
    die("Nom d'utilisateur non spécifié.");
}

// Requête pour obtenir l'ID du chauffeur
// Dans PageAvis.php, remplacez la requête actuelle par :
$query_chauffeur = "SELECT u.id AS user_id, ut.id AS utilisateur_id, ut.note, u.username 
                   FROM users u
                   LEFT JOIN utilisateurs ut ON u.id = ut.user_id
                   WHERE u.username = :username OR u.email = :username
                   LIMIT 1";
$stmt_chauffeur = $pdo->prepare($query_chauffeur);
$stmt_chauffeur->execute([':username' => $username_chauffeur]);
$chauffeur = $stmt_chauffeur->fetch(PDO::FETCH_ASSOC);

// Vérification que le chauffeur a été trouvé
if (!$chauffeur) {
    die("Chauffeur non trouvé.");
}

$id_chauffeur = $chauffeur['utilisateur_id'];
$noteMoyenne = $chauffeur['note'] ?? 0; // Récupération de la note depuis utilisateurs.note

// Après avoir récupéré $chauffeur
$username_chauffeur = $chauffeur['username']; // Utilisez directement le username du résultat

// Configuration de la pagination pour les avis
$avisParPage = 8;
$pageCourante = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($pageCourante - 1) * $avisParPage;

// Requête pour récupérer les avis avec pagination
$query = "
    SELECT a.*, u_auteur.username AS auteur_username
    FROM avis a
    JOIN utilisateurs u_chauffeur ON a.id_chauffeur = u_chauffeur.id
    JOIN utilisateurs u_auteur ON a.id_utilisateur = u_auteur.id
    WHERE u_chauffeur.username = :username_chauffeur
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($query);
$stmt->bindValue(':username_chauffeur', $username_chauffeur, PDO::PARAM_STR);
$stmt->bindValue(':limit', $avisParPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$avisList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Requête pour le nombre total d'avis (pour la pagination)
$queryTotal = "
    SELECT COUNT(*) as total
    FROM avis a
    JOIN utilisateurs u_chauffeur ON a.id_chauffeur = u_chauffeur.id
    WHERE u_chauffeur.username = :username_chauffeur
";
$stmtTotal = $pdo->prepare($queryTotal);
$stmtTotal->execute([':username_chauffeur' => $username_chauffeur]);
$totalAvis = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalAvis / $avisParPage);

} catch (PDOException $e) {
    // Journalisation des erreurs de base de données
    error_log("Erreur DB: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/PageAvis.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" 
    rel="stylesheet">
    <link rel="icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title> Page d'avis </title>
</head>
<body>
    <header>
        <div class="space">
            <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <a href="<?= BASE_URL ?>/pages/Accueil.php" class="menu-principal" id="menu-principal">Accueil</a>
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
            <a class="linkCovoit" href="<?= BASE_URL ?>/pages/PageCovoiturageIndividuelle.php?id=<?= $notif['trajet_id'] ?>">Détails...</a>
        </div>
       </div>  
        <span><?= htmlspecialchars($notif['username']) ?> à réservé votre trajet <?=  extraireVille(htmlspecialchars($notif['adresse_depart'])) ?> -> 
        <?= extraireVille(htmlspecialchars($notif['adresse_arrive'])) ?> du <?= formatDate(htmlspecialchars($notif['date_depart'])) ?></span>
        <form method="POST" action="<?= BASE_URL ?>/actions/TraiterDemande.php">
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
                      <span class="titleNav"><img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu"><img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="userNav"></span>
                    </div>
                    <div class="InsideNav" id="InsideNav">
            <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe'): ?>
    <a href="#" class="linkNav"><?= htmlspecialchars($employe['prenom']) ?></a>
    <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Mon compte</a>
    <a href="<?= BASE_URL ?>/actions/Logout.php" class="linkNav">Déconnexion</a>

<?php elseif (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
    <a href="#" class="linkNav">Admin</a>
    <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Compte Admin</a>
    <a href="<?= BASE_URL ?>/actions/LogoutAdmin.php" class="linkNav">Déconnexion</a>

<?php else: ?>
    <a href="#" class="linkNav"><?= htmlspecialchars($user['username']) ?></a>
    <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Mon compte</a>
    <a href="<?= BASE_URL ?>/pages/TrajetIndividuel.php" class="linkNav">Mes trajets</a>
    <a href="<?= BASE_URL ?>/pages/Historique.php" class="linkNav">Historique</a>
    <a href="<?= BASE_URL ?>/actions/Logout.php" class="linkNav">Déconnexion</a>
<?php endif; ?>

                </div>
              </div>
            </div>
        </div>     
        <div id="main">
          <button class="openbtn" id="buttonAside"><img src="<?= BASE_URL ?>/assets/images/burger.svg" alt=""></button>
        </div>
        <div class="sidebar" id="mySidebar">
          <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe'): ?>
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#"><?= htmlspecialchars($employe['prenom']) ?></a>
          <a href="<?= BASE_URL ?>/pages/Accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/div>Contact.php">Contact</a>
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
       
        <?php else: ?>
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#"><?= htmlspecialchars($user['username']) ?></a>
          <a href="<?= BASE_URL ?>/pages/Accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/TrajetIndividuel.php">Mes trajets</a>
          <a href="<?= BASE_URL ?>/pages/Historique.php">Historique</a>
          <a href="<?= BASE_URL ?>/pages/Contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= htmlspecialchars($lienCompte) ?>">Mon compte</a>
          <a href="<?= BASE_URL ?>/actions/Logout.php">Déconnexion</a>
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
    <div class="center">
        <div class="infos-user">
            <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="user" />
            <div class="col">
                <div class="alignProfil">
                  <span class="note">
                    ★ <?= $noteMoyenne !== null ? htmlspecialchars(entierOuDecimal(number_format($noteMoyenne, 2))) : '--' ?>/5
                  </span>
                </div>
                <div>
                    <span><?= htmlspecialchars($totalAvis) ?> avis</span>
                </div>
            </div>
        </div>
    </div>

    <div class="avis">
        <span>Avis</span>
    </div>
    <hr class="hrMessage" />

    <?php if (!empty($_GET['trajet_id'])): ?>
    <?php
        $trajetId = (int)$_GET['trajet_id'];
        $userId = $currentUser['id'] ?? 0;
        $hasAvis = false;

        if ($trajetId > 0) {
            try {
                $stmt = $pdo->prepare("
                    SELECT 1 
                    FROM avis 
                    WHERE id_trajet = :trajet_id 
                      AND id_utilisateur = :utilisateur_id
                    LIMIT 1
                ");
                $stmt->execute([
                    ':trajet_id' => $trajetId,
                    ':utilisateur_id' => $userId
                ]);
                $hasAvis = $stmt->fetchColumn() ? true : false;
            } catch (PDOException $e) {
                error_log("Erreur DB: " . $e->getMessage());
            }
        }
    ?>
    <form action="<?= BASE_URL ?>/actions/Avis.php" method="post">
        <?= csrf_input() ?>
        <input type="hidden" name="id_chauffeur" value="<?= htmlspecialchars($chauffeur['user_id'] ?? '') ?>">
        <input type="hidden" name="id_trajet" value="<?= htmlspecialchars($trajetId) ?>">
        <input type="hidden" name="redirect_user" value="<?= htmlspecialchars($chauffeur['username'] ?? '') ?>">

        <div class="containerArea">
            <textarea
                name="commentaire"
                id="messageInput"
                class="message"
                placeholder="Envoyer un avis ..."
                required
            ></textarea>

            <div class="containerNote">
                <label for="etoiles" class="textNote">Donnez une note :</label>
                <div class="etoiles">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <input type="radio" name="note" id="star<?= $i ?>" value="<?= $i ?>" />
                        <label for="star<?= $i ?>"><img src="<?= BASE_URL ?>/assets/images/starOff.svg" class="etoile" alt="none" /></label>
                    <?php endfor; ?>
                </div>
            </div>
<div class="buttonContainerAvis">
    <button type="submit" 
            class="buttonSubmitAvis" 
            <?= $hasAvis ? 'disabled style="background-color: grey; cursor: not-allowed;"' : '' ?>>
        Envoyer
    </button>
</div>


        </div>
    </form>
<?php endif; ?>


  <div class="avisAffiche">
    <div class="middle">
        <?php 
        // Filtrer les avis pour ne garder que ceux avec statut = 'valide'
        $avisValides = array_filter($avisList ?? [], function($avis) {
            return isset($avis['statut']) && strtolower($avis['statut']) === 'valide';
        });
        ?>

        <?php if (empty($avisValides)): ?>
            <p>Aucun avis validé pour le moment.</p>
        <?php else: ?>
            <?php foreach ($avisValides as $avis): ?>
                <div class="avis-container">
                    <div class="avis-header">
                        <div class="infos">
                            <img src="<?= BASE_URL ?>/assets/images/profil.svg" class="user" alt="profil" />
                            <span class="avis-nom"><?= htmlspecialchars($avis['username'] ?? $avis['auteur_username'] ?? 'N/A') ?></span><br />
                        </div>
                        <div class="avis-note">★ <?= htmlspecialchars(entierOuDecimal(number_format($avis['note'], 2 ?? '--'))) ?>/5</div>
                    </div>
                    <div class="commentaire"><?= nl2br(htmlspecialchars($avis['commentaire'] ?? '')) ?></div>
                    <a href="#" class="voir-plus">Voir plus</a>
                    <a href="<?= BASE_URL ?>/pages/PageCovoiturageIndividuelle.php?id=<?= htmlspecialchars($avis['id_trajet'] ?? '') ?>" class="trajet-info">Détails ...</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
    <!-- Pagination -->
     <div class="containerPagination">
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $pageCourante): ?>
                    <span class="current-page"><?= $i ?></span>
                <?php else: ?>
                    <a href="?user=<?= urlencode($username_chauffeur) ?>&page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
      </div>
</main>
    <footer> 
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
    <script src="<?= BASE_URL ?>/assets/javascript/Notif.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/PageAvis.js"></script>
</body>
</html>