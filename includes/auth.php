<?php
// Démarrage de la session pour gérer l'état de connexion
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/Config.php';

// Variables par défaut pour l'état non connecté
$estConnecte = false;
$user = [];
$role = '';
$lienCompte = '';
$username = '';

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['user'])) {
    $estConnecte = true;
    $user = $_SESSION['user'];
    $role = $user['role'] ?? '';
    $username = $user['username'] ?? '';
    
    // Déterminer le lien du compte selon le rôle de l'utilisateur
    switch ($role) {
        case 'admin':
            $lienCompte = BASE_URL . '/pages/EspaceAdministrateur.php';
            break;
            
        case 'employe':
            $lienCompte = BASE_URL . '/pages/CompteEmploye.php';

            try {
                $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Vérification que l'employé existe bien en base de données
                $stmt = $pdo->prepare("SELECT * FROM employe WHERE email = :email");
                $stmt->execute([':email' => $_SESSION['user']['email']]);
                $employe = $stmt->fetch(PDO::FETCH_ASSOC);

                // Si l'employé n'existe pas, déconnexion
                if (!$employe) {
                    header("Location: " . BASE_URL . "/actions/Logout.php");
                    exit;
                }

            } catch (PDOException $e) {
                die("Erreur : " . $e->getMessage());
            }
            break;
            
        case 'utilisateur':
            // Page par défaut pour les utilisateurs sans statut défini
            $lienCompte = BASE_URL . '/pages/ChoixStatut.php';

            // Détermination du lien du compte selon le statut de l'utilisateur
            if (isset($user['statut'])) {
                switch ($user['statut']) {
                    case 'chauffeur':
                        $lienCompte = BASE_URL . '/pages/CompteUtilisateurChauffeur.php';
                        break;
                    case 'passager':
                        $lienCompte = BASE_URL . '/pages/CompteUtilisateurPassager.php';
                        break;
                    case 'passager_chauffeur':
                        $lienCompte = BASE_URL . '/pages/CompteUtilisateurPassagerChauffeur.php';
                        break;
                }
            }
            break;
    }
}
?>