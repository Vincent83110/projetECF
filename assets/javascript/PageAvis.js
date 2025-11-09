// Gestion de la page des avis avec système d'étoiles et commentaires

// Initialisation au chargement de la page
window.addEventListener('load', () => {
    const avisContainers = document.querySelectorAll('.avis-container');

    avisContainers.forEach(container => {
      const commentaire = container.querySelector('.commentaire');
      const voirPlus = container.querySelector('.voir-plus');

      // Vérification après un délai pour s'assurer que le contenu est rendu
      setTimeout(() => {
        // Afficher "Voir plus" seulement si le contenu dépasse la hauteur visible
        if (commentaire.scrollHeight > commentaire.clientHeight) {
          voirPlus.classList.add('visible');
        }
      }, 100);

      // Gestion du clic sur "Voir plus/Voir moins"
      voirPlus.addEventListener('click', (e) => {
        e.preventDefault();
        // Basculer l'état étendu/réduit
        commentaire.classList.toggle('expanded');
        voirPlus.textContent = commentaire.classList.contains('expanded') ? 'Voir moins' : 'Voir plus';
      });
    });
  });

// Système de notation par étoiles
const etoiles = document.querySelectorAll('.etoile');

etoiles.forEach((etoile, index) => {
  etoile.addEventListener('click', () => {
    // Mettre à jour l'affichage des étoiles selon la sélection
    etoiles.forEach((e, i) => {
      e.src = (i <= index ? `${BASE_URL}/assets/images/starOn.svg` : `${BASE_URL}/assets/images/starOff.svg`)
    });
  });
});