<?php
// Inclusion des fichiers nécessaires pour l'authentification, les notifications, les fonctions utilitaires et la protection CSRF
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/auth.php';   // Gestion de l'authentification utilisateur
include __DIR__ . '/../actions/notif.php'; 
include __DIR__ . '/../includes/function.php';          
include __DIR__ . '/../includes/csrf.php';

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user']['id'])) {
    header("Location: ". BASE_URL ."/actions/connexion.php");
    exit;
}

// Initialisation des variables
$id = $_SESSION['user']['id'];
$erreur = '';
$succes = '';

// Récupération des informations actuelles de l'employé depuis la base de données
$stmt = $pdo->prepare("SELECT prenom, nom, telephone, email, user_id FROM employe WHERE id = :id");
$stmt->execute([':id' => $id]);
$employe = $stmt->fetch(PDO::FETCH_ASSOC); // Correction: $employe au lieu de $user
$userId = $employe['user_id'] ?? null;

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données du formulaire
    $nouveauPrenom = trim($_POST['prenom']);
    $nouveauNom = trim($_POST['nom']); // Ajout de ce champ manquant
    $nouvelEmail = trim($_POST['email']);
    $nouveauTelephone = trim($_POST['telephone']);
    $nouveauMdp = $_POST['password'];

    // Vérifie si l'email est déjà utilisé par un autre employé
    $check = $pdo->prepare("SELECT id FROM employe WHERE email = :email AND id != :id");
    $check->execute([':email' => $nouvelEmail, ':id' => $id]);

    if ($check->fetch()) {
        $erreur = "Cet email est déjà utilisé.";
    } else {
        try {
            // Début de la transaction pour assurer l'intégrité des données
            $pdo->beginTransaction();

            // Mise à jour de la table employe
            $query = "UPDATE employe SET prenom = :prenom, nom = :nom, email = :email, telephone = :telephone";
            $params = [
                ':prenom' => $nouveauPrenom,
                ':nom' => $nouveauNom,
                ':email' => $nouvelEmail,
                ':telephone' => $nouveauTelephone,
                ':id' => $id
            ];

            // Ajout du mot de passe dans la requête si fourni
            if (!empty($nouveauMdp)) {
                $mdpHash = password_hash($nouveauMdp, PASSWORD_DEFAULT);
                $query .= ", password = :password";
                $params[':password'] = $mdpHash;
            }

            $query .= " WHERE id = :id";

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);

            // Mise à jour de la table users si user_id est défini
            if ($userId) {
                $userQuery = "UPDATE users SET email = :email";
                $userParams = [':email' => $nouvelEmail, ':id' => $userId];

                if (!empty($nouveauMdp)) {
                    $userQuery .= ", password = :password";
                    $userParams[':password'] = $mdpHash;
                }

                $userQuery .= " WHERE id = :id";

                $stmtUser = $pdo->prepare($userQuery);
                $stmtUser->execute($userParams);
            }

            // Mise à jour des informations dans la session
            $_SESSION['user'] = array_merge($_SESSION['user'], [
                'prenom' => $nouveauPrenom,
                'nom' => $nouveauNom,
                'email' => $nouvelEmail,
                'telephone' => $nouveauTelephone
            ]);

            // Validation de la transaction
            $pdo->commit();
            $_SESSION['succes'] = "Profil mis à jour avec succès";
            header("Location: ". BASE_URL ."/actions/compte.php");
            exit;

        } catch (Exception $e) {
            // En cas d'erreur, annulation de la transaction
            $pdo->rollBack();
            $erreur = "Une erreur est survenue : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
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
    <title> Modification profil employe </title>
</head>
<body>

<!-- Affichage des messages d'erreur -->
<?php if (!empty($erreur)): ?>
    <div class="erreur"><?= htmlspecialchars($erreur) ?></div>
<?php endif; ?>

<!-- Formulaire de modification du profil employé -->
<form method="POST">
    <?= csrf_input() ?>
    <label>Prénom</label>
    <input type="text" name="prenom" value="<?= htmlspecialchars($employe['prenom'] ?? '') ?>"><br>

    <label>Nom</label>
    <input type="text" name="nom" value="<?= htmlspecialchars($employe['nom'] ?? '') ?>"><br>

    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($employe['email'] ?? '') ?>"><br>

    <label>Téléphone</label>
    <input type="text" name="telephone" value="<?= htmlspecialchars($employe['telephone'] ?? '') ?>"><br>

    <label>Nouveau mot de passe (laisser vide pour ne pas changer)</label>
    <input type="password" name="password"><br>

    <button type="submit">Mettre à jour</button>
</form>

</body>
</html>