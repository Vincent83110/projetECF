<?php
if (file_exists(__DIR__ . '/../includes/ConfigLocal.php')) {
    require_once __DIR__ . '/../includes/ConfigLocal.php'; // environnement local
} else {
    require_once __DIR__ . '/../includes/Config.php'; // pour Render
}

// Démarrage de la session
session_start();
// Suppression de toutes les variables de session
session_unset();
// Destruction de la session
session_destroy();
// Redirection vers la page d'accueil publique
header("Location: " . BASE_URL . "/AccueilECF.php");
exit;