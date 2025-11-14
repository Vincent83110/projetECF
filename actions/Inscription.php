<?php
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}

include __DIR__ . '/../includes/Csrf.php';

// Vérification que la méthode de requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Méthode non autorisée.";
    header("Location: " . BASE_URL . "/pages/InscriptionECF.php");
    exit();
}

// Vérification CSRF pour prévenir les attaques
if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
    $_SESSION['error'] = "Erreur CSRF : action non autorisée !";
    header("Location: " . BASE_URL . "/pages/InscriptionECF.php");
    exit();
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
        $_SESSION['error'] = "Email invalide.";
        header("Location: " . BASE_URL . "/pages/InscriptionECF.php");
        exit();
    }
    if (empty($formEmail) || empty($formPassword) || empty($formUsername)) {
        $_SESSION['error'] = "Veuillez remplir tous les champs.";
        header("Location: " . BASE_URL . "/pages/InscriptionECF.php");
        exit();
    }

    // Vérifier si l'email existe déjà
    $checkEmail = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $checkEmail->execute([':email' => $formEmail]);
    if ($checkEmail->fetchColumn() > 0) {
        $_SESSION['error'] = "Email déjà utilisé.";
        header("Location: " . BASE_URL . "/pages/InscriptionECF.php");
        exit();
    }

    // Vérifier si le nom d'utilisateur existe déjà
    $checkUsername = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
    $checkUsername->execute([':username' => $formUsername]);
    if ($checkUsername->fetchColumn() > 0) {
        $_SESSION['error'] = "Nom d'utilisateur déjà utilisé.";
        header("Location: " . BASE_URL . "/pages/InscriptionECF.php");
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
    $_SESSION['error'] = "Erreur lors de l'inscription : " . $errorInfo[2];
    header("Location: " . BASE_URL . "/pages/InscriptionECF.php");
    exit();
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
    header("Location: " . BASE_URL . "/pages/ChoixStatut.php");
    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de connexion ou d'exécution : " . $e->getMessage();
    header("Location: " . BASE_URL . "/pages/InscriptionECF.php");
    exit();
}
?>