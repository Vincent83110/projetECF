<?php

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/auth.php';          // Gestion de l'authentification utilisateur
include __DIR__ . '/../includes/headerProtection.php';      // Fonctions utilitaires
include __DIR__ . '/../includes/csrf.php';

try {
    // --- Connexion PostgreSQL ---
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usernamePgadmin, $passwordPgadmin);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- Récupération des employés (optionnel ici) ---
    $stmt = $pdo->prepare("SELECT * FROM employe");
    $stmt->execute();
    $employe = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Étape 1 : Création des 7 derniers jours ---
    $revenus = [];
    $trajets = [];
    $labels = [];

    for ($i = 6; $i >= 0; $i--) {
        $date = new DateTime();
        $date->sub(new DateInterval("P{$i}D"));
        $formattedDate = $date->format('Y-m-d');
        $labels[] = $date->format('d/m/Y');
        $revenus[$formattedDate] = 0;
        $trajets[$formattedDate] = 0;
    }

    // --- Étape 2 : Récupération des données réelles ---
    $sql = "
        SELECT
            TO_CHAR(date_creation AT TIME ZONE 'UTC' AT TIME ZONE 'Europe/Paris', 'YYYY-MM-DD') AS jour,
            COUNT(*) * 2 AS revenus,
            COUNT(*) AS nombre_trajets
        FROM infos_trajet
        WHERE date_creation >= current_date - INTERVAL '6 days'
        GROUP BY jour
        ORDER BY jour;
    ";
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $row) {
        $jour = $row['jour'];
        if (isset($revenus[$jour])) {
            $revenus[$jour] = (float)$row['revenus'];
            $trajets[$jour] = (int)$row['nombre_trajets'];
        }
    }

    // --- Étape 3 : Conversion en tableaux pour Chart.js ---
    $revenusFinal = array_values($revenus);
    $trajetsFinal = array_values($trajets);

    // --- Encodage JSON pour JS ---
    $labelsJson = json_encode($labels);
    $revenusJson = json_encode($revenusFinal);
    $trajetsJson = json_encode($trajetsFinal);

    // --- Étape 4 : Totaux globaux ---
    $sqlTotals = "
        SELECT 
            COUNT(*) * 2 AS total_revenus, 
            COUNT(*) AS total_trajets 
        FROM credits_utilises;
    ";
    $stmtTotals = $pdo->query($sqlTotals);
    $totaux = $stmtTotals->fetch(PDO::FETCH_ASSOC);

    $totalRevenus = $totaux['total_revenus'] ?? 0;
    $totalTrajets = $totaux['total_trajets'] ?? 0;

} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données : " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/EspaceAdministrateur.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="website icon" type="png" href="<?= BASE_URL ?>/assets/images/icon.png">
    <title>Espace Administrateur</title>
</head>
<body>
    <header>
        <div class="space">
            <div class="accueil">
              <span class="logo">ECO RIDE</span>
              <a href="<?= BASE_URL ?>/pages/accueil.php" class="menu-principal">Accueil</a>
            </div>
            <div>
                <div class="nav">
                    <div class="ContainerTitleNavConnect" id="Nav">
                      <span class="titleNav">
                          <img src="<?= BASE_URL ?>/assets/images/flecheMenu.svg" id="ImageIcon" alt="" class="ImgFlecheMenu">
                          <img src="<?= BASE_URL ?>/assets/images/profil.svg" alt="pas d'image" class="userNav">
                      </span>
                    </div>
                    <div class="InsideNav" id="InsideNav">
                        <!-- Menu administrateur -->
                        <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                          <a href="#" class="linkNav">Admin</a>
                          <a href="<?= htmlspecialchars($lienCompte) ?>" class="linkNav">Compte Admin</a>
                          <a href="<?= BASE_URL ?>/actions/logoutAdmin.php" class="linkNav">Déconnexion</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>     
        
        <!-- Menu burger pour mobile -->
        <div id="main">
          <button class="openbtn" id="buttonAside"><img src="<?= BASE_URL ?>/assets/images/burger.svg" alt=""></button>
        </div>
        
        <!-- Sidebar administrateur -->
        <div class="sidebar" id="mySidebar">
          <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
          <a href="#" class="closebtn" id="closebtn">×</a>
          <a href="#">Admin</a>
          <a href="<?= BASE_URL ?>/pages/accueil.php">Accueil</a>
          <a href="<?= BASE_URL ?>/pages/contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/pages/MentionsLegales.php">Mentions Legales</a>
          <hr class="color">
          <a href="<?= htmlspecialchars($lienCompte) ?>">Compte Admin</a>
          <a href="<?= BASE_URL ?>/actions/logoutAdmin.php">Déconnexion</a>
          <?php endif; ?>
          
          <!-- Liens réseaux sociaux -->
          <div class="ReseauxRow">
            <a href="https://www.youtube.com/" class="gapReseauxPop" target="_blank"><i class="fab fa-youtube"></i></a>
            <a href="https://www.instagram.com/" class="gapReseauxPop" target="_blank"><i class="fab fa-instagram"></i></a>
            <a href="https://x.com/?lang=fr&mx=2" class="gapReseauxPop" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>
            <a href="https://fr.pinterest.com/" class="gapReseauxPop" target="_blank"><i class="fab fa-pinterest"></i></a>
          </div>
        </div>
    </header>
    
    <main id="pop">
        <!-- Titre principal -->
        <div class="row1">
            <span class="high-span">Espace Administrateur</span>
        </div>
        
        <!-- Barre de recherche -->
        <div class="backSearchBar">
            <form id="searchForm">
                <div class="search-bar">
                    <input class="pseudo" type="search" name="username" id="pseudo" placeholder="Pseudo chauffeur">
                    <input class="numberCovoit" type="search" name="numero_trajet" id="numberCovoit" placeholder="Numéro de covoiturage">
                    <button type="submit" aria-label="Rechercher" class="ButtonRounded">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Colonnes principales -->
        <div class="colPrincipal">
            <!-- Colonne 1 : Formulaire nouveau employé -->
            <div class="col1">
                <form method="post" action="<?= BASE_URL ?>/actions/inscriptionEmploye.php" class="formNewEmployee">
                    <?= csrf_input() ?>
                    <div class="NewEmployee">
                        <span class="textTitle">Nouvel(le) employé(e)</span>
                        <hr class="hrAdmin">
                        <div class="spaceAdmin">
                            <!-- Champs du formulaire -->
                            <div class="align">
                                <label for="Name" class="LabelCol"> Prénom </label>
                                <input type="search" id="Name" name="prenom" class="inputAdmin">
                            </div>
                            <div class="align">
                                <label for="SurName" class="LabelCol"> Nom </label>
                                <input type="search" id="SurName" name="nom" class="inputAdmin">
                            </div>
                            <div class="align">
                                <label for="Email" class="LabelCol"> Email </label>
                                <input type="search" id="Email" name="email" class="inputAdmin">
                            </div>
                            <div class="align">
                                <label for="Password" class="LabelCol"> Mot de passe </label>
                                <input type="search" id="Password" name="password" class="inputAdmin" autocomplete="new-password">
                            </div>
                            <div class="align">
                                <label for="Fonction" class="LabelCol"> Fonction </label>
                                <input type="search" id="Fonction" name="fonction" class="inputAdmin">
                            </div>
                            <div class="align">
                                <label for="phone" class="LabelCol2"> Téléphone </label>
                                <input type="number" id="phone" name="telephone" class="inputAdmin2">
                            </div>
                            <div class="align">
                                <label for="hire" class="LabelCol2"> Date d'embauche </label>
                                <input type="date" id="hire" name="date_embauche" class="inputAdmin2">
                            </div>
                        </div>
                    </div>
                    <div>
                        <button class="inscriptionEmploye" type="submit" name="role" value="employe">Inscrire</button>
                    </div>
                </form>
            </div>
            
            <!-- Colonne 2 : Liste des employés -->
            <div class="col2">
                <div class="col2Inside">
                    <div class="ListEmployee">
                        <span class="textTitle2">Liste employé(e)s</span>
                        <hr class="hrAdmin2">
                        <div class="employee-list">
                            <ul>
                                <?php if (!empty($employe)) : ?>
                                    <?php foreach ($employe as $emp) : ?>
                                        <li class="employee-item">
                                           <!-- Lien vers le profil de l'employé -->
                                           <a href="<?= BASE_URL ?>/pages/CompteEmploye.php?user_id=<?= urlencode($emp['user_id']) ?>" class="textDel">
                                               <?=$emp['prenom'] . " " .$emp['nom']. " - " .$emp['fonction']?>
                                           </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <li>Aucun employé enregistré.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section graphiques et statistiques -->
        <div class="backGraphics">
            <!-- Graphique des revenus -->
            <div class="chart-container">
                <canvas id="barCanvas"></canvas>
                <div class="dashboard-global">
                    <h2>Statistiques cumulées</h2>
                    <div class="stat-box">
                        <span><strong>Total des revenus :</strong> <?= $totalRevenus ?> crédits</span>
                    </div>
                </div>
            </div>
            
            <!-- Graphique des trajets -->
            <div class="chart-container">
                <canvas id="barCanvas2"></canvas>
                <div class="dashboard-global">
                    <h2>Statistiques cumulées</h2>
                    <div class="stat-box">
                        <span><strong>Total des trajets :</strong> <?= $totalTrajets ?> trajet<?= $totalTrajets > 1 ? "s" : "" ?></span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer> 
        <!-- Pied de page -->
        <div>
            <a href="<?= BASE_URL ?>/pages/MentionsLegales.php" class="mentions-legales">mentions légales</a>
            <a href="<?= BASE_URL ?>/pages/contact.php" class="mentions-legales"> contact </a>
        </div>
        <div>
            <a href="https://www.youtube.com/" class="gapReseaux" target="_blank"><i class="fab fa-youtube"></i></a>
            <a href="https://www.instagram.com/" class="gapReseaux" target="_blank"><i class="fab fa-instagram"></i></a>
            <a href="https://x.com/?lang=fr&mx=2" class="gapReseaux" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>
            <a href="https://fr.pinterest.com/" class="gapReseaux" target="_blank"><i class="fab fa-pinterest"></i></a>
        </div>
    </footer>

    <!-- Scripts JavaScript -->
    <script>
        const BASE_URL = "<?= BASE_URL ?>";
        window.GRAPH_DATA = {
        labels: <?= $labelsJson ?>,
        revenus: <?= $revenusJson ?>,
        trajets: <?= $trajetsJson ?>,
        BASE_URL: "<?= BASE_URL ?>"
    };
    </script>
    <script src="<?= BASE_URL ?>/assets/javascript/menu.js"></script>
    
    
    <!-- Scripts pour les graphiques Chart.js -->
    <script>
        // Données pour les graphiques
        const labels = <?= $labelsJson ?>;
        const revenus = <?= $revenusJson ?>;
        const trajets = <?= $trajetsJson ?>;

        // Graphique 1 : Revenus
        const ctx = document.getElementById('barCanvas').getContext('2d');
        const barChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Transactions',
                    data: revenus,
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true, position: 'top' },
                    title: { display: true, text: 'Revenus des 7 derniers jours' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 50 }
                    }
                }
            }
        });

        // Graphique 2 : Trajets
        const ctx2 = document.getElementById('barCanvas2').getContext('2d');
        const barChart2 = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Nombre de trajets',
                    data: trajets,
                    backgroundColor: 'rgba(238, 38, 38, 0.5)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true, position: 'top' },
                    title: { display: true, text: 'Trajets des 7 derniers jours' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 10 }
                    }
                }
            }
        });

        // Gestion de la recherche
        document.getElementById('searchForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const pseudo = document.getElementById('pseudo').value.trim();
            const numero = document.getElementById('numberCovoit').value.trim();

            if (pseudo && numero) {
                // Recherche par numéro de trajet
                window.location.href = `${BASE_URL}/pages/PageCovoiturageIndividuel.php?numero_trajet=${encodeURIComponent(numero)}`;
            } else if (numero) {
                // Recherche par numéro de trajet seulement
                window.location.href = `${BASE_URL}/pages/PageCovoiturageIndividuel.php?numero_trajet=${encodeURIComponent(numero)}`;
            } else if (pseudo) {
                // Recherche par pseudo - vérification du statut
                console.log("Requête envoyée avec pseudo :", pseudo);

                fetch(`${BASE_URL}/actions/getStatut.php?username=${encodeURIComponent(pseudo)}`)
                    .then(res => res.json())
                    .then(data => {
                        console.log("Réponse reçue :", data);

                        if (!data.success) {
                            alert("Utilisateur introuvable.");
                            return;
                        }

                        const statut = data.statut;
                        console.log("Statut détecté :", statut);
                        let page = '';

                        // Redirection selon le statut de l'utilisateur
                        switch (statut) {
                            case 'chauffeur':
                                page = 'CompteUtilisateurChauffeur.php';
                                break;
                            case 'passager_chauffeur':
                                page = 'CompteUtilisateurPassagerChauffeur.php';
                                break;
                            case 'passager':
                                page = 'CompteUtilisateurPassager.php';
                                break;
                            default:
                                alert("Statut inconnu.");
                                return;
                        }

                        const url = `${BASE_URL}/pages/${page}?pseudo=${encodeURIComponent(pseudo)}`;
                        console.log("Redirection vers :", url);
                        window.location.href = url;
                    })
                    .catch(error => {
                        console.error("Erreur fetch :", error);
                        alert("Erreur lors de la vérification du pseudo.");
                    });

            } else {
                alert("Veuillez entrer au moins un champ.");
            }
        });
    </script>
</body>
</html>