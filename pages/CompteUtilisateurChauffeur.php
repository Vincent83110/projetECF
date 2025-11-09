<?php
// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/auth.php';          // Gestion de l'authentification utilisateur
include __DIR__ . '/../actions/notif.php';         // Système de notifications
include __DIR__ . '/../includes/function.php';      // Fonctions utilitaires
include __DIR__ . '/../actions/infosCompteChauffeur.php'; // Protection des en-têtes HTTP
include __DIR__ . '/../includes/csrf.php';

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération de l'ID utilisateur depuis la session
    $userId = $_SESSION['user']['id'] ?? null;
    $userConnecte = $_SESSION['user'];

    // Vérification de l'autorisation
    if (!$userId) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['success' => false, 'error' => 'Non autorisé']);
        exit;
    }

    // Gestion de la consultation de profil via différents paramètres
    if (isset($_GET['id'])) {
        // Consultation par ID utilisateur
        $profil_id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
        $stmt->execute([':id' => $profil_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            die("Utilisateur introuvable.");
        }
        $est_proprietaire = ($profil_id === $userId);
    } elseif (isset($_GET['pseudo'])) {
        // Consultation par pseudo
        $pseudo = $_GET['pseudo'];
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE username = :username");
        $stmt->execute([':username' => $pseudo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            die("Utilisateur introuvable.");
        }
        $profil_id = $user['id'];
        $est_proprietaire = ($profil_id === $userId);
    } else {
        // Consultation de son propre profil
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

    // Récupération des avis sur l'utilisateur (en tant que chauffeur)
    $stmtAvis = $pdo->prepare("
        SELECT 
            a.*,
            u.username AS auteur_username
        FROM avis a
        JOIN infos_trajet t ON a.id_trajet = t.id
        JOIN utilisateurs u ON a.id_utilisateur = u.id
        WHERE t.id_utilisateur = :id_chauffeur
        AND a.id_utilisateur != t.id_utilisateur
        ORDER BY a.id DESC
    ");
    $stmtAvis->execute([':id_chauffeur' => $profil_id]);
    $avis = $stmtAvis->fetchAll(PDO::FETCH_ASSOC);

    // Récupération de la note moyenne depuis la table utilisateurs
    $stmtNote = $pdo->prepare("SELECT note FROM utilisateurs WHERE id = :id");
    $stmtNote->execute([':id' => $profil_id]);
    $noteData = $stmtNote->fetch(PDO::FETCH_ASSOC);
    $noteMoyenne = $noteData['note'] ?? null;

    // Récupération des crédits
    $stmtCredits = $pdo->prepare('SELECT credits FROM utilisateurs WHERE id = :id');
    $stmtCredits->execute([':id' => $profil_id]);
    $userCredits = $stmtCredits->fetch(PDO::FETCH_ASSOC);

    // Récupération des véhicules
    $stmtVehicules = $pdo->prepare("SELECT * FROM vehicules WHERE user_id = :id");
    $stmtVehicules->execute([':id' => $profil_id]);
    $vehicules = $stmtVehicules->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des préférences (évite les doublons)
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
    <title>Compte utilisateur Chauffeur</title>
</head>
<body>
    <header>
        <!-- Structure du header similaire aux autres pages -->
        <div class="space">
            <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <a href="<?= BASE_URL ?>/pages/accueil.php" class="menu-principal" id="menu-principal">Accueil</a>
              
              <!-- Section notifications pour utilisateurs -->
              <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'utilisateur' && ($_SESSION['user']['statut'] === 'passager_chauffeur' || $_SESSION['user']['statut'] === 'chauffeur')): ?>

                     <div>
                <div class="notif-container">
                    <!-- Icône de notification avec badge -->
                    <div class="bellNotif" id="toggleNotifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($notificationCount > 0): ?>
                            <span class="notif-badge"><?= $notificationCount ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Affichage des notifications -->
                    <?php if (!empty($notifications)): ?>
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
                                    <!-- Formulaire pour accepter/refuser les demandes -->
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
                    <?php else: ?>
                        <div class="notification" id="notificationMenu">
                            <p>Aucune nouvelle demande</p>
                        </div>
                    <?php endif; ?>
                </div> 
            </div>
            <?php endif; ?>
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
                        <!-- Menu selon le type d'utilisateur -->
                        <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe'): ?>
                            <a href="#" class="linkNav"><?= htmlspecialchars($employe['prenom']) ?></a>
                            <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Mon compte</a>
                            <a href="<?= BASE_URL ?>/actions/logout.php" class="linkNav">Déconnexion</a>
                        <?php elseif (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                            <a href="#" class="linkNav">Admin</a>
                            <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Compte Admin</a>
                            <a href="<?= BASE_URL ?>/actions/logoutAdmin.php" class="linkNav">Déconnexion</a>
                        <?php else: ?>
                            <a href="#" class="linkNav"><?= htmlspecialchars($userConnecte['username']) ?></a>
                            <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Mon compte</a>
                            <a href="<?= BASE_URL ?>/pages/TrajetIndividuel.php" class="linkNav">Mes trajets</a>
                            <a href="<?= BASE_URL ?>/pages/historique.php" class="linkNav">Historique</a>
                            <a href="<?= BASE_URL ?>/actions/logout.php" class="linkNav">Déconnexion</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>     
        
        <!-- Menu burger et sidebar (structure standard) -->
        <div id="main">
          <button class="openbtn" id="buttonAside"><img src="<?= BASE_URL ?>/assets/images/burger.svg" alt=""></button>
        </div>
        <div class="sidebar" id="mySidebar">
            <!-- Contenu de la sidebar selon le type d'utilisateur -->
            <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employe'): ?>
                <!-- Sidebar employé -->
            <?php elseif (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                <!-- Sidebar admin -->
            <?php else: ?>
                <!-- Sidebar utilisateur standard -->
                <a href="#" class="closebtn" id="closebtn">×</a>
                <a href="#"><?= htmlspecialchars($userConnecte['username']) ?></a>
                <a href="<?= BASE_URL ?>/pages/accueil.php">Accueil</a>
                <a href="<?= BASE_URL ?>/pages/TrajetIndividuel.php">Mes trajets</a>
                <a href="<?= BASE_URL ?>/pages/historique.php">Historique</a>
                <a href="<?= BASE_URL ?>/pages/contact.php">Contact</a>
                <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
                <hr class="color">
                <a href="<?= htmlspecialchars($lienCompte) ?>">Mon compte</a>
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
        <!-- Affichage du profil selon si c'est le propriétaire ou un visiteur -->
        <?php if ($est_proprietaire): ?>
            <!-- Vue propriétaire du compte - avec toutes les fonctionnalités -->
            <div class="account">
                <div class="row1 col1">
                    <div class="infos-user">
                        <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="user">
                        <div class="size2">
                            <div class="ecart">
                                <span class="name"><?= htmlspecialchars($user['username']) ?></span>
                                <!-- Affichage de la note moyenne -->
                                <span class="note">
                                    ★ <?= $noteMoyenne !== null ? htmlspecialchars(entierOuDecimal(number_format($noteMoyenne, 2))) : '--' ?>/5
                                </span>
                            </div>
                            <div>
                                <span class="statut">Chauffeur</span>
                                <!-- Lien pour changer de statut -->
                                <a href="<?= BASE_URL ?>/pages/choixStatut.php" class="linkText">Changer</a>
                            </div>
                        </div>
                    </div> 
                    <div class="ecart2">
                        <a href="#" class="linkText">Changer de photo</a>
                        <span class="email"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <!-- Liens pour modifier le profil et ajouter du contenu -->
                    <div>
                        <a href="<?= BASE_URL ?>/pages/modifierProfilUtilisateur.php" class="linkText">Modifier mon profil</a>
                    </div>
                    <div>
                        <a href="<?= BASE_URL ?>/pages/InfosTrajetChauffeur.php" class="travel">Ajouter un voyage <img src="<?= BASE_URL ?>/assets/images/plus.svg" class="imgPlus" alt=""></a>
                    </div>
                    <div>
                        <a href="<?= BASE_URL ?>/pages/infosChauffeur.php" class="travel">Ajouter un vehicule <img src="<?= BASE_URL ?>/assets/images/plus.svg" class="imgPlus" alt=""></a>
                    </div>
                    
                    <!-- Section avec toutes les informations du profil -->
                    <div class="col2">
                        <hr class="line">
                        
                        <!-- Section Avis -->
                        <div class="div1">
                            <span class="title1">Avis</span>
                            <?php if (!empty($avis)): ?>
                                <?php foreach ($avis as $a): ?>
                                    <div>
                                        <span class="text_div1">
                                            <?= !empty($a['texte']) ? htmlspecialchars($a['texte']) : '' ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                                <!-- Lien pour voir plus d'avis -->
                                <a href="<?= BASE_URL ?>/pages/PageAvis.php?user=<?= urlencode($user['username']) ?>">plus d'avis ...</a>
                            <?php else: ?>
                                <p class="text_div1">Aucun avis trouvé.</p>
                            <?php endif; ?>
                        </div>
                        
                        <hr class="line">
                        
                        <!-- Section Conducteur/Véhicules -->
                        <div class="div2">
                            <span class="title2">Conducteur</span>
                            <?php if (!empty($vehicules)): ?>
                                <?php foreach ($vehicules as $v): ?>
                                    <div class="col3">
                                        <span class="textVehicule">Véhicule : <?= htmlspecialchars($v['marque']) ?> <?= htmlspecialchars($v['modele']) ?> - <?= htmlspecialchars($v['couleur']) ?></span>
                                        <span class="textVehicule">Plaque : <?= htmlspecialchars($v['plaque_immatriculation']) ?></span>
                                        <!-- Lien pour modifier le véhicule -->
                                        <div>
                                            <a href="<?= BASE_URL ?>/pages/modifier_vehicule.php?id=<?= htmlspecialchars($v['id']) ?>" class="btn-modifier">
                                                <i class="fas fa-edit"></i> Modifier
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text_div1">Aucun véhicule enregistré pour le moment.</p>
                            <?php endif; ?>
                        </div>

                        <hr class="line">
                        
                        <!-- Section Préférences -->
                        <div class="div3">
                            <span class="title3">Préférences</span>
                            <div class="col4">
                                <?php foreach ($preferences as $pref) : ?>
                                    <span class="text_div3"><?= htmlspecialchars($pref) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <hr class="line">
                        
                        <!-- Section Crédits -->
                        <div class="div3">
                            Credits : <?= htmlspecialchars($userCredits['credits'] ?? '0') ?>
                        </div>
                        <div>
                            <a href="#" class="travel">Ajouter des crédits <img src="<?= BASE_URL ?>/assets/images/plus.svg" class="imgPlus" alt=""></a>
                        </div>
                        
                        <hr class="line">
                        
                        <!-- Lien de suppression du compte -->
                        <div class="delete">
                            <a href="<?= BASE_URL ?>/actions/deleteAccount.php" class="del">Supprimer le compte</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($est_visiteur): ?>
            <!-- Vue visiteur du compte - informations limitées -->
            <div class="account">
                <div class="row1 col1">
                    <div class="infos-user">
                        <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="user">
                        <div class="size2">
                            <div class="ecart">
                                <span class="name"><?= htmlspecialchars($user['username']) ?></span>
                                <!-- Affichage de la note moyenne -->
                                <span>
                                    ★ <?= $noteMoyenne !== null ? htmlspecialchars(entierOuDecimal(number_format($noteMoyenne, 2))) : '--' ?>/5
                                </span>
                            </div>
                            <div>
                                <span class="statut">Passager-Chauffeur</span>
                            </div>
                        </div>
                    </div> 
                    <div>
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
                    
                    <!-- Section informations (version visiteur) -->
                    <div class="col2">
                        <hr class="line">
                        
                        <!-- Section Avis -->
                        <div class="div1">
                            <span class="title1">Avis</span>
                            <?php if (!empty($avis)): ?>
                                <?php foreach ($avis as $a): ?>
                                    <div>
                                        <span class="text_div1">
                                            <?= !empty($a['texte']) ? htmlspecialchars($a['texte']) : '' ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                                <a href="<?= BASE_URL ?>/pages/PageAvis.php?user=<?= urlencode($user['username']) ?>">plus d'avis ...</a>
                            <?php else: ?>
                                <p class="text_div1">Aucun avis trouvé.</p>
                            <?php endif; ?>
                        </div>
                        
                        <hr class="line">
                        
                        <!-- Section Conducteur/Véhicules (version lecture seule) -->
                        <div class="div2">
                            <span class="title2">Conducteur</span>
                            <?php if (!empty($vehicules)): ?>
                                <?php foreach ($vehicules as $v): ?>
                                    <div class="col3">
                                        <span class="textVehicule">Véhicule : <?= htmlspecialchars($v['marque']) ?> <?= htmlspecialchars($v['modele']) ?> - <?= htmlspecialchars($v['couleur']) ?></span>
                                        <span class="textVehicule">Plaque : <?= htmlspecialchars($v['plaque_immatriculation']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text_div1">Aucun véhicule enregistré pour le moment.</p>
                            <?php endif; ?>
                        </div>

                        <hr class="line">
                        
                        <!-- Section Préférences -->
                        <div class="div3">
                            <span class="title3">Préférences</span>
                            <div class="col4">
                                <?php foreach ($preferences as $pref) : ?>
                                    <span class="text_div3"><?= htmlspecialchars($pref) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <hr class="line">
                        
                        <!-- Lien de suppression pour les administrateurs -->
                        <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                            <div class="delete">
                                <a href="<?= BASE_URL ?>/actions/deleteAccount.php?username=<?= urlencode($user['username']) ?>" class="del">Supprimer le compte</a>
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
            <a href="<?= BASE_URL ?>/pages/contact.php" class="mentions-legales">contact</a>
        </div>
        <div>
            <!-- Liens vers les réseaux sociaux -->
            <a href="https://www.youtube.com/" class="gapReseaux" target="_blank"><i class="fab fa-youtube"></i></a>
            <a href="https://www.instagram.com/" class="gapReseaux" target="_blank"><i class="fab fa-instagram"></i></a>
            <a href="https://x.com/?lang=fr&mx=2" class="gapReseaux" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>
            <a href="https://fr.pinterest.com/" class="gapReseaux" target="_blank"><i class="fab fa-pinterest"></i></a>
        </div>
    </footer>
    <script>
        const BASE_URL = "<?= BASE_URL ?>";
        const currentUserId = <?= json_encode($_SESSION['user']['id']) ?>;
    </script>
    <script src="<?= BASE_URL ?>/assets/javascript/menu.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/notif.js"></script>
    <script src="<?= BASE_URL ?>/assets/javascript/messagerie.js"></script>
</body>
</html>