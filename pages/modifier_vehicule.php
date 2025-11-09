<?php
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/headerProtection.php';
include __DIR__ . '/../includes/csrf.php';

$error_message = '';
$success_message = '';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération du véhicule
    if (!isset($_GET['id'])) {
        $_SESSION['message'] = "Aucun véhicule spécifié";
        header("Location: " . BASE_URL . "/actions/compte.php");
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM vehicules WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id' => $_GET['id'],
        ':user_id' => $_SESSION['user']['id']
    ]);
    $vehicule = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehicule) {
        $_SESSION['message'] = "Véhicule non trouvé";
        header("Location: " . BASE_URL . "/actions/compte.php");
        exit();
    }

    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // === MODIFIER ===
        if (isset($_POST['action']) && $_POST['action'] === 'modifier') {
            $data = [
                ':marque' => $_POST['marque'],
                ':modele' => $_POST['modele'],
                ':couleur' => $_POST['couleur'],
                ':plaque' => $_POST['plaque'],
                ':id' => $_POST['vehicule_id'],
                ':user_id' => $_SESSION['user']['id']
            ];

            $stmt = $pdo->prepare("
                UPDATE vehicules SET 
                    marque = :marque,
                    modele = :modele,
                    couleur = :couleur,
                    plaque_immatriculation = :plaque
                WHERE id = :id AND user_id = :user_id
            ");

            if ($stmt->execute($data)) {
                $_SESSION['message'] = "Véhicule modifié avec succès";
                header("Location: " . BASE_URL . "/actions/compte.php");
                exit();
            }
        }

        // === SUPPRIMER ===
        if (isset($_POST['action']) && $_POST['action'] === 'supprimer') {
            try {
                $pdo->beginTransaction();

                // 1️⃣ Vérifier s'il reste des trajets avec des réservations
                $stmt_trajets = $pdo->prepare("
                    SELECT t.id 
                    FROM infos_trajet t
                    LEFT JOIN reservation r ON t.id = r.trajet_id
                    WHERE t.id_vehicule = :id_vehicule
                    GROUP BY t.id
                    HAVING COUNT(r.id) > 0
                ");
                $stmt_trajets->execute([':id_vehicule' => $_POST['vehicule_id']]);
                $trajets_avec_resa = $stmt_trajets->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($trajets_avec_resa)) {
                    // Soft delete si au moins une réservation existe
                    $stmt_delete_vehicule = $pdo->prepare("
                        UPDATE vehicules 
                        SET deleted = TRUE, plaque_immatriculation = NULL
                        WHERE id = :id AND user_id = :user_id
                    ");
                    $stmt_delete_vehicule->execute([
                        ':id' => $_POST['vehicule_id'],
                        ':user_id' => $_SESSION['user']['id']
                    ]);
                    $success_message = "Le véhicule est lié à des trajets avec réservation, il a été marqué comme supprimé (soft delete).";
                } else {
                    // Supprimer les trajets terminés / null / en cours
                    $stmt_delete_trajets = $pdo->prepare("
                        DELETE FROM infos_trajet
                        WHERE id_vehicule = :id_vehicule
                          AND (statut IS NULL OR statut IN ('termine','en cours'))
                    ");
                    $stmt_delete_trajets->execute([':id_vehicule' => $_POST['vehicule_id']]);

                    // Supprimer le véhicule complètement
                    $stmt_delete_vehicule = $pdo->prepare("
                        DELETE FROM vehicules 
                        WHERE id = :id AND user_id = :user_id
                    ");
                    $stmt_delete_vehicule->execute([
                        ':id' => $_POST['vehicule_id'],
                        ':user_id' => $_SESSION['user']['id']
                    ]);
                    $success_message = "Véhicule et trajets associés supprimés avec succès.";
                }

                $pdo->commit();
                $_SESSION['message'] = $success_message;
                header("Location: " . BASE_URL . "/actions/compte.php");
                exit();

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error_message = "Erreur lors de la suppression : " . $e->getMessage();
            }
        }
    }

} catch (PDOException $e) {
    $error_message = "Erreur de base de données : " . $e->getMessage();
}

// === Vérification pour bouton supprimer ===
$can_delete = true;
try {
    $stmt_check = $pdo->prepare("
        SELECT COUNT(*) 
        FROM infos_trajet t
        LEFT JOIN reservation r ON t.id = r.trajet_id
        WHERE t.id_vehicule = :id_vehicule
          AND r.id IS NOT NULL
    ");
    $stmt_check->execute([':id_vehicule' => $vehicule['id']]);
    $trajets_non_supprimables = $stmt_check->fetchColumn();
    $can_delete = ($trajets_non_supprimables == 0);
} catch (PDOException $e) {
    $can_delete = false;
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
    <title> Modification vehicule </title>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <!-- Affichage des messages d'erreur -->
            <?php if (!empty($error_message)): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulaire de modification du véhicule -->
            <form method="POST">
                <?= csrf_input() ?>
                <input type="hidden" name="vehicule_id" value="<?= $vehicule['id'] ?? '' ?>">
                
                <label>Marque:</label>
                <input type="text" name="marque" value="<?= htmlspecialchars($vehicule['marque'] ?? '') ?>" required>
                
                <label>Modèle:</label>
                <input type="text" name="modele" value="<?= htmlspecialchars($vehicule['modele'] ?? '') ?>" required>
                
                <label>Couleur:</label>
                <input type="text" name="couleur" value="<?= htmlspecialchars($vehicule['couleur'] ?? '') ?>">
                
                <label>Plaque d'immatriculation:</label>
                <input type="text" name="plaque" value="<?= htmlspecialchars($vehicule['plaque_immatriculation'] ?? '') ?>" required>
                
                <div class="buttons">
                    <!-- Bouton pour enregistrer les modifications -->
                    <button type="submit" name="action" value="modifier" class="btn btn-primary">
                        Enregistrer
                    </button>
                    
                    <!-- Bouton pour supprimer le véhicule -->
                    <?php if ($can_delete): ?>
                        <button type="submit" name="action" value="supprimer" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce véhicule ? Les trajets associés seront dissociés (conservés mais sans véhicule attribué). Cette action est irréversible.')">
                            Supprimer
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-danger" disabled title="Impossible de supprimer - des trajets sont encore en cours">
                            Supprimer (non disponible)
                        </button>
                        <div class="info-box">
                            <i class="fas fa-info-circle"></i> Vous ne pouvez pas supprimer ce véhicule car il est associé à des trajets non terminés.
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</body>
</html>vrfrb