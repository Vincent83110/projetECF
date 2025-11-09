<?php
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/csrf.php';
include __DIR__ . '/../includes/auth.php';

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
                // Crédite le chauffeur
                $stmt = $pdo->prepare("UPDATE infos_trajet SET statut_paiement_chauffeur = 'paye' WHERE id = :trajetId");
                $stmt->execute([':trajetId' => $trajetId]);
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
                // Créditer quand même le chauffeur (le trajet a eu lieu)
                $stmt = $pdo->prepare("UPDATE infos_trajet SET statut_paiement_chauffeur = 'paye' WHERE id = :trajetId");
                $stmt->execute([':trajetId' => $trajetId]);
            }
        }
    }

    // Retour sur la même page avec pagination
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    header("Location: " . BASE_URL . "/pages/AvisEmployésTotal.php?page=$page");
    exit;

} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
