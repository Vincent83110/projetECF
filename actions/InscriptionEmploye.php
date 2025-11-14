<?php
// Inclusion des fichiers de configuration et de protection CSRF
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}

include __DIR__ . '/../includes/Csrf.php';

// Vérification que la méthode de requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Méthode non autorisée.";
    header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php");
    exit();
}

// Vérification CSRF pour prévenir les attaques
if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
    $_SESSION['error'] = "Erreur CSRF : action non autorisée !";
    header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php");
    exit();
}

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération des données du formulaire
    $formPrenom = $_POST['prenom'] ?? null;
    $formNom = $_POST['nom'] ?? null;
    $formEmail = $_POST['email'] ?? null;
    $formPassword = $_POST['password'] ?? null;
    $formFonction = $_POST['fonction'] ?? null;
    $formTelephone = $_POST['telephone'] ?? null;
    $formDate_embauche = $_POST['date_embauche'] ?? null;
    $role = $_POST['role'] ?? 'employe';

    // Validation de l'email
    if (!filter_var($formEmail, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Email invalide.";
        header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php");
        exit();
    }

    // Vérification des champs obligatoires
    if (empty($formEmail) || empty($formPassword) || empty($formPrenom) || empty($formNom) || empty($formFonction) || empty($formTelephone) || empty($formDate_embauche)) {
        $_SESSION['error'] = "Veuillez remplir tous les champs.";
        header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php");
        exit();
    }

    // Vérification si l'email est déjà utilisé
    $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $check->bindValue(':email', $formEmail);
    $check->execute();

    if ($check->fetchColumn() > 0) {
        $_SESSION['error'] = "Email déjà utilisé.";
        header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php");
        exit();
    }

    // Hachage du mot de passe pour la sécurité
    $hashPassword = password_hash($formPassword, PASSWORD_DEFAULT);

    // Début de la transaction pour assurer l'intégrité des données
    $pdo->beginTransaction();

    // Insertion dans users avec récupération de l'id
    $stmt = $pdo->prepare("INSERT INTO users (email, password, role) 
                           VALUES (:email, :password, :role) 
                           RETURNING id");
    $stmt->execute([
        ':email' => $formEmail,
        ':password' => $hashPassword,
        ':role' => $role
    ]);
    $userId = $stmt->fetchColumn(); // Récupère l'id généré

    // Insertion dans la table employe avec l'user_id correspondant
    $stmtEmploye = $pdo->prepare("INSERT INTO employe (email, password, prenom, nom, fonction, telephone, date_embauche, user_id) 
                              VALUES (:email, :password, :prenom, :nom, :fonction, :telephone, :date_embauche, :user_id)");
    $stmtEmploye->execute([
        ':prenom' => $formPrenom,
        ':nom' => $formNom,
        ':fonction' => $formFonction,
        ':telephone' => $formTelephone,
        ':date_embauche' => $formDate_embauche,
        ':email' => $formEmail,
        ':password' => $hashPassword,
        ':user_id' => $userId
    ]);

    // Validation de la transaction
    $pdo->commit();
    $_SESSION['success'] = "Inscription réussie !";
    header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php");
    exit();

} catch (PDOException $e) {
    // En cas d'erreur, annulation de la transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = "Erreur de connexion à la base de données : " . $e->getMessage();
    header("Location: " . BASE_URL . "/pages/EspaceAdministrateur.php");
    exit();
}