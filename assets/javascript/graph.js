document.addEventListener("DOMContentLoaded", () => {
    const { labels, revenus, trajets, BASE_URL } = window.GRAPH_DATA;

    // === Graphique 1 : Revenus ===
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

    // === Graphique 2 : Trajets ===
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

    // === Gestion de la recherche ===
    const form = document.getElementById('searchForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const pseudo = document.getElementById('pseudo').value.trim();
            const numero = document.getElementById('numberCovoit').value.trim();

            if (pseudo && numero) {
                window.location.href = `${BASE_URL}/pages/PageCovoiturageIndividuel.php?numero_trajet=${encodeURIComponent(numero)}`;
            } else if (numero) {
                window.location.href = `${BASE_URL}/pages/PageCovoiturageIndividuel.php?numero_trajet=${encodeURIComponent(numero)}`;
            } else if (pseudo) {
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
    }
});
