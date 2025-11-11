<?php
// URL racine du projet
define('BASE_URL','/projetECF');

// --- PostgreSQL ---
$host = getenv('POSTGRES_HOST');
$dbname = getenv('POSTGRES_DB');
$usernamePgadmin = getenv('POSTGRES_USER');
$passwordPgadmin = getenv('POSTGRES_PASSWORD');

// --- MongoDB ---
$mongoUri = getenv('MONGO_URI');