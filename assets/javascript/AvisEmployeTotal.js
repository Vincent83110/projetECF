// Gestion de la recherche et affichage des avis employés

// Gestion de la soumission du formulaire de recherche
document.getElementById('searchForm').addEventListener('submit', function (e) {
  e.preventDefault();

  const pseudo = document.getElementById('pseudo').value.trim();
  const numero = document.getElementById('numberCovoit').value.trim();

  if (pseudo && numero) {
    // Redirection avec pseudo et numéro
    window.location.href = `${BASE_URL}/pages/PageCovoiturageIndividuelle.php?numero_trajet=${encodeURIComponent(numero)}`;
  } else if (numero) {
    // Redirection avec numéro seulement
    window.location.href = `${BASE_URL}/pages/PageCovoiturageIndividuelle.php?numero_trajet=${encodeURIComponent(numero)}`;
  } else if (pseudo) {
    console.log("Requête envoyée avec pseudo :", pseudo);

    // Vérification du statut utilisateur
    fetch(`${BASE_URL}/actions/GetStatut.php?username=${encodeURIComponent(pseudo)}`)
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

        // Redirection selon le statut
        switch (statut) {
          case 'chauffeur':
            page = `${BASE_URL}/pages/CompteUtilisateurChauffeur.php`;
            break;
          case 'passager_chauffeur':
            page = `${BASE_URL}/pages/CompteUtilisateurPassagerChauffeur.php`;
            break;
          case 'passager':
            page = `${BASE_URL}/pages/CompteUtilisateurPassager.php`;
            break;
          default:
            alert("Statut inconnu.");
            return;
        }

        const url = `${page}?pseudo=${encodeURIComponent(pseudo)}`;
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

// Gestion de l'affichage des commentaires (voir plus/voir moins)
window.addEventListener('load', () => {
  const avisContainers = document.querySelectorAll('.avis-container');

  avisContainers.forEach(container => {
    const commentaire = container.querySelector('.commentaire');
    const voirPlus = container.querySelector('.voir-plus');

    setTimeout(() => {
      if (commentaire.scrollHeight > commentaire.clientHeight) {
        voirPlus.classList.add('visible');
      }
    }, 100);

    voirPlus.addEventListener('click', (e) => {
      e.preventDefault();
      commentaire.classList.toggle('expanded');
      voirPlus.textContent = commentaire.classList.contains('expanded') ? 'Voir moins' : 'Voir plus';
    });
  });
});