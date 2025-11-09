<?php
// Vérification si la session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Génération d'un token CSRF s'il n'existe pas
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Fonction pour générer un champ input caché avec le token CSRF
 * @return string HTML du champ input caché
 */
function csrf_input() {
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

/**
 * Fonction pour vérifier la validité du token CSRF
 * @param string $token Le token à vérifier
 * @return bool True si le token est valide, false sinon
 */
function csrf_check($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}