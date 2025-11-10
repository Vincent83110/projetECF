// Gestion complète des trajets pour chauffeurs avec système dynamique

// Mise à jour de l'affichage des crédits en fonction du nombre de trajets
function updateCreditDisplay() {
  const trajetsDynamiques = document.querySelectorAll('#trajets-container .vehicule-form');
  const trajetInitial = document.querySelector('.mainPrincipal:not(#trajets-container .vehicule-form)');

  let nbTrajets = trajetsDynamiques.length;
  if (trajetInitial) {
    nbTrajets += 1;
  }

  const totalCredits = nbTrajets * 2;
  document.getElementById('credit-count').textContent = totalCredits;
}

// Validation des dates (la date d'arrivée ne peut pas être avant la date de départ)
document.getElementById('label4-1').addEventListener('change', function() {
  document.getElementById('label4-2').min = this.value;
});

// Système d'autocomplétion pour les adresses de départ et d'arrivée
function setupAutocompleteMultiple(inputClass, suggestionsClass) {
  const inputs = document.querySelectorAll(`.${inputClass}`);

  inputs.forEach(input => {
    const suggestionsContainer = input.parentElement.querySelector(`.${suggestionsClass}`);

    input.addEventListener('input', function () {
      this.dataset.valid = "false"; // Marqué comme invalide tant que non sélectionné
      const query = this.value;
      if (query.length < 3) {
        suggestionsContainer.innerHTML = '';
        return;
      }

      // Appel à l'API de géocodage
      fetch(`https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(query)}&limit=5`)
        .then(response => response.json())
        .then(data => {
          suggestionsContainer.innerHTML = '';
          data.features.forEach(feature => {
            const suggestion = document.createElement('div');
            suggestion.className = 'suggestion';
            suggestion.textContent = feature.properties.label;
            suggestion.addEventListener('click', () => {
              input.value = feature.properties.label;
              input.dataset.valid = "true"; // Marqué comme valide après sélection
              suggestionsContainer.innerHTML = '';
            });
            suggestionsContainer.appendChild(suggestion);
          });
        })
        .catch(error => console.error('Erreur :', error));
    });
  });
}

// Initialisation de l'autocomplétion
setupAutocompleteMultiple('adresse-depart', 'suggestions-depart');
setupAutocompleteMultiple('adresse-arrivee', 'suggestions-arrivee');

// Validation du formulaire avant soumission
document.querySelector('form').addEventListener('submit', function (e) {
  const champs = this.querySelectorAll('input[data-valid]');
  let valid = true;

  champs.forEach(input => {
    if (input.dataset.valid !== "true") {
      valid = false;
      input.classList.add("erreur");
    } else {
      input.classList.remove("erreur");
    }
  });

  if (!valid) {
    e.preventDefault();
    alert("Veuillez sélectionner une adresse valide dans la liste.");
  }
});

// Gestion de la popup de confirmation
document.querySelector('.open-popup').addEventListener('click', () => {
  document.getElementById('popupForm').style.display = 'block';
});

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

// Renumérotation des trajets après suppression
function renumeroterTrajets() {
  const trajets = document.querySelectorAll('.vehicule-form');
  trajets.forEach((form, i) => {
    form.querySelectorAll('[name]').forEach((input) => {
      input.name = input.name.replace(/trajets\[\d+\]/g, `trajets[${i}]`);
    });
    form.querySelectorAll('[id]').forEach((el) => {
      el.id = el.id.replace(/trajet_\d+/, `trajet_${i}`);
    });
    form.querySelectorAll('label[for]').forEach((label) => {
      label.htmlFor = label.htmlFor.replace(/trajet_\d+/, `trajet_${i}`);
    });
  });
  currentTrajetIndex = trajets.length;
}

let currentTrajetIndex = 1; // Index commençant à 1 car le premier trajet existe déjà

// Ajout dynamique d'un nouveau trajet
document.getElementById("add-trajet-btn").addEventListener("click", (e) => {
  const container = document.getElementById("trajets-container");
  const index = currentTrajetIndex++;

  const vehiculeForm = document.createElement("div");
  vehiculeForm.className = "vehicule-form";

  let optionsVehicules = '<option value="">-- Choisir un véhicule --</option>';

if (vehiculesDisponibles.length > 0) {
  vehiculesDisponibles.forEach(v => {
    optionsVehicules += `
      <option value="${v.id}">
        ${v.marque} ${v.modele}
      </option>
    `;
  });
} else {
  optionsVehicules += '<option value="">Aucun véhicule disponible</option>';
}


  vehiculeForm.innerHTML = `
   <div class="back">
  <div class="mainPrincipal">
    <div class="form-container">
      <label class="form-label">Adresse départ</label>
      <input type="search" name="trajets[${index}][adresse_depart]" class="form-input adresse-depart" autocomplete="off" placeholder="Ex: 10 rue de la paix" required data-valid="false" />
      <div class="suggestions suggestions-depart"></div>
    </div>

    <div class="form-container">
      <label class="form-label">Adresse arrivée</label>
      <input type="search" name="trajets[${index}][adresse_arrive]" class="form-input adresse-arrivee" autocomplete="off" placeholder="Ex: 10 rue de la république" required data-valid="false" />
      <div class="suggestions suggestions-arrivee"></div>
    </div>

    <div class="containerDivlabel3">
      <div class="divLablel3-1">
        <label class="textLabel">Heure de départ</label>
        <input type="time" name="trajets[${index}][heure_depart]" class="label3-1" required />
      </div>
      <div class="divLablel3-2">
        <label class="textLabel">Heure d'arrivée</label>
        <input type="time" name="trajets[${index}][heure_arrive]" class="label3-2" required />
      </div>
    </div>

    <div class="containerDivlabel4">
      <div class="divLablel4-1">
        <label class="textLabel">Date de départ</label>
        <input type="date" name="trajets[${index}][date_depart]" class="label4-1" required />
      </div>
      <div class="divLablel4-2">
        <label class="textLabel">Date d'arrivée</label>
        <input type="date" name="trajets[${index}][date_arrive]" class="label4-2" required />
      </div>
    </div>

    <div class="containerDivlabel5">
      <div class="divLablel5">
        <label class="textLabel">Prix</label>
        <input type="number" name="trajets[${index}][prix]" class="label5" required min="0" />
        <span class="credit">Crédits</span>
      </div>
      <div class="divLabel6">
        <label class="textLabel">Nombre de places</label>
        <input type="number" name="trajets[${index}][nombre_place]" class="label6" min="1" max="8" />
      </div>
    </div>

    <div class="containerDivlabel6">
      <div class="divLablel7-1">
        <label class="textLabel">Véhicule</label>
        <select name="trajets[${index}][id_vehicule]" class="Cars" required>
    ${optionsVehicules}
  </select>
      </div>
    </div>
    <div class="div4">
      <button type="button" class="removeTrajet">Supprimer ce trajet</button>
    </div>
  </div>
</div>
  `;

  container.appendChild(vehiculeForm);

  // Réactivation de l'autocomplétion pour les nouveaux champs
  setupAutocompleteMultiple('adresse-depart', 'suggestions-depart');
  setupAutocompleteMultiple('adresse-arrivee', 'suggestions-arrivee');

  updateCreditDisplay();
  
  // Gestion de la suppression du trajet
  vehiculeForm.querySelector(".removeTrajet").addEventListener("click", () => {
    vehiculeForm.remove();
    renumeroterTrajets();
    updateCreditDisplay();
  });
});

// Gestion de l'ajout de préférences personnalisées
document.addEventListener("click", (event) => {
    if (event.target.closest(".ajout-preference-perso")) {
        event.preventDefault();
        const input = document.getElementById("new-preference-input");
        const preferenceText = input.value.trim();
        
        if (preferenceText) {
            const container = document.querySelector(".container-align2");
            
            const newPref = document.createElement("div");
            newPref.className = "preference-item";
            newPref.innerHTML = `
                <input type="checkbox" name="preferences[]" value="${preferenceText}" checked>
                <label class="textLabel">${preferenceText}</label>
            `;
            
            container.insertBefore(newPref, document.querySelector(".new-preference-form"));
            input.value = "";
        }
    }
});

// Confirmation de soumission du formulaire
document.getElementById("confirmSubmit").addEventListener("click", () => {
  document.getElementById("infos-chauffeur").submit();
});