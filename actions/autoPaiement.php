<?php
require_once __DIR__ . '/../includes/config.php';

try {
    // Connexion PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1️ Créditer automatiquement les chauffeurs après 24h
    $sql = "
    UPDATE utilisateurs u
    SET credits = credits + t.prix * (
        SELECT COALESCE(SUM(nombre_places), 0)
        FROM reservation r
        WHERE r.trajet_id = t.id
    )
    FROM infos_trajet t
    WHERE t.id_utilisateur = u.id
    AND t.date_confirmation_trajet < NOW() - INTERVAL '24 hours'
    AND t.statut_paiement_chauffeur = 'en_attente'
    AND NOT EXISTS (
    SELECT 1 FROM avis a WHERE a.id_trajet = t.id
    )
    ";

    $rowsCredited = $pdo->exec($sql);

    // 2️ Mettre à jour le statut des trajets concernés
    $sql2 = "
    UPDATE infos_trajet
    SET statut_paiement_chauffeur = 'paye'
    WHERE date_confirmation_trajet < NOW() - INTERVAL '24 hours'
    AND statut_paiement_chauffeur = 'en_attente'
    AND NOT EXISTS (
    SELECT 1 FROM avis a WHERE a.id_trajet = t.id
    )
    ";

    $rowsUpdated = $pdo->exec($sql2);

    echo "Script exécuté avec succès.\n";
    echo "Chauffeurs crédités : {$rowsCredited}\n";
    echo "Trajets mis à jour : {$rowsUpdated}\n";

} catch (PDOException $e) {
    echo "Erreur PDO : " . $e->getMessage();
    exit;
}
