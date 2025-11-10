<?php
session_start();
require_once __DIR__ . '/../includes/Config.php';
// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header("Location: " .BASE_URL. "/pages/ConnexionUtilisateur.php");
    exit;
}


try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

     // 1. Vérification de la session utilisateur
    if (!isset($_SESSION['user']['id'])) {
        (header("Location: " . BASE_URL . "/AccueilECF.php"));
    }

    // Récupération de l'ID du profil à afficher (soit depuis GET, soit depuis la session)
    $profil_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user']['id'];

    // 1. Recherche d'abord dans la table utilisateurs
    $stmt = $pdo->prepare("
        SELECT u.id, ut.statut, ut.username, 
               COALESCE(u.role, 'utilisateur') as role
        FROM utilisateurs ut
        LEFT JOIN users u ON ut.user_id = u.id
        WHERE ut.id = :id
    ");
    $stmt->execute([':id' => $profil_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Détermination du type de compte basé sur le rôle et le statut
        $role = strtolower(trim($result['role'] ?? ''));
        $statut = strtolower(trim($result['statut'] ?? ''));

        // Redirection vers la page employé si rôle = employe
        if ($role === 'employe') {
            header("Location: " .BASE_URL. "/pages/CompteEmploye.php?id=$profil_id");
            exit;
        }
        // Redirection vers les pages utilisateurs selon le statut
        else {
            switch ($statut) {
                case 'passager':
                    header("Location: " .BASE_URL. "/pages/CompteUtilisateurPassager.php?id=$profil_id");
                    exit;
                case 'chauffeur':
                    header("Location: " .BASE_URL. "/pages/CompteUtilisateurChauffeur.php?id=$profil_id");
                    exit;
                case 'passager_chauffeur':
                case 'passagerchauffeur':
                    header("Location: " .BASE_URL. "/pages/CompteUtilisateurPassagerChauffeur.php?id=$profil_id");
                    exit;
                default:
                    // Par défaut, redirection vers passager
                    header("Location: " .BASE_URL. "/pages/CompteUtilisateurPassager.php?id=$profil_id");
                    exit;
            }
        }
    }

    // 2. Si non trouvé dans utilisateurs, recherche dans la table employe
    $stmt = $pdo->prepare("SELECT id FROM employe WHERE id = :id");
    $stmt->execute([':id' => $profil_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        header("Location: " .BASE_URL. "/pages/CompteEmploye.php?id=$profil_id");
        exit;
    }

    // 3. Si aucun profil trouvé dans aucune table
    die("Profil introuvable. L'ID $profil_id n'existe dans aucune table.");

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>