<?php
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

    // Récupération des données du formulaire
    $formEmail = $_POST['email'] ?? null;
    $formPassword = $_POST['password'] ?? null;
    $formUsername = $_POST['username'] ?? null;

    // Rôle par défaut pour toute nouvelle inscription
    $role = 'utilisateur';

    // Validation des données
    if (!filter_var($formEmail, FILTER_VALIDATE_EMAIL)) {
        header("Location: " . BASE_URL . "/pages/InscriptionECF.php?error=Email invalide.");
        exit();
    }
    if (empty($formEmail) || empty($formPassword) || empty($formUsername)) {
        header("Location: " . BASE_URL . "/pages/InscriptionECF.php?error=Veuillez remplir tous les champs.");
        exit();
    }

    // Vérifier si l'email existe déjà
    $checkEmail = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $checkEmail->execute([':email' => $formEmail]);
    if ($checkEmail->fetchColumn() > 0) {
        header("Location: " . BASE_URL . "/pages/InscriptionECF.php?error=Email déjà utilisé.");
        exit();
    }

    // Vérifier si le nom d'utilisateur existe déjà
    $checkUsername = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
    $checkUsername->execute([':username' => $formUsername]);
    if ($checkUsername->fetchColumn() > 0) {
        header("Location: " . BASE_URL . "/pages/InscriptionECF.php?error=Nom d'utilisateur déjà utilisé.");
        exit();
    }

    // Hash du mot de passe pour la sécurité
    $hashPassword = password_hash($formPassword, PASSWORD_DEFAULT);

    // Insertion dans la table users
    $stmt = $pdo->prepare("INSERT INTO users (email, username, password, role) VALUES (:email, :username, :password, :role)");
    $stmt->execute([
        ':email' => $formEmail,
        ':username' => $formUsername,
        ':password' => $hashPassword,
        ':role' => $role
    ]);

    // Récupération de l'ID inséré
    $userId = $pdo->lastInsertId('public.users_id_seq1');

    // Insertion dans la table utilisateurs
    $stmt2 = $pdo->prepare("INSERT INTO utilisateurs (user_id, statut, password, email, username, credits) VALUES (:user_id, 'utilisateur', :password, :email, :username, :credits)");

   $stmt2->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt2->bindValue(':password', $hashPassword);
$stmt2->bindValue(':email', $formEmail);
$stmt2->bindValue(':username', $formUsername);
$stmt2->bindValue(':credits', 20, PDO::PARAM_INT);

if (!$stmt2->execute()) {
    $errorInfo = $stmt2->errorInfo();
    die("Erreur lors de l'insertion dans utilisateurs : " . $errorInfo[2]);
}

    // Stockage des informations utilisateur dans la session
    $_SESSION['user'] = [
        'id' => $userId,
        'email' => $formEmail,
        'password' => $hashPassword,
        'role' => $role,
        'username' => $formUsername
    ];

    // Redirection vers la page de choix de statut
    header("Location: " . BASE_URL . "/pages/choixStatut.php");
    exit;

} catch (PDOException $e) {
    echo "Erreur de connexion ou d'exécution : " . $e->getMessage();
}
?>