<?php
require_once __DIR__ . '/../includes/Config.php';
include __DIR__ . '/../includes/Csrf.php';
include __DIR__ . '/../includes/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
    die("Erreur CSRF : action non autorisée !");
}

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_POST['id_avis'], $_POST['action'])) {
        $id = (int)$_POST['id_avis'];
        $action = $_POST['action'];

        if ($action === 'valider') {
            // Valider l'avis
            $stmt = $pdo->prepare("UPDATE avis SET statut = 'valide' WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // Récupérer l'id du trajet
            $stmtTrajet = $pdo->prepare("SELECT id_trajet FROM avis WHERE id = :id");
            $stmtTrajet->execute([':id' => $id]);
            $trajetId = $stmtTrajet->fetchColumn();

            if ($trajetId) {
                // Récupérer le prix et le nombre de passagers
$stmtTrajet = $pdo->prepare("
    SELECT t.prix, t.id_utilisateur, COALESCE(SUM(r.nombre_places),0) AS nb_places
    FROM infos_trajet t
    LEFT JOIN reservation r ON r.trajet_id = t.id
    WHERE t.id = :trajetId
    GROUP BY t.id, t.prix
");
$stmtTrajet->execute([':trajetId' => $trajetId]);
$trajetData = $stmtTrajet->fetch(PDO::FETCH_ASSOC);

if ($trajetData) {
    $totalCredits = $trajetData['prix'] * $trajetData['nb_places'];

    // Créditer le chauffeur
    $stmtUpdate = $pdo->prepare("UPDATE utilisateurs SET credits = credits + :credits WHERE id = :chauffeurId");
    $stmtUpdate->execute([
        ':credits' => $totalCredits,
        ':chauffeurId' => $trajetData['id_utilisateur']
    ]);

    // Mettre à jour le statut
    $stmtUpdateStatut = $pdo->prepare("UPDATE infos_trajet SET statut_paiement_chauffeur = 'paye' WHERE id = :trajetId");
    $stmtUpdateStatut->execute([':trajetId' => $trajetId]);
}

            }

        } elseif ($action === 'supprimer') {
            // Récupérer l'id du trajet avant suppression
            $stmtTrajet = $pdo->prepare("SELECT id_trajet FROM avis WHERE id = :id");
            $stmtTrajet->execute([':id' => $id]);
            $trajetId = $stmtTrajet->fetchColumn();

            // Supprimer l'avis
            $stmt = $pdo->prepare("DELETE FROM avis WHERE id = :id");
            $stmt->execute([':id' => $id]);

            if ($trajetId) {
                // Récupérer le prix et le nombre de passagers
$stmtTrajet = $pdo->prepare("
    SELECT t.prix, t.id_utilisateur, COALESCE(SUM(r.nombre_places),0) AS nb_places
    FROM infos_trajet t
    LEFT JOIN reservation r ON r.trajet_id = t.id
    WHERE t.id = :trajetId
    GROUP BY t.id, t.prix
");
$stmtTrajet->execute([':trajetId' => $trajetId]);
$trajetData = $stmtTrajet->fetch(PDO::FETCH_ASSOC);

if ($trajetData) {
    $totalCredits = $trajetData['prix'] * $trajetData['nb_places'];

    // Créditer le chauffeur
    $stmtUpdate = $pdo->prepare("UPDATE utilisateurs SET credits = credits + :credits WHERE id = :chauffeurId");
    $stmtUpdate->execute([
        ':credits' => $totalCredits,
        ':chauffeurId' => $trajetData['id_utilisateur']
    ]);

    // Mettre à jour le statut
    $stmtUpdateStatut = $pdo->prepare("UPDATE infos_trajet SET statut_paiement_chauffeur = 'paye' WHERE id = :trajetId");
    $stmtUpdateStatut->execute([':trajetId' => $trajetId]);
}

            }
        }
    }

    // Retour sur la même page avec pagination
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    header("Location: " . BASE_URL . "/pages/AvisEmployesTotal.php?page=$page");
    exit;

} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
