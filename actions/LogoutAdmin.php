<?php
require_once __DIR__ . '/../includes/Config.php';
// Démarrage de la session
session_start();
// Suppression de toutes les variables de session
session_unset();
// Destruction de la session
session_destroy();
// Redirection vers la page de connexion administrateur
header("Location: " . BASE_URL . "/pages/ConnexionAdmin.php");
exit;