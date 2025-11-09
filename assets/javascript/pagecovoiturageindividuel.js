// Gestion de la page individuelle de covoiturage avec popups

document.addEventListener("DOMContentLoaded", function() {
    // Gestion de la popup principale
    const openButton = document.querySelector(".buttonsubmit");
    const overlayPopup = document.querySelector(".overlay-popup");
    const closeButton = document.querySelector(".close-popup");
    
    if (openButton && overlayPopup && closeButton) {
        // Ouverture de la popup
        openButton.addEventListener("click", () => {
            overlayPopup.classList.add("active-popup");
        });

        // Fermeture via le bouton
        closeButton.addEventListener("click", () => {
            overlayPopup.classList.remove("active-popup");
        });

        // Fermeture en cliquant en dehors
        overlayPopup.addEventListener("click", (e) => {
            if (e.target === overlayPopup) overlayPopup.classList.remove("active-popup");
        });
    }

    // Gestion de la popup secondaire
    const openBtn = document.querySelector('.open-popup');
    if (openBtn) {
        openBtn.addEventListener('click', () => {
            document.getElementById('popupForm').style.display = 'block';
        });
    }

    const closeBtn = document.getElementById('close-popup');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            document.getElementById('popupForm').style.display = 'none';
        });
    }

    const cancelBtn = document.getElementById('cancel-popup');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            document.getElementById('popupForm').style.display = 'none';
        });
    }
});