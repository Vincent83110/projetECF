<?php
session_start();

if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}


// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "Utilisateur non connecté.";
    header("Location:" . BASE_URL . "/pages/ConnexionUtilisateur.php");
    exit;
}

try {
    // Connexion à la base de données
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération du rôle et de l'ID utilisateur
    $role = $_SESSION['user']['role'] ?? 'utilisateur';
    
    // Pour un employé, utiliser le bon ID dans users
    $user_id = $_SESSION['user']['role'] === 'employe' 
               ? ($_SESSION['user']['user_id'] ?? $_SESSION['user']['id']) 
               : $_SESSION['user']['id'];

    $id_employe = $_GET['user_id'] ?? null;
    $target_username = $_GET['username'] ?? null;

    /* =========================================================
       CAS 1 : ADMIN supprime un utilisateur classique par username
    ============================================================ */
    if ($role === 'admin' && $target_username) {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE username = :pseudo");
        $stmt->execute([':pseudo' => $target_username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Suppression de la table utilisateurs
            $stmtDelete = $pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
            $stmtDelete->execute([':id' => $user['id']]);

            // Suppression de la table users
            $stmtDeleteUser = $pdo->prepare("DELETE FROM users WHERE username = :pseudo");
            $stmtDeleteUser->execute([':pseudo' => $target_username]);
            
            $_SESSION['success'] = "Compte supprimé avec succès.";
            header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php");
            exit;
        } else {
            $_SESSION['error'] = "Utilisateur introuvable.";
            header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php");
            exit;
        }
    }

    /* =========================================================
       CAS 2 : ADMIN supprime un employé par ID
    ============================================================ */
    elseif ($role === 'admin' && $id_employe) {
        // Récupération des informations de l'employé
        $stmtEmploye = $pdo->prepare("
            SELECT e.user_id, u.username 
            FROM employe e
            JOIN users u ON u.id = e.user_id
            WHERE e.user_id = :id
        ");
        $stmtEmploye->execute([':id' => $id_employe]);
        $employe = $stmtEmploye->fetch(PDO::FETCH_ASSOC);

        if ($employe) {
            // Supprimer de la table employe
            $stmtDeleteEmploye = $pdo->prepare("DELETE FROM employe WHERE user_id = :id");
            $stmtDeleteEmploye->execute([':id' => $employe['user_id']]);

            // Supprimer de la table users
            $stmtDeleteUser = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmtDeleteUser->execute([':id' => $employe['user_id']]);

            // Redirection immédiate
            $_SESSION['success'] = "Employé supprimé avec succès.";
            header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php");
            exit;
        } else {
            $_SESSION['error'] = "Employé introuvable.";
            header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php");
            exit;
        }
    }

    /* =========================================================
       CAS 3 : EMPLOYÉ supprime son propre compte
    ============================================================ */
    elseif ($role === 'employe') {
        // Récupération du username pour le log
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $employe = $stmt->fetch(PDO::FETCH_ASSOC);
        $username = $employe['username'] ?? 'Employe_ID_' . $user_id;

        // Supprimer d'abord de employe
        $stmtDeleteEmploye = $pdo->prepare("DELETE FROM employe WHERE user_id = :id");
        $stmtDeleteEmploye->execute([':id' => $user_id]);

        // Supprimer de users
        $stmtDeleteUser = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmtDeleteUser->execute([':id' => $user_id]);


        // Destruction de la session
        session_unset();
        session_destroy();

        $_SESSION['success'] = "Votre compte a été supprimé avec succès.";
        header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php");
        exit;
    }

    /* =========================================================
       CAS 4 : UTILISATEUR classique supprime son compte
    ============================================================ */
    elseif ($role === 'utilisateur') {
        // Récupération des données utilisateur
        $stmt = $pdo->prepare("SELECT username FROM utilisateurs WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        // Suppression de la table utilisateurs
        $stmtDelete = $pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
        $stmtDelete->execute([':id' => $user_id]);

        // Suppression de la table users
        $stmtDeleteUser = $pdo->prepare("DELETE FROM users WHERE username = :pseudo");
        $stmtDeleteUser->execute([':pseudo' => $userData['username']]);

        // Destruction de la session
        session_unset();
        session_destroy();
        
        $_SESSION['success'] = "Votre compte a été supprimé avec succès.";
        header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php");
        exit;
    }

} catch (Exception $e) {
    // Gestion des erreurs
    $_SESSION['error'] = "Erreur lors de la suppression : " . $e->getMessage();
    header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php");
    exit;
}