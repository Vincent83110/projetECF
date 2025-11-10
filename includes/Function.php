<?php 

// Fonction pour formater l'heure au format "hhhmm"
function formatTime($timeString) {
    list($hour, $minute) = explode(':', $timeString);
    return $hour . 'h' . $minute;
}

// Fonction pour formater la date au format "jj/mm/aaaa"
function formatDate($dateString) {
    list($year, $month, $day) = explode('-', $dateString);
    return $day . '/' . $month . '/' . $year;
}

// Fonction pour extraire le nom de ville d'une adresse complète
function extraireVille($adresse) {
    // Recherche d'un code postal suivi du nom de ville
    if (preg_match('/\b\d{5}\s+([\wÀ-ÿ\- ]+)/u', $adresse, $matches)) {
        return trim($matches[1]);
    }
    return $adresse; // Retourne l'adresse complète si aucun match
}

// Fonction pour calculer la durée d'un trajet
function getDuree($dateDepart, $heureDepart, $dateArrivee, $heureArrivee) {
    // Création des objets DateTime complets
    $depart = new DateTime("$dateDepart $heureDepart");
    $arrivee = new DateTime("$dateArrivee $heureArrivee");

    // Calcul de la différence
    $interval = $depart->diff($arrivee);

    // Calcul total d'heures et minutes
    $jours = $interval->days; // nombre total de jours
    $heures = $interval->h + ($jours * 24);
    $minutes = $interval->i;

    // Formatage du texte de résultat
    if ($heures === 0 && $minutes === 0) {
        return "--"; // cas spécial : aucune différence
    }

    if ($minutes === 0) {
        return $heures . "h";
    } else {
        return sprintf("%dh%02d", $heures, $minutes);
    }
}

// Fonction pour formater une note (entier ou décimal)
function entierOuDecimal($note) {
    if ($note == intval($note)) {
        return intval($note); // pas de décimale si entier
    }
    // Supprime les zéros inutiles à la fin
    return rtrim(rtrim(number_format($note, 2, '.', ''), '0'), '.');
}