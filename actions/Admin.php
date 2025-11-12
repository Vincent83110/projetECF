<?php
// Démarrage de la session pour gérer l'état de connexion
session_start();
// Inclusion du fichier de configuration de la base de données
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}


try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    // Configuration pour afficher les erreurs PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération des données du formulaire avec opérateur null coalescent
    $email = $_POST['email'] ?? null;
    $pass = $_POST['password'] ?? null;

    // Vérification que tous les champs sont remplis
    if (!$email || !$pass) {
        echo "Veuillez remplir tous les champs.";
        exit;
    }

    // Recherche de l'utilisateur dans la table users avec rôle admin
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role = 'admin'");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérification si l'administrateur existe
    if (!$user) {
        echo "Administrateur non trouvé.";
        exit;
    }

    // Vérification du mot de passe hashé
    if (!password_verify($pass, $user['password'])) {
        echo "Mot de passe incorrect.";
        exit;
    }

    // Recherche des informations détaillées dans la table administrateur
    $stmt2 = $pdo->prepare("SELECT * FROM administrateur WHERE email = :email");
    $stmt2->execute([':email' => $email]);
    $admin_info = $stmt2->fetch(PDO::FETCH_ASSOC);

    // Vérification si les informations administrateur existent
    if (!$admin_info) {
        echo "Informations administrateur introuvables.";
        exit;
    }

    // Fusion des données utilisateur et administrateur dans la session
    $_SESSION['user'] = array_merge($user, $admin_info);

    // Redirection vers l'espace administrateur
    header("Location: " . BASE_URL ."/pages/EspaceAdministrateur.php");
    exit;

} catch (PDOException $e) {
    // Gestion des erreurs de base de données
    echo "Erreur : " . $e->getMessage();
}