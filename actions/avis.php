<?php
// Inclusion des fichiers d'authentification, configuration et sécurité
require_once __DIR__ . '/../includes/Config.php';
include __DIR__ . '/../includes/Csrf.php';
include __DIR__ . '/../includes/Auth.php';

// Vérification que la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
    die("Erreur CSRF : action non autorisée !");
}

try {
    // Connexion à la base de données avec gestion d'erreurs
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération des données du formulaire d'avis
    $userId        = $_SESSION['user']['user_id'] ?? null; 
    $trajetId      = (int)($_POST['id_trajet'] ?? 0);
    $chauffeurId   = (int)($_POST['id_chauffeur'] ?? 0);
    $note          = (int)($_POST['note'] ?? 0);
    $commentaire   = trim($_POST['commentaire'] ?? '');
    $chauffeurUser = trim($_POST['redirect_user'] ?? '');

    // Validation des données obligatoires
    if (!$userId || $trajetId < 1 || $chauffeurId < 1 || $note < 1 || $note > 5) {
        throw new Exception("Données invalides ou manquantes");
    }

    // Récupération du profil utilisateur courant
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$utilisateur) {
        throw new Exception("Profil utilisateur introuvable");
    }

    // Récupération du profil chauffeur
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE user_id = :chauffeur_user_id");
    $stmt->execute([':chauffeur_user_id' => $chauffeurId]);
    $chauffeurUtilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$chauffeurUtilisateur) {
        throw new Exception("Profil chauffeur introuvable");
    }

  // Insertion de l'avis
$stmt = $pdo->prepare("
    INSERT INTO avis (id_utilisateur, id_trajet, id_chauffeur, note, commentaire, statut)
    VALUES (:utilisateur_id, :trajet_id, :chauffeur_id, :note, :comment, 'invalide')
");
$stmt->execute([
    ':utilisateur_id' => $utilisateur['id'],
    ':trajet_id'      => $trajetId,
    ':chauffeur_id'   => $chauffeurUtilisateur['id'],
    ':note'           => $note,
    ':comment'        => $commentaire
]);

    // Mise à jour de la note moyenne du chauffeur
    $stmt = $pdo->prepare("
        UPDATE utilisateurs 
        SET note = (
            SELECT AVG(note) 
            FROM avis 
            WHERE id_chauffeur = :chauffeur_id 
            AND statut = 'valide'
        )
        WHERE id = :chauffeur_id
    ");
    $stmt->execute([':chauffeur_id' => $chauffeurUtilisateur['id']]);

    // Message de succès et redirection
    $_SESSION['success'] = "Avis enregistré avec succès";
    $redirectURL = BASE_URL. "/pages/PageAvis.php?trajet_id=$trajetId&user=" . urlencode($chauffeurUser);
    header("Location: $redirectURL");
    exit;

} catch (PDOException $e) {
    // Gestion des erreurs SQL
    $_SESSION['error'] = "Erreur SQL: " . $e->getMessage();
} catch (Exception $e) {
    // Gestion des autres erreurs
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
}

// Redirection en cas d'erreur avec conservation des paramètres
$redirectParams = [
    'trajet_id' => $_POST['id_trajet'] ?? '',
    'user'      => $_POST['redirect_user'] ?? ''
];
$redirectURL = BASE_URL. "/pages/PageAvis.php?" . http_build_query(array_filter($redirectParams));
header("Location: $redirectURL");
exit;