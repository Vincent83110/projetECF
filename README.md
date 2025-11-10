# Description du projet 
ECO RIDE est un site web de covoiturages tournée vers l'écologie, le site permet de publier des trajets en mettant en avant les trajets écologiques pour les voitures électriques 
# Fonctionnalités principales
- Inscription et connexion utilisateur sécurisée (PHP + PDO)
- Recherche de trajets avec API d’auto-complétion
- Création et gestion de trajets / historique (ajout, modification, suppression, lancement, confirmation)
- Réservation de trajets par les passagers et passagers-chauffeurs sur la page détaillée du trajet
- Interface responsive (media queries)
- Tableau de bord utilisateur :  
  - modification / suppression du compte personnel  
  - lien vers la page d’avis  
  - gestion et modification des véhicules  
- Tableau de bord employé(e) :  
  - gestion des comptes personnels  
  - validation des avis utilisateurs  
  - recherche d’utilisateurs  
- Tableau de bord administrateur :  
  - suppression de comptes (utilisateur / employé)  
  - graphiques statistiques (crédits, trajets)  
  - recherche d’utilisateurs  
- Page d’avis : affichage et envoi des avis sur les trajets  
- Gestion des messages et notifications  
# Technologies utilisées 
- backend : PHP8 (PDO) 
- frontend : HTML5, Javascript, css3
- Base de données : postgreSQL (pgAdmin)
- Serveur local : XAMPP
- Gestion de projet : Trello
- Outils : Visual studio code, git, GitHub
# Installation en local

## Prérequis
- XAMPP: https://www.apachefriends.org/fr/index.html (Apache + PHP)
- PostgreSQL + pgAdmin https://www.postgresql.org/download/
- Git: https://git-scm.com/

## Cloner le projet
Ouvrir un terminal, puis exécuter :
```bash
git clone https://github.com/Vincent83110/projetECF.git
```
Déplacer ensuite le dossier cloné dans le répertoire de XAMPP :
C:\xampp\htdocs\projetECF et taper dans l'url d'un onglet localhost/htdocs/projetECF/AccueilECF.php
