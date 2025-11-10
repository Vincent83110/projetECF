<?php 
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

// 🚫 Interdire les chauffeurs
if (isset($_SESSION['user']['statut']) && $_SESSION['user']['statut'] === 'chauffeur') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès interdit : les chauffeurs ne peuvent pas rechercher de trajets.']);
    exit;
}

// ✅ Vérifier que l'utilisateur a un rôle autorisé
if (
    !isset($_SESSION['user']) ||
    !in_array($_SESSION['user']['role'], ['employe', 'admin', 'utilisateur'], true)
) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

try {
    // Connexion PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération des paramètres GET
    $departure = trim($_GET['departure'] ?? '');
    $destination = trim($_GET['destination'] ?? '');
    $date = $_GET['date'] ?? '';
    $passengers = (int)($_GET['passengers'] ?? 1);

    if (empty($departure) || empty($destination) || empty($date)) {
        echo json_encode(['trajets' => [], 'suggestion' => null]);
        exit;
    }

    // Requête principale
    $sql = "
        SELECT 
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
        ORDER BY t.date_depart, t.heure_depart
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':departure' => "%$departure%",
        ':destination' => "%$destination%",
        ':date' => $date,
        ':passengers' => $passengers
    ]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Suggestion si aucun résultat
    if (empty($results)) {
        $suggestionSql = "
            SELECT t.id, t.date_depart, t.adresse_depart, t.adresse_arrive
            FROM infos_trajet t
            WHERE t.adresse_depart ILIKE :departure
              AND t.adresse_arrive ILIKE :destination
              AND t.nombre_place >= :passengers
              AND t.date_depart > :current_date
              AND (t.statut IS NULL)
            ORDER BY ABS(DATE_PART('day', t.date_depart::timestamp - :current_date::timestamp)) ASC
            LIMIT 1
        ";

        $suggestionStmt = $pdo->prepare($suggestionSql);
        $suggestionStmt->execute([
            ':departure' => "%$departure%",
            ':destination' => "%$destination%",
            ':passengers' => $passengers,
            ':current_date' => $date
        ]);

        $suggestion = $suggestionStmt->fetch(PDO::FETCH_ASSOC);

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

    echo json_encode([
        "trajets" => $results,
        "suggestion" => null
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur : ' . $e->getMessage()]);
}
