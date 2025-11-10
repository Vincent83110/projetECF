<?php
session_start();

require_once __DIR__ . '/../includes/Config.php';

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
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
       CAS 1 : ADMIN supprime un utilisateur classique (par username)
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
            
            header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php?Compte_supprimée");
            exit;
        } else {
            header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php?error=utilisateur_introuvable");
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
            header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php?message=employe_supprime");
            exit;
        } else {
            header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php?error=employe_introuvable");
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

        header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php?message=compte_supprime");
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
        
        header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php?message=compte_supprime");
        exit;
    }

} catch (Exception $e) {
    // Gestion des erreurs
    header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php?error=suppression_impossible");
    exit;
}