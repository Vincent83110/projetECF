<?php
// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../includes/Config.php';
include __DIR__ . '/../includes/Csrf.php';

// Vérification que la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

// Vérification CSRF pour prévenir les attaques
if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
    die("Erreur CSRF : action non autorisée !");
}

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération des données du formulaire
    $formEmail = $_POST['email'] ?? null;
    $formPassword = $_POST['password'] ?? null;

    // Validation des champs obligatoires
    if (!$formEmail || !$formPassword) {
        echo "Veuillez remplir tous les champs.";
        exit;
    }

    // Recherche de l'utilisateur dans la table users
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindValue(':email', $formEmail);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérification si l'utilisateur existe
    if (!$user) {
        echo "Aucun utilisateur trouvé pour cet email.";
        exit;
    }

    // Vérification du mot de passe
    if (!password_verify($formPassword, $user['password'])) {
        echo "Mot de passe incorrect.";
        exit;
    }

    // Régénération de l'ID de session pour la sécurité
    session_regenerate_id(true);
    $_SESSION = []; // Nettoyage de la session
    
    // Récupération du rôle de l'utilisateur
    $role = $user['role'];

    // Récupération des paramètres de redirection depuis les emails
    $redirect = $_GET['redirect'] ?? null;
    $trajet_id = $_GET['trajet_id'] ?? null;
    $target_user = $_GET['user'] ?? null;

    // Traitement selon le rôle de l'utilisateur
    switch ($role) {
        case 'admin':
            // Connexion en tant qu'administrateur
            $stmt_admin = $pdo->prepare("SELECT * FROM administrateur WHERE email = :email");
            $stmt_admin->bindValue(':email', $formEmail);
            $stmt_admin->execute();
            $admin_info = $stmt_admin->fetch(PDO::FETCH_ASSOC);

            if (!$admin_info) {
                echo "Aucun administrateur trouvé dans la table 'administrateur'.";
                exit;
            }

            // Construction de la session administrateur
            $_SESSION['user'] = array_merge($user, $admin_info);

            // Redirection vers l'espace administrateur
            header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php");
            exit;

        case 'employe':
            // Connexion en tant qu'employé
            $stmt_employe = $pdo->prepare("SELECT id, prenom, nom, email, fonction, telephone, date_embauche, user_id FROM employe WHERE email = :email");
            $stmt_employe->bindValue(':email', $formEmail);
            $stmt_employe->execute();
            $employe_info = $stmt_employe->fetch(PDO::FETCH_ASSOC);

            if (!$employe_info) {
                $_SESSION['error'] = "Identifiants professionnels incorrects ou compte non autorisé.";
                header("Location: " . BASE_URL . "/pages/connexionEmploye.php");
                exit;
            }

            // Construction UNIFORME de la session employé
            $_SESSION['user'] = [
                // Clé PRIMAIRE uniforme (toujours user_id)
                'user_id' => $employe_info['user_id'], // Doit correspondre à users.id
                'id' => $employe_info['id'], // ID de la table employe
                
                // Données communes
                'email' => $user['email'],
                'role' => 'employe',
                
                // Données spécifiques
                'prenom' => $employe_info['prenom'],
                'nom' => $employe_info['nom'],
                'fonction' => $employe_info['fonction'],
                'telephone' => $employe_info['telephone'],
                'date_embauche' => $employe_info['date_embauche'],
                
                // Timestamp
                'last_login' => date('Y-m-d H:i:s')
            ];

            // Redirection vers la page employé
            header("Location: " . BASE_URL . "/pages/CompteEmploye.php");
            exit;

        case 'utilisateur':
            // Connexion en tant qu'utilisateur standard
            $stmt_statut = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = :email");
            $stmt_statut->bindValue(':email', $formEmail);
            $stmt_statut->execute();
            $statut_info = $stmt_statut->fetch(PDO::FETCH_ASSOC);

            if (!$statut_info || !isset($statut_info['statut']) || !isset($statut_info['username'])) {
                echo "Aucun statut trouvé pour cet utilisateur.";
                exit;
            }

            // Construction de la session utilisateur
            $_SESSION['user'] = array_merge($user, $statut_info);
            $statut = $statut_info['statut'];

            // Redirection conditionnelle depuis mail (pour laisser un avis)
           if ($redirect === 'PageAvis.php' && $trajet_id) {
            $url = BASE_URL . '/pages/PageAvis.php?trajet_id=' . urlencode($trajet_id);

            if (!empty($target_user)) {
                $url .= '&user=' . urlencode($target_user);
            }

            header("Location: $url");
            exit;
           }

            // Redirection classique selon le statut de l'utilisateur
            switch ($statut) {
                case 'chauffeur':
                    header("Location: " . BASE_URL . "/pages/CompteUtilisateurChauffeur.php");
                    exit;
                case 'passager':
                    header("Location: " . BASE_URL . "/pages/CompteUtilisateurPassager.php");
                    exit;
                case 'passager_chauffeur':
                    header("Location: " . BASE_URL . "/pages/CompteUtilisateurPassagerChauffeur.php");
                    exit;
                default:
                    echo "Statut d'utilisateur inconnu.";
                    exit;
            }

        default:
            echo "Rôle inconnu.";
            exit;
    }

} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}