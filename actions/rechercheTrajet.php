<?php 
// Démarrage de la session et configuration du header pour une réponse JSON
session_start();
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté comme chauffeur uniquement (les chauffeurs ne peuvent pas rechercher de trajets)
if (isset($_SESSION['user']) && $_SESSION['user']['statut'] === 'chauffeur') {
    http_response_code(403); // Code HTTP 403 : interdit
    echo json_encode(['error' => 'Accès interdit : les chauffeurs ne peuvent pas rechercher de trajets.']);
    exit;
}

// Inclusion du fichier de configuration de la base de données
require_once __DIR__ . '/../includes/config.php';

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération des paramètres GET pour la recherche de trajets
    $departure = $_GET['departure'] ?? '';
    $destination = $_GET['destination'] ?? '';
    $date = $_GET['date'] ?? '';
    $passengers = $_GET['passengers'] ?? 1;

    // Vérification que les paramètres obligatoires sont fournis
    if (empty($departure) || empty($destination) || empty($date)) {
        echo json_encode([]);
        exit;
    }

    // Construction de la requête SQL pour rechercher les trajets correspondants aux critères
    $sql = "SELECT 
            t.id,
            t.numero_trajet, 
            t.adresse_depart, 
            t.adresse_arrive, 
            t.date_depart,
            t.date_arrive,
            t.heure_depart, 
            t.heure_arrive, 
            t.prix, 
            t.nombre_place,
            t.trajet_ecologique,
            u.note AS note_conducteur,
            u.username
        FROM infos_trajet t
        LEFT JOIN utilisateurs u ON t.id_utilisateur = u.id
        WHERE t.adresse_depart ILIKE :departure 
          AND t.adresse_arrive ILIKE :destination 
          AND t.date_depart = :date 
          AND t.nombre_place >= :passengers
          AND (t.statut IS NULL)
        ORDER BY t.date_depart, t.heure_depart";

    // Préparation et exécution de la requête avec les paramètres
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':departure' => "%$departure%",
        ':destination' => "%$destination%",
        ':date' => $date,
        ':passengers' => $passengers
    ]);

    // Récupération de tous les résultats
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si aucun trajet n'est trouvé, on cherche des suggestions
    if (count($results) === 0) {
        // Requête pour trouver des trajets similaires (mêmes adresses mais dates différentes)
        $suggestionSql = "SELECT 
                            t.id,
                            t.date_depart,
                            t.adresse_depart,
                            t.adresse_arrive
                          FROM infos_trajet t
                          WHERE t.adresse_depart ILIKE :departure
                            AND t.adresse_arrive ILIKE :destination
                            AND t.nombre_place >= :passengers
                            AND t.date_depart > :current_date
                            AND (t.statut IS NULL)
                          ORDER BY ABS(DATE_PART('day', t.date_depart::timestamp - :current_date::timestamp)) ASC
                          LIMIT 1";

        $suggestionStmt = $pdo->prepare($suggestionSql);
        $suggestionStmt->execute([
            ':departure' => "%$departure%",
            ':destination' => "%$destination%",
            ':passengers' => $passengers,
            ':current_date' => $date
        ]);

        $suggestion = $suggestionStmt->fetch(PDO::FETCH_ASSOC);

        // Retour des résultats avec une suggestion si disponible
        echo json_encode([
            "trajets" => [],
            "suggestion" => $suggestion ? [
                "id" => $suggestion["id"], 
                "date" => $suggestion["date_depart"],
                "depart" => $suggestion["adresse_depart"],
                "arrivee" => $suggestion["adresse_arrive"]
            ] : null
        ]);
        exit;
    }

    // Retour des trajets trouvés
    echo json_encode([
        "trajets" => $results,
        "suggestion" => null
    ]);

} catch (PDOException $e) {
    // Journalisation de l'erreur et retour d'une réponse d'erreur
    error_log("Erreur PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}