<?php
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}

include __DIR__ . '/../includes/Csrf.php';

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header("Location:" . BASE_URL . "/actions/Connexion.php");
    exit;
}

// Vérification que la méthode de requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

// Vérification CSRF pour prévenir les attaques
if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
    die("Erreur CSRF : action non autorisée !");
}

// Traitement du formulaire de choix de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['statut'])) {
    $statutChoisi = $_POST['statut'];

    // Récupération des informations utilisateur depuis la session
    $email = $_SESSION['user']['email'];
    $password = $_SESSION['user']['password'];
    $username = $_SESSION['user']['username'];

    // Vérification que le mot de passe et le nom d'utilisateur sont définis
    if (empty($password) || empty($username)) {
        echo "Erreur : Le mot de passe ou le nom d'utilisateur n'est pas défini.";
        exit;
    }

    try {
        // Connexion à la base de données PostgreSQL
        $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Vérifier si utilisateur existe par email
        $stmtCheck = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = :email");
        $stmtCheck->execute([':email' => $email]);
        $user = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Utilisateur existe => UPDATE du statut
            $stmtUpdate = $pdo->prepare("UPDATE utilisateurs SET statut = :statut WHERE id = :id");
            $stmtUpdate->execute([
                ':statut' => $statutChoisi,
                ':id' => $user['id']
            ]);
            $userId = $user['id'];
        } else {
            // Pas d'utilisateur => INSERT complet
            $stmtInsert = $pdo->prepare("INSERT INTO utilisateurs (username, email, password, statut) VALUES (:username, :email, :password, :statut) RETURNING id");
            $stmtInsert->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $password,
                ':statut' => $statutChoisi
            ]);
            // Récupérer l'id nouvellement créé
            $userId = $stmtInsert->fetchColumn();
        }

        // Mise à jour de la session avec l'ID utilisateur et le statut
        $_SESSION['user']['id'] = $userId;
        $_SESSION['user']['statut'] = $statutChoisi;

    } catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
        exit;
    }

    // Redirection selon le statut choisi
    switch ($statutChoisi) {
        case 'chauffeur':
            header("Location: " . BASE_URL . "/pages/CompteUtilisateurChauffeur.php");
            break;
        case 'passager':
            header("Location: " . BASE_URL . "/pages/CompteUtilisateurPassager.php");
            break;
        case 'passager_chauffeur':
            header("Location: " . BASE_URL . "/pages/CompteUtilisateurPassagerChauffeur.php");
            break;
        default:
            header("Location: " . BASE_URL . "/pages/ChoixStatut.php");
            break;
    }
    exit;
} else {
    // Redirection vers la page de choix de statut si aucune donnée valide
    header("Location: " . BASE_URL . "/pages/ChoixStatut.php");
    exit;
}