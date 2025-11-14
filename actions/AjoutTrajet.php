<?php
// Inclusions des fichiers de configuration et de sécurité
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}

include __DIR__ . '/../includes/Csrf.php';

// Vérification qu'un utilisateur est connecté et a un ID
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $_SESSION['error'] = "Utilisateur non connecté.";
    header("Location: " . BASE_URL . "/pages/ConnexionUtilisateur.php");
    exit;
}

// Récupération de l'ID utilisateur
$userId = $_SESSION['user']['id'];

// Vérification que la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Méthode non autorisée.";
    header("Location: " . BASE_URL . "/pages/InfosTrajetChauffeur.php");
    exit;
}

// Utilisation de verifyCsrfToken() au lieu de csrf_check() 
// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
    $_SESSION['error'] = "Erreur CSRF : action non autorisée !";
    header("Location: " . BASE_URL . "/pages/InfosTrajetChauffeur.php");
    exit;
}

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fonction pour générer un numéro de trajet unique
    function generateNumeroTrajet(PDO $pdo): string {
        do {
            // Génération d'une lettre aléatoire A-Z
            $lettre = chr(random_int(65, 90));
            // Génération de chiffres aléatoires
            $chiffres = random_int(100000, 999999);
            $numero = $lettre . $chiffres;

            // Vérification que le numéro n'existe pas déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM infos_trajet WHERE numero_trajet = :numero_trajet");
            $stmt->execute([':numero_trajet' => $numero]);
        } while ($stmt->fetchColumn() > 0); // Regénère si le numéro existe

        return $numero;
    }

    // Récupération des crédits actuels de l'utilisateur
    $stmtCredits = $pdo->prepare("SELECT credits FROM utilisateurs WHERE id = :id");
    $stmtCredits->execute([':id' => $userId]);
    $currentCredits = $stmtCredits->fetchColumn();

    // Vérification que l'utilisateur possède au moins un véhicule
    $stmtVehicules = $pdo->prepare("SELECT COUNT(*) FROM vehicules WHERE user_id = :user_id");
    $stmtVehicules->execute([':user_id' => $userId]);
    $nbVehicules = $stmtVehicules->fetchColumn();

    // Récupération des trajets et préférences depuis le formulaire
    $trajets = $_POST['trajets'] ?? [];
    $preferencesGlobales = $_POST['preferences'] ?? [];

    // Calcul du coût total (2 crédits par trajet)
    $nbTrajets = count($trajets);
    $coutTotal = 2 * $nbTrajets;

    // Validation complète de tous les trajets AVANT déduction des crédits
    $errors = [];
    foreach ($trajets as $i => $trajet) {
        // Nettoyage des données
        $adresse_depart = trim($trajet['adresse_depart'] ?? '');
        $adresse_arrive = trim($trajet['adresse_arrive'] ?? '');
        $date_depart = trim($trajet['date_depart'] ?? '');
        $date_arrive = trim($trajet['date_arrive'] ?? '');
        $heure_depart = trim($trajet['heure_depart'] ?? '');
        $prix = trim($trajet['prix'] ?? '');
        $nombre_place = trim($trajet['nombre_place'] ?? '');

        // Validation des champs obligatoires
        if ($adresse_depart === '') $errors[] = "Trajet #" . ($i+1) . " : Adresse de départ obligatoire.";
        if ($adresse_arrive === '') $errors[] = "Trajet #" . ($i+1) . " : Adresse d'arrivée obligatoire.";
        if ($date_depart === '') $errors[] = "Trajet #" . ($i+1) . " : Date de départ obligatoire.";
        if ($heure_depart === '') $errors[] = "Trajet #" . ($i+1) . " : Heure de départ obligatoire.";
        if ($nombre_place === '' || !is_numeric($nombre_place) || (int)$nombre_place <= 0) $errors[] = "Trajet #" . ($i+1) . " : Nombre de places obligatoire et positif.";
        if ($prix === '' || !is_numeric($prix) || (float)$prix < 0) $errors[] = "Trajet #" . ($i+1) . " : Prix obligatoire et positif.";
        if ($date_arrive !== '' && $date_depart > $date_arrive) $errors[] = "Trajet #" . ($i+1) . " : La date d'arrivée doit être postérieure à la date de départ.";
    }

    // Si erreurs de validation, on arrête
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        header("Location: " . BASE_URL . "/pages/InfosTrajetChauffeur.php");
        exit;
    }

    // Vérification des crédits suffisants
    if ($currentCredits < $coutTotal) {
        $_SESSION['error'] = "Crédits insuffisants. Vous avez $currentCredits crédits mais besoin de $coutTotal crédits.";
        header("Location: " . BASE_URL . "/pages/InfosTrajetChauffeur.php");
        exit;
    }

    //Suppression de TOUTES les anciennes préférences de l'utilisateur
    $stmtDeleteAllPref = $pdo->prepare("DELETE FROM preferences WHERE user_id = :user_id");
    $stmtDeleteAllPref->execute([':user_id' => $userId]);

    // Déduction des crédits seulement après validation réussie
    $stmtUpdateCredits = $pdo->prepare("UPDATE utilisateurs SET credits = credits - :cout WHERE id = :id");
    $stmtUpdateCredits->execute([
        ':cout' => $coutTotal,
        ':id' => $userId
    ]);

    // Mise à jour des crédits dans la session
    $_SESSION['credits'] = $currentCredits - $coutTotal;

    // Préparation de la requête d'insertion des trajets
    $stmtInsert = $pdo->prepare("
        INSERT INTO infos_trajet (
            adresse_depart, adresse_arrive, date_depart, date_arrive, heure_depart, heure_arrive,
            prix, nombre_place, id_utilisateur, numero_trajet, trajet_ecologique, id_vehicule
        ) VALUES (
            :adresse_depart, :adresse_arrive, :date_depart, :date_arrive, :heure_depart, :heure_arrive,
            :prix, :nombre_place, :id_utilisateur, :numero_trajet, :trajet_ecologique, :id_vehicule
        )
        RETURNING id
    ");

    // Préparation de la requête d'insertion des préférences POUR CHAQUE TRAJET
    $stmtPref = $pdo->prepare("INSERT INTO preferences (texte, trajet_id, user_id) VALUES (:texte, :trajet_id, :user_id)");

    $lastTrajet = null;

    // Insertion de chaque trajet
    foreach ($trajets as $trajet) {
        // Nettoyage des données du trajet
        $adresse_depart = trim($trajet['adresse_depart'] ?? '');
        $adresse_arrive = trim($trajet['adresse_arrive'] ?? '');
        $date_depart = trim($trajet['date_depart'] ?? '');
        $date_arrive = trim($trajet['date_arrive'] ?? '');
        $heure_depart = trim($trajet['heure_depart'] ?? '');
        $heure_arrive = trim($trajet['heure_arrive'] ?? '');
        $prix = trim($trajet['prix'] ?? '');
        $nombre_place = trim($trajet['nombre_place'] ?? '');
        $id_vehicule = trim($trajet['id_vehicule'] ?? '');

        // Génération d'un numéro de trajet unique
        $numero_trajet = generateNumeroTrajet($pdo);

        // Vérification si le véhicule est électrique pour marquer le trajet comme écologique
        $trajetEcologique = 'Non';
        if ($id_vehicule !== '') {
            $stmtVehicule = $pdo->prepare("SELECT energie FROM vehicules WHERE id = :id");
            $stmtVehicule->execute([':id' => $id_vehicule]);
            $energie = $stmtVehicule->fetchColumn();

            if ($energie && strtolower($energie) === 'electrique') {
                $trajetEcologique = 'Oui';
            }
        }

        // Insertion du trajet dans la base de données
        $stmtInsert->execute([
            ':adresse_depart' => $adresse_depart,
            ':adresse_arrive' => $adresse_arrive,
            ':date_depart' => $date_depart,
            ':date_arrive' => $date_arrive ?: null, // null si vide
            ':heure_depart' => $heure_depart,
            ':heure_arrive' => $heure_arrive ?: null, // null si vide
            ':prix' => (float)$prix,
            ':nombre_place' => (int)$nombre_place,
            ':id_utilisateur' => $userId,
            ':numero_trajet' => $numero_trajet,
            ':trajet_ecologique' => $trajetEcologique,
            ':id_vehicule' => $id_vehicule !== '' ? (int)$id_vehicule : null,
        ]);

        // Récupération de l'ID du trajet inséré
        $trajetId = $stmtInsert->fetchColumn();

        // Enregistrement de l'utilisation des crédits
        $stmtCreditUse = $pdo->prepare("INSERT INTO credits_utilises (id_utilisateur, type_operation, quantite) VALUES (?, 'publication_trajet', -2)");
        $stmtCreditUse->execute([$userId]);

        // Insertion des préférences associées au trajet
        foreach ($preferencesGlobales as $pref) {
            $prefClean = trim($pref);
            if ($prefClean !== '') {
                $stmtPref->execute([
                    ':texte' => $prefClean,
                    ':trajet_id' => $trajetId,
                    ':user_id' => $userId
                ]);
            }
        }

        // Sauvegarde du dernier trajet pour redirection
        $lastTrajet = [
            'id' => $trajetId,
            'numero' => $numero_trajet,
            'adresse_depart' => $adresse_depart,
            'adresse_arrive' => $adresse_arrive,
            'date_depart' => $date_depart,
            'date_arrive' => $date_arrive ?: $date_depart,
            'heure_depart' => $heure_depart,
            'heure_arrive' => $heure_arrive,
            'places' => $nombre_place,
            'prix' => $prix,
            'ecolo' => $trajetEcologique === 'Oui',
            'chauffeur_nom' => $_SESSION['user']['prenom'] ?? 'Conducteur',
            'chauffeur_note' => 0,
        ];
    }

    // Stockage du dernier trajet dans la session pour affichage
    $_SESSION['dernier_trajet'] = $lastTrajet;
    $_SESSION['success'] = "Trajet(s) publié(s) avec succès ! Les anciennes préférences ont été supprimées.";

    // Redirection vers la page des trajets individuels
    header("Location: " . BASE_URL . "/pages/TrajetIndividuel.php");
    exit;

} catch (PDOException $e) {
    // Gestion des erreurs de base de données
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: " . BASE_URL . "/pages/InfosTrajetChauffeur.php");
    exit;
} catch (Exception $e) {
    // Gestion des autres erreurs
    $_SESSION['error'] = "Erreur : " . $e->getMessage();
    header("Location: " . BASE_URL . "/pages/InfosTrajetChauffeur.php");
    exit;
}