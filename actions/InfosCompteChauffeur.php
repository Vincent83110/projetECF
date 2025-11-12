<?php
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}


try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération des informations de l'utilisateur depuis la session
    $user = $_SESSION['user'] ?? null;
    $userIdUsers = $user['id'] ?? null; // id de la table users, important!

    // Redirection si l'utilisateur n'est pas connecté
    if (!$userIdUsers) {
        header("Location: " . BASE_URL . "/AccueilECF.php");
        exit;
    }

    // Récupération des véhicules de l'utilisateur selon user_id (id de users)
    $stmt = $pdo->prepare("SELECT id, marque, plaque_immatriculation, modele, couleur, capacite 
                           FROM vehicules 
                           WHERE user_id = :user_id 
                           ORDER BY id DESC");
    $stmt->execute([':user_id' => $userIdUsers]);
    $vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialisation des préférences
    $preferences = [];

    // Récupération du dernier trajet de l'utilisateur
    $stmtTrajet = $pdo->prepare("SELECT id FROM infos_trajet WHERE id_utilisateur = :user_id ORDER BY id DESC");
    $stmtTrajet->execute([':user_id' => $userIdUsers]);
    $trajetId = $stmtTrajet->fetchColumn();

    // Récupération des préférences associées au dernier trajet
    if ($trajetId) {
        $stmtPref = $pdo->prepare("SELECT texte FROM preferences WHERE trajet_id = :trajet_id");
        $stmtPref->execute([':trajet_id' => $trajetId]);
        $preferences = $stmtPref->fetchAll(PDO::FETCH_COLUMN);
    }

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
    exit;
}
?>