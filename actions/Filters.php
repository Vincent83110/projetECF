<?php
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}


// Définition du header pour une réponse JSON
header('Content-Type: application/json; charset=utf-8');
try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération et validation des paramètres de filtrage depuis l'URL
    $departure   = $_GET['departure']   ?? null;
    $destination = $_GET['destination'] ?? null;
    $date        = $_GET['date']        ?? null;
    $passengers  = isset($_GET['passengers']) ? (int)$_GET['passengers'] : 1;
    $eco         = $_GET['eco']         ?? null;
    $min_time    = $_GET['min_time']    ?? null;
    $max_time    = $_GET['max_time']    ?? null;
    $min_price   = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
    $max_price   = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
    $min_note = isset($_GET['note']) ? (float)$_GET['note'] : null;

    // Construction de la requête SQL de base pour récupérer les trajets
    $sql = "
        SELECT 
            t.id, t.numero_trajet, t.adresse_depart, t.adresse_arrive, 
            t.date_depart, t.date_arrive, t.heure_depart, t.heure_arrive, 
            t.prix, t.nombre_place, t.trajet_ecologique, u.username,
            u.note AS note_conducteur
        FROM infos_trajet t
        LEFT JOIN utilisateurs u ON t.id_utilisateur = u.id
        WHERE (t.statut IS NULL)
    ";

    $params = [];

    // Ajout des conditions de filtrage dynamiques
    if ($departure)   { $sql .= " AND t.adresse_depart ILIKE :departure";   $params[':departure']   = "%$departure%"; }
    if ($destination) { $sql .= " AND t.adresse_arrive ILIKE :destination"; $params[':destination'] = "%$destination%"; }
    if ($date)        { $sql .= " AND t.date_depart = :date";                $params[':date']        = $date; }
    if ($passengers)  { $sql .= " AND t.nombre_place >= :passengers";        $params[':passengers']  = $passengers; }
    if ($eco === 'Oui') $sql .= " AND t.trajet_ecologique = 'Oui'";

    // Filtre sur la durée du trajet
    if ($min_time || $max_time) {
        $sql .= " AND ((EXTRACT(EPOCH FROM (t.heure_arrive - t.heure_depart + INTERVAL '24 hours')) / 60)::int % 1440)";
        if ($min_time && $max_time) {
            list($hMin,$mMin)=explode(':',$min_time); $minDuree=$hMin*60+$mMin;
            list($hMax,$mMax)=explode(':',$max_time); $maxDuree=$hMax*60+$mMax;
            $sql.=" BETWEEN :min_duree AND :max_duree"; $params[':min_duree']=$minDuree; $params[':max_duree']=$maxDuree;
        } elseif ($min_time) {
            list($hMin,$mMin)=explode(':',$min_time); $minDuree=$hMin*60+$mMin;
            $sql.=" >= :min_duree"; $params[':min_duree']=$minDuree;
        } elseif ($max_time) {
            list($hMax,$mMax)=explode(':',$max_time); $maxDuree=$hMax*60+$mMax;
            $sql.=" <= :max_duree"; $params[':max_duree']=$maxDuree;
        }
    }

    // Filtres sur le prix et la note
    if ($min_price!==null){ $sql.=" AND t.prix >= :min_price"; $params[':min_price']=$min_price; }
    if ($max_price!==null){ $sql.=" AND t.prix <= :max_price"; $params[':max_price']=$max_price; }
    if ($min_note!==null){ $sql.=" AND u.note >= :note"; $params[':note']=$min_note; }

    // Tri des résultats par date et heure de départ
    $sql .= " ORDER BY t.date_depart ASC, t.heure_depart ASC";

    // Préparation et exécution de la requête
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retour des résultats au format JSON
    echo json_encode([
        "trajets" => $results,
        "suggestion" => null
    ]);

} catch (PDOException $e) {
    // Gestion des erreurs avec journalisation
    error_log("Erreur PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "error" => "Erreur de base de données",
        "details" => $e->getMessage()
    ]);
}