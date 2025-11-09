<?php
// Inclusion des fichiers nécessaires pour l'authentification, les notifications, les fonctions utilitaires, la sécurité et la protection CSRF
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/auth.php';   // Gestion de l'authentification utilisateur
include __DIR__ . '/../actions/notif.php'; 
include __DIR__ . '/../includes/function.php';          
include __DIR__ . '/../includes/headerProtection.php';  // Fonctions utilitaires
include __DIR__ . '/../includes/csrf.php';


// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user']['id'])) {
    header("Location:". BASE_URL ."/actions/connexion.php");
    exit;
}

// Initialisation des variables
$id = $_SESSION['user']['id'];
$erreur = '';
$succes = '';

// Récupération des informations actuelles de l'utilisateur depuis la base de données
$stmt = $pdo->prepare("SELECT username, email, user_id FROM utilisateurs WHERE id = :id");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$userId = $user['user_id'] ?? null;

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données du formulaire
    $nouveauNom = trim($_POST['username']);
    $nouvelEmail = trim($_POST['email']);
    $nouveauMdp = $_POST['password'];

    // Vérifie si l'email est déjà utilisé par un autre utilisateur
    $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = :email AND id != :id");
    $check->execute([':email' => $nouvelEmail, ':id' => $id]);

    if ($check->fetch()) {
        $erreur = "Cet email est déjà utilisé.";
    } else {
        // Préparation de la requête de mise à jour
        $query = "UPDATE utilisateurs SET username = :username, email = :email";
        $params = [':username' => $nouveauNom, ':email' => $nouvelEmail];

        // Ajout du mot de passe dans la requête si fourni
        if (!empty($nouveauMdp)) {
            $mdpHash = password_hash($nouveauMdp, PASSWORD_DEFAULT);
            $query .= ", password = :password";
            $params[':password'] = $mdpHash;
        }

        $query .= " WHERE id = :id";
        $params[':id'] = $id;

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        // Met à jour aussi la table users si user_id est défini
        if ($userId) {
            $userQuery = "UPDATE users SET username = :username, email = :email";
            $userParams = [':username' => $nouveauNom, ':email' => $nouvelEmail];

            if (!empty($nouveauMdp)) {
                $userQuery .= ", password = :password";
                $userParams[':password'] = $mdpHash;
            }

            $userQuery .= " WHERE id = :id";
            $userParams[':id'] = $userId;

            $stmtUser = $pdo->prepare($userQuery);
            $stmtUser->execute($userParams);
        }

        // Mise à jour des informations dans la session
        $_SESSION['user']['email'] = $nouvelEmail;
        $_SESSION['user']['username'] = $nouveauNom;

        // Redirection vers la page du compte
        header("Location: ". BASE_URL ."/actions/compte.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le véhicule</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modif.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="website icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title>modification profil</title>
</head>
<body>

<!-- Formulaire de modification du profil utilisateur -->
<form method="POST">
    <?= csrf_input() ?>
    <label>Nom d'utilisateur</label>
    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required><br>

    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required><br>

    <label>Nouveau mot de passe (laisser vide pour ne pas changer)</label>
    <input type="password" name="password"><br>

    <button type="submit">Mettre à jour</button>
</form>

</body>
</html>