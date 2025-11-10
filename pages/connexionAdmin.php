<?php
require_once __DIR__ . '/../includes/Config.php';
include __DIR__ . '/../includes/Csrf.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Définition de l'encodage des caractères et des paramètres viewport -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin</title>
</head>
<body>
    <!-- Conteneur principal centré verticalement et horizontalement -->
    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh;">
        <!-- Formulaire de connexion qui envoie les données vers Connexion.php -->
        <form method="post" action="<?= BASE_URL ?>/actions/Connexion.php">
            <?= csrf_input() ?>
            <fieldset>
                <!-- Légende du formulaire -->
                <legend>Connexion Admin</legend>

                <!-- Champ pour l'email -->
                <label for="username">Email :</label><br>
                <input type="text" name="email" id="username" required><br><br>

                <!-- Champ pour le mot de passe -->
                <label for="password">Mot de passe :</label><br>
                <input type="password" name="password" id="password" required><br><br>

                <!-- Bouton de soumission du formulaire -->
                <button type="submit">Se connecter</button>
            </fieldset>
        </form>
    </div>
</body>
</html>