document.addEventListener("DOMContentLoaded", () => {
  // ===== Gestion des modales de confirmation =====
  const buttons = document.querySelectorAll(".openPopupBtn, .openPopupBtnAnnuler, .openPopupBtnLancer");

  buttons.forEach((btn) => {
    const form = btn.closest(".formAnnulation"); // le formulaire le plus proche
    const modal = form.querySelector(".modal-overlay");
    const cancelBtn = modal.querySelector(".cancelBtn");

    // Ouvrir la popup
    btn.addEventListener("click", () => {
      modal.style.display = "flex";
    });

    // Fermer la popup (bouton "Non")
    cancelBtn.addEventListener("click", () => {
      modal.style.display = "none";
    });

    // Fermer la popup en cliquant à l'extérieur
    modal.addEventListener("click", (e) => {
      if (e.target === modal) {
        modal.style.display = "none";
      }
    });
  });

  // ===== Gestion de la popup principale (overlay-popup) =====
  const openButton = document.querySelector(".openPopup"); // bouton principal (si tu en as un)
  const overlayPopup = document.querySelector(".overlay-popup");
  const closeButton = document.querySelector(".close-popup");

  if (openButton && overlayPopup && closeButton) {
    openButton.addEventListener("click", () => {
      overlayPopup.classList.add("active-popup");
    });

    closeButton.addEventListener("click", () => {
      overlayPopup.classList.remove("active-popup");
    });

    overlayPopup.addEventListener("click", (e) => {
      if (e.target === overlayPopup) {
        overlayPopup.classList.remove("active-popup");
      }
    });
  }
});


 let col1 = document.querySelector('.col1');
let col2 = document.querySelector('.col2');
let button1 = document.querySelector('.button1change');
let button2 = document.querySelector('.button2change');
let container = document.querySelector('.container');

button1.addEventListener('click', () => {
  col1.style.display = 'block';
  col2.style.display = 'none';
  button1.classList.add('active');
  button2.classList.remove('active');

});

button2.addEventListener('click', () => {
  col2.style.display = 'block';
  col1.style.display = 'none';
  button2.classList.add('active');
  button1.classList.remove('active');
  
});


document.addEventListener("DOMContentLoaded", function () {
  // Ouvrir la modale pour annuler
  document.querySelectorAll(".openPopupBtnAnnuler").forEach(button => {
    button.addEventListener("click", function () {
      const form = this.closest("form");
      const modal = form.querySelector(".modal-overlay");
      modal.style.display = "flex";
    });
  });

  // Ouvrir la modale pour lancer
  document.querySelectorAll(".openPopupBtnLancer").forEach(button => {
    button.addEventListener("click", function () {
      const form = this.closest("form");
      const modal = form.querySelector(".modal-overlay");
      modal.style.display = "flex";
    });
  });

  // Bouton annuler dans la modale (fermer modale)
  document.querySelectorAll(".cancelBtn").forEach(button => {
    button.addEventListener("click", function () {
      const modal = this.closest(".modal-overlay");
      modal.style.display = "none";
    });
  });
});

document.addEventListener("DOMContentLoaded", function () {
    // On récupère tous les boutons "Annuler"
    const openButtons = document.querySelectorAll('.openPopupBtn');

    openButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const form = btn.closest('form');
            const modal = form.querySelector('.modal-overlay');
            modal.style.display = 'flex';
        });
    });

    // On gère tous les boutons "Non" dans les popups
    const cancelButtons = document.querySelectorAll('.cancelBtn');
    cancelButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const modal = btn.closest('.modal-overlay');
            modal.style.display = 'none';
        });
    });
});
