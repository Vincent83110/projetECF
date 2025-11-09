<?php
// Connexion à la base de données et inclusion de la protection CSRF
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/csrf.php';

// Vérification que la méthode de requête est POST
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

    // Informations de l'administrateur à insérer (en dur ici)
    $adminEmail = 'admin9@gmail.com';
    $adminPassword = 'Admin13@';
    $role = 'admin';

    // Vérifie si l'email est déjà utilisé dans la table `users`
    $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $check->execute([':email' => $adminEmail]);

    if ($check->fetchColumn() > 0) {
        echo "Erreur : cet email est déjà utilisé.";
        exit();
    }

    // Hachage du mot de passe pour la sécurité
    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);

    // Démarrage de la transaction pour assurer l'intégrité des données
    $pdo->beginTransaction();

    // 1. Insertion dans `users` avec RETURNING id pour récupérer l'ID généré
    $stmtUser = $pdo->prepare("
        INSERT INTO users (email, password, role)
        VALUES (:email, :password, :role)
        RETURNING id
    ");
    $stmtUser->execute([
        ':email' => $adminEmail,
        ':password' => $hashedPassword,
        ':role' => $role
    ]);

    // Récupération de l'ID de l'utilisateur créé
    $userId = $stmtUser->fetchColumn();

    // 2. Insertion dans `administrateur` avec le même email et mot de passe
    $stmtAdmin = $pdo->prepare("
        INSERT INTO administrateur (email, password)
        VALUES (:email, :password)
    ");
    $stmtAdmin->execute([
        ':email' => $adminEmail,
        ':password' => $hashedPassword
    ]);

    // Validation de la transaction
    $pdo->commit();

    echo "Administrateur ajouté avec succès.";

} catch (PDOException $e) {
    // En cas d'erreur, annulation de la transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Erreur lors de l'inscription : " . $e->getMessage();
}