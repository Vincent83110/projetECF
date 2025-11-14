<?php
// Inclusion des fichiers de configuration et de sécurité
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}

include __DIR__ . '/../includes/Csrf.php';

// Vérification que la méthode de requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Méthode non autorisée.";
    header("Location: " . BASE_URL . "/pages/InfosChauffeur.php");
    exit;
}

// Vérification du token CSRF pour prévenir les attaques
if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
    $_SESSION['error'] = "Erreur CSRF : action non autorisée !";
    header("Location: " . BASE_URL . "/pages/InfosChauffeur.php");
    exit;
}

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Transformation des entrées en tableaux pour gérer plusieurs véhicules
    $plaques = (array) ($_POST['plaque_immatriculation'] ?? []);
    $dates = (array) ($_POST['date_immatriculation'] ?? []);
    $marques = (array) ($_POST['marque'] ?? []);
    $modeles = (array) ($_POST['modele'] ?? []);
    $couleurs = (array) ($_POST['couleur'] ?? []);
    $capacites = (array) ($_POST['capacite'] ?? []);
    $energies = (array) ($_POST['energie'] ?? []);

    // Récupération des données utilisateur depuis la session
    $user = $_SESSION['user'] ?? null;
    $userId = $user['id'] ?? null;

    // Vérification que l'utilisateur est connecté
    if (!$userId) {
        $_SESSION['error'] = "Utilisateur non connecté.";
        header("Location:" .BASE_URL. "/pages/InfosChauffeur.php");
        exit;
    }

    // Détermination du nombre de véhicules à insérer
    $nbVehicules = count($plaques);

    // Boucle sur chaque véhicule à ajouter
    for ($i = 0; $i < $nbVehicules; $i++) {
        // Nettoyage et formatage de la plaque d'immatriculation
        $plaque = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $plaques[$i] ?? ''));

        // Formatage de la plaque au format XX-123-XX
        if (preg_match('/^([A-Z]{2})([0-9]{3})([A-Z]{2})$/', $plaque, $matches)) {
            $plaque = "{$matches[1]}-{$matches[2]}-{$matches[3]}";
        } else {
            $_SESSION['error'] = "Format de plaque d'immatriculation invalide.";
            header("Location: " .BASE_URL. "/pages/InfosChauffeur.php");
            exit;
        }

        // Récupération des autres données du véhicule
        $date = $dates[$i] ?? null;
        $marque = $marques[$i] ?? null;
        $modele = $modeles[$i] ?? null;
        $couleur = $couleurs[$i] ?? null;
        $capacite = $capacites[$i] ?? null;

        // Vérification que tous les champs obligatoires sont remplis
        if (!$plaque || !$date || !$marque || !$modele || !$couleur || !$capacite ) {
            $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
            header("Location: " .BASE_URL. "/pages/InfosChauffeur.php");
            exit;
        }

        // Détermination du type d'énergie et si le véhicule est écologique
        $energie = strtolower(trim($energies[$i] ?? 'essence')); // Valeur par défaut : essence
        $trajetEcologique = ($energie === 'electrique' || $energie === 'hybride') ? 'Oui' : 'Non';

        // Préparation et exécution de la requête d'insertion
        $stmt = $pdo->prepare("INSERT INTO vehicules (user_id, plaque_immatriculation, date_immatriculation, marque, modele, couleur, capacite, energie) 
                               VALUES (:user_id, :plaque, :date, :marque, :modele, :couleur, :capacite, :energie)");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':plaque', $plaque);
        $stmt->bindValue(':date', $date);
        $stmt->bindValue(':marque', $marque);
        $stmt->bindValue(':modele', $modele);
        $stmt->bindValue(':couleur', $couleur);
        $stmt->bindValue(':capacite', $capacite, PDO::PARAM_INT);
        $stmt->bindValue(':energie', $energie);
        $stmt->execute();
    }

    // Redirection vers la page du compte après ajout réussi
    header("Location: " .BASE_URL. "/actions/Compte.php");
    exit;

} catch (PDOException $e) {
    // Gestion des erreurs de base de données
    $_SESSION['error'] = "Erreur lors de l'ajout du véhicule : " . $e->getMessage();
    header("Location: " .BASE_URL. "/pages/InfosChauffeur.php");
    exit;
}
?>