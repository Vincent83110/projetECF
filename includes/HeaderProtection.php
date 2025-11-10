<?php
// En-têtes de sécurité pour protéger contre diverses vulnérabilités web
header("X-Frame-Options: DENY"); // Empêche le clickjacking
header("X-Content-Type-Options: nosniff"); // Empêche le MIME-sniffing
header("Referrer-Policy: no-referrer"); // Limite les informations de referrer
header("Permissions-Policy: geolocation=()"); // Désactive la géolocalisation par défaut
?>