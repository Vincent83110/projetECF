<?php
// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../includes/Config.php';
include __DIR__ . '/../includes/Auth.php';          // Gestion de l'authentification utilisateur
include __DIR__ . '/../actions/Notif.php';         // Système de notifications
include __DIR__ . '/../includes/Function.php';      // Fonctions utilitaires
include __DIR__ . '/../actions/InfosCompteChauffeur.php'; // Protection des en-têtes HTTP
include __DIR__ . '/../includes/Csrf.php';

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération de l'ID utilisateur depuis la session
    $userId = $_SESSION['user']['id'] ?? null;
    $userConnecte = $_SESSION['user'];

    // Vérification de l'autorisation - si pas d'ID utilisateur, erreur 401
    if (!$userId) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['success' => false, 'error' => 'Non autorisé']);
        exit;
    }

    // Gestion de la consultation de profil (soi-même ou autre utilisateur)
    if (isset($_GET['id'])) {
        // Cas où on consulte le profil d'un autre utilisateur via ID
        $profil_id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
        $stmt->execute([':id' => $profil_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            die("Utilisateur introuvable.");
        }
        // Vérifie si l'utilisateur connecté est le propriétaire du profil
        $est_proprietaire = ($profil_id === $userId);
    } else {
        // Cas où on consulte son propre profil
        $profil_id = $userId;
        $est_proprietaire = true;
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
        $stmt->execute([':id' => $profil_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            die("Utilisateur introuvable.");
        }
    }
    $est_visiteur = !$est_proprietaire;

    // Récupération des crédits et note de l'utilisateur
    $stmtCredits = $pdo->prepare('SELECT credits, note FROM utilisateurs WHERE id = :user_id');
    $stmtCredits->execute([':user_id' => $profil_id]);
    $user2 = $stmtCredits->fetch(PDO::FETCH_ASSOC);

    // Récupération des préférences utilisateur (évite les doublons)
    $stmtPreferences = $pdo->prepare("SELECT id, texte FROM preferences WHERE user_id = :id ORDER BY id DESC");
    $stmtPreferences->execute([':id' => $profil_id]);
    $results = $stmtPreferences->fetchAll(PDO::FETCH_ASSOC);

    $preferences = [];
    $seen = [];
    foreach ($results as $row) {
        if (!in_array($row['texte'], $seen)) {
            $preferences[] = $row['texte'];
            $seen[] = $row['texte'];
        }
    }

    // Récupération de la note moyenne (peut être null si pas de note)
    $noteMoyenne = isset($user2['note']) ? $user2['note'] : null;

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/CompteUtilisateur.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="website icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title>Compte utilisateur Passager</title>
</head>
<body>
    <header>
        <div class="space">
            <div class="accueil">
                <span class="logo">ECO RIDE</span>
                <a href="<?= BASE_URL ?>/pages/Accueil.php" class="menu-principal" id="menu-principal">Accueil</a>
                </div>
            
            <!-- Navigation utilisateur -->
            <div>
                <div class="nav">
                    <div class="ContainerTitleNavConnect" id="Nav">
                        <span class="titleNav">
                            <img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu">
                            <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="userNav">
                        </span>
                    </div>
                    <div class="InsideNav" id="InsideNav">
                        <!-- Menu déroulant selon le type d'utilisateur connecté -->
                        <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe'): ?>
                            <a href="#" class="linkNav"><?= htmlspecialchars($employe['prenom']) ?></a>
                            <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Mon compte</a>
                            <a href="<?= BASE_URL ?>/actions/Logout.php" class="linkNav">Déconnexion</a>
                        <?php elseif (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                            <a href="#" class="linkNav">Admin</a>
                            <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Compte Admin</a>
                            <a href="<?= BASE_URL ?>/actions/LogoutAdmin.php" class="linkNav">Déconnexion</a>
                        <?php else: ?>
                            <a href="#" class="linkNav"><?= htmlspecialchars($userConnecte['username']) ?></a>
                            <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Mon compte</a>
                            <a href="<?= BASE_URL ?>/pages/TrajetIndividuel.php" class="linkNav">Mes trajets</a>
                            <a href="<?= BASE_URL ?>/pages/Historique.php" class="linkNav">Historique</a>
                            <a href="<?= BASE_URL ?>/actions/Logout.php" class="linkNav">Déconnexion</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>     
        
        <!-- Menu burger pour version mobile -->
        <div id="main">
            <button class="openbtn" id="buttonAside"><img src="<?= BASE_URL ?>/assets/images/burger.svg" alt=""></button>
        </div>
        
        <!-- Sidebar / Menu latéral -->
        <div class="sidebar" id="mySidebar">
            <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe'): ?>
                <!-- Sidebar pour les employés -->
                <a href="#" class="closebtn" id="closebtn">×</a>
                <a href="#" class="linkNav"><?= htmlspecialchars($employe['prenom']) ?></a>
                <a href="<?= BASE_URL ?>/pages/Accueil.php">Accueil</a>
                <a href="<?= BASE_URL ?>/pages/Contact.php">Contact</a>
                <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
                <hr class="color">
                <a href="<?= htmlspecialchars($lienCompte) ?>">Compte Pro</a>
                <a href="<?= BASE_URL ?>/actions/Logout.php">Déconnexion</a>
            <?php elseif (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                <!-- Sidebar pour les administrateurs -->
                <a href="#" class="closebtn" id="closebtn">×</a>
                <a href="#">Admin</a>
                <a href="<?= BASE_URL ?>/pages/Accueil.php">Accueil</a>
                <a href="<?= BASE_URL ?>/pages/Contact.php">Contact</a>
                <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
                <hr class="color">
                <a href="<?= htmlspecialchars($lienCompte) ?>">Compte Admin</a>
                <a href="<?= BASE_URL ?>/actions/LogoutAdmin.php">Déconnexion</a>
            <?php else: ?>
                <!-- Sidebar pour les utilisateurs standard -->
                <a href="#" class="closebtn" id="closebtn">×</a>
                <a href="#"><?= htmlspecialchars($userConnecte['username']) ?></a>
                <a href="<?= BASE_URL ?>/pages/Accueil.php">Accueil</a>
                <a href="<?= BASE_URL ?>/pages/TrajetIndividuel.php">Mes trajets</a>
                <a href="<?= BASE_URL ?>/pages/Historique.php">Historique</a>
                <a href="<?= BASE_URL ?>/pages/Contact.php">Contact</a>
                <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
                <hr class="color">
                <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Mon compte</a>
                <a href="<?= BASE_URL ?>/actions/Logout.php">Déconnexion</a>
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
        <!-- Affichage du profil selon si c'est le propriétaire ou un visiteur -->
        <?php if ($est_proprietaire): ?>
            <!-- Vue propriétaire du compte - l'utilisateur voit son propre profil -->
            <div class="account">
                <div class="row1 col1">
                    <div class="infos-user">
                        <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="user">
                        <div class="size2">
                            <div class="ecart">
                                <span class="name"><?= htmlspecialchars($user['username']) ?></span>
                            </div>
                            <div>
                                <span class="statut">Passager</span>
                                <!-- Lien pour changer de statut -->
                                <a href="<?= BASE_URL ?>/pages/ChoixStatut.php" class="linkText">Changer</a>
                            </div>
                        </div>
                    </div> 
                    <div class="ecart2">
                        <a href="#" class="linkText">changer de photo</a>
                        <span class="email"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <!-- Lien pour modifier le profil -->
                    <a href="<?= BASE_URL ?>/pages/ModifierProfilUtilisateur.php" class="linkText">Modifier mon profil</a>
                    <div class="col2">
                        <hr class="line">
                        <!-- Affichage des crédits -->
                        <div class="div3">
                            Credits : <?= htmlspecialchars(($user2['credits'] === null ? '0' : $user2['credits'])) ?>
                        </div>
                        <div>
                            <a href="#" class="travel">Ajouter des crédits <img src="<?= BASE_URL ?>/assets/images/plus.svg" class="imgPlus" alt=""></a>
                        </div>
                        <hr class="line">
                        <!-- Lien pour supprimer le compte -->
                        <div class="delete">
                            <a href="<?= BASE_URL ?>/actions/DeleteAccount.php" class="del">Supprimer le compte</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($est_visiteur): ?>
            <!-- Vue visiteur du compte - consultation d'un autre utilisateur -->
            <div class="account">
                <div class="row1 col1">
                    <div class="infos-user">
                        <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="user">
                        <div class="size2">
                            <div class="ecart">
                                <span class="name"><?= htmlspecialchars($user['username']) ?></span>
                            </div>
                            <div>
                                <span class="statut">Passager</span>
                            </div>
                        </div>
                    </div> 
                    <div class="ecart2">
                        <span class="email"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    
                    <!-- Bouton d'envoi de message pour les utilisateurs connectés -->
                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'utilisateur'): ?>
                        <div class="sur_container">
                            <div class="container_send_message">
                                <a href="#" class="send_message" 
                                    onclick="openChatWithAnimation(<?= $profil_id ?>, '<?= addslashes($user['username']) ?>'); event.preventDefault();">
                                    Envoyer un message <img src="<?= BASE_URL ?>/assets/images/message.svg" class="imgPlus" alt="">
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="col2">
                        <hr class="line">
                        <div class="div3">
                            Credits : <?= htmlspecialchars(($user2['credits'] === null ? '0' : $user2['credits'])) ?>
                        </div>
                        <div>
                            <a href="#" class="travel">Ajouter des crédits <img src="<?= BASE_URL ?>/assets/images/plus.svg" class="imgPlus" alt=""></a>
                        </div>
                        <hr class="line">
                        
                        <!-- Lien de suppression pour les administrateurs -->
                        <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                            <div class="delete">
                                <a href="<?= BASE_URL ?>/actions/DeleteAccount.php?username=<?= urlencode($user['username']) ?>" class="del">Supprimer le compte</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
    
    <!-- Widget de messagerie pour les utilisateurs connectés -->
    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'utilisateur'): ?>
        <!-- Bulle de messagerie flottante -->
        <div id="chat-widget">
            <!-- Icône + compteur de messages non lus -->
            <div id="chat-notif">
                <i class="fas fa-comment-dots"></i>
                <span id="unread-count"></span>
            </div>

            <!-- Fenêtre de chat -->
            <div id="chat-box" class="hidden">
                <div class="chat-sidebar">
                    <!-- Liste des conversations -->
                    <div id="conversation-list"></div>
                </div>
                <div class="chat-main">
                    <div class="chat-header">
                        <div id="chat-header">Discussion</div>
                        <!-- Bouton de fermeture -->
                        <div id="closebtnMess" class="close">X</div>
                    </div>
                    <!-- Container des messages -->
                    <div id="message-container" class="scrollable-chat"></div>
                    <!-- Formulaire d'envoi de message -->
                    <form id="chat-form">
                        <?= csrf_input() ?>
                        <input type="text" id="chat-input" placeholder="Votre message..." autocomplete="off">
                        <button type="submit" class="button_chat">>></button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <footer> 
        <!-- Pied de page standard -->
        <div>
            <a href="<?= BASE_URL ?>/pages/MentionsLegales.php" class="mentions-legales">mentions légales</a>
            <a href="<?= BASE_URL ?>/pages/Contact.php" class="mentions-legales">contact</a>
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
        const currentUserId = <?= json_encode($_SESSION['user']['id']) ?>;
    </script>
    <script src="<?= BASE_URL ?>/assets/javascript/Menu.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/Notif.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/Messagerie.js"></script>

</body>
</html>