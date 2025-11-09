// Gestion complète de la page de recherche de covoiturages

// Variables globales pour la pagination
let currentPage = 1;
const itemsPerPage = 5;
let totalPages = 1;
let currentTrajets = [];
let currentPassengers = 1;

// Gestion de l'ouverture/fermeture des filtres
let cross = document.querySelector('.cross');
let filters = document.querySelector('.filters');
let button = document.querySelector('.button');

button.addEventListener('click', (event) => {
  event.preventDefault();
  filters.classList.toggle('open');
});

cross.addEventListener('click', (event) => {
  event.preventDefault();
  filters.classList.remove('open');
});

// Système de notation par étoiles
const etoiles = document.querySelectorAll('.etoile');
etoiles.forEach((etoile, index) => {
  etoile.addEventListener('click', () => {
    etoiles.forEach((e, i) => {
      e.src = (i <= index ? `${BASE_URL}/assets/images/starOn.svg` : `${BASE_URL}/assets/images/starOff.svg`)
    });
  });
});

// Fonctions de formatage
function formatDate(dateString) {
  const [year, month, day] = dateString.split('-');
  return `${day}/${month}/${year}`;
}

function formatTime(timeString) {
  const [hour, minute] = timeString.split(':');
  return `${hour}h${minute}`;
}

function calculerDureeAvecDates(dateDepart, heureDepart, dateArrivee, heureArrivee) {
  const [anneeD, moisD, jourD] = dateDepart.split('-').map(Number);
  const [hD, mD] = heureDepart.split(':').map(Number);
  const depart = new Date(anneeD, moisD - 1, jourD, hD, mD);

  const [anneeA, moisA, jourA] = dateArrivee.split('-').map(Number);
  const [hA, mA] = heureArrivee.split(':').map(Number);
  const arrivee = new Date(anneeA, moisA - 1, jourA, hA, mA);

  let diffMs = arrivee - depart;
  if (diffMs < 0) diffMs += 24 * 60 * 60 * 1000;

  const diffMinutes = Math.floor(diffMs / 60000);
  const heures = Math.floor(diffMinutes / 60);
  const minutes = diffMinutes % 60;

  return `${heures}h${minutes.toString().padStart(2, '0')}`;
}

function extraireVille(adresse) {
  const match = adresse.match(/\b\d{5}\s+([\wÀ-ÿ\- ]+)/);
  return match ? match[1].trim() : adresse;
}

// Initialisation au chargement
document.addEventListener("DOMContentLoaded", function () {
  function getInitialParams() {
    const urlParams = new URLSearchParams(window.location.search);
    return {
      departure: urlParams.get("departure"),
      destination: urlParams.get("destination"),
      date: urlParams.get("date"),
      passengers: urlParams.get("passengers") || 1
    };
  }

  function getFilters() {
    const mainForm = {
      eco: document.querySelector("#checkbox_eco2")?.checked ? "Oui" : null,
      min_time: document.querySelector("#minTime")?.value || null,
      max_time: document.querySelector("#maxTime")?.value || null,
      min_price: document.querySelector("#minPrice")?.value || null,
      max_price: document.querySelector("#maxPrice")?.value || null,
      note: document.querySelector("#filtersForm input[name='note']:checked")?.value || null
    };

    const sideForm = {
      eco: document.querySelector("#checkbox_eco1")?.checked ? "Oui" : null,
      min_time: document.querySelector("#minTime1")?.value || null,
      max_time: document.querySelector("#maxTime1")?.value || null,
      min_price: document.querySelector("#minPrice1")?.value || null,
      max_price: document.querySelector("#maxPrice1")?.value || null,
      note: document.querySelector(".etoilesSide input[name='note']:checked")?.value || null
    };

    return {
      eco: sideForm.eco || mainForm.eco,
      min_time: sideForm.min_time || mainForm.min_time,
      max_time: sideForm.max_time || mainForm.max_time,
      min_price: sideForm.min_price || mainForm.min_price,
      max_price: sideForm.max_price || mainForm.max_price,
      note: sideForm.note || mainForm.note
    };
  }

  function buildQuery(params, filters) {
    const query = new URLSearchParams();
    for (const [key, value] of Object.entries(params)) {
      if (value) query.append(key, value);
    }
    for (const [key, value] of Object.entries(filters)) {
      if (value) query.append(key, value);
    }
    return query.toString();
  }

  function entierOuDecimal(note) {
    if (note == null || isNaN(note)) return '--';
    const n = parseFloat(note);
    if (Number.isInteger(n)) return n.toString();
    return parseFloat(n.toFixed(2)).toString();
  }

  function afficherTrajets(data, passengers = 1) {
    currentTrajets = data;
    currentPassengers = passengers;
    totalPages = Math.ceil(data.length / itemsPerPage);
    currentPage = 1;

    updatePaginationControls();
    displayCurrentPage();

    const paginationControls = document.querySelector('.pagination-controls');
    if (totalPages > 1) {
      paginationControls.style.display = 'flex';
    } else {
      paginationControls.style.display = 'none';
    }
  }

  function displayCurrentPage() {
    const container = document.getElementById("col3Covoit");
    container.innerHTML = "";
    
    if (!currentTrajets.length) {
      container.innerHTML = "<p>Aucun trajet trouvé.</p>";
      return;
    }
    
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, currentTrajets.length);
    const paginatedData = currentTrajets.slice(startIndex, endIndex);
    
    function renderTrajets() {
  container.innerHTML = "";

  paginatedData.forEach(trajet => {
    const trajetDiv = document.createElement("div");
    trajetDiv.className = "trajet";
    let content = "";

    if (window.innerWidth > 645) {
      // Affichage grand écran
      content = `
        <div class="container">
          <div class="col1Pop">
            <img src="${BASE_URL}/assets/images/profil.svg" class="userPop" alt="">
            <span class="textNote">
              ${trajet.note_conducteur !== null ? '★ ' + entierOuDecimal(trajet.note_conducteur) + '/5' : '--/5'}
            </span>
          </div>
          <div class="col2Pop">
            <div class="spacePop">
              <span class="namePop">${trajet.username}</span>
              <span>${formatDate(trajet.date_depart)}</span>
            </div>
            <div class="ContainerlineWithText">
              <div class="townPop">
                <span>${extraireVille(trajet.adresse_depart)}</span>
                <span>${extraireVille(trajet.adresse_arrive)}</span>
              </div>
              <div class="lineWithText">
                <hr class="hrCovoit">
                <span>${calculerDureeAvecDates(trajet.date_depart, trajet.heure_depart, trajet.date_arrive, trajet.heure_arrive)}</span>
                <hr class="hrCovoit">
              </div>
              <div class="HourPop">
                <span>Départ : ${formatTime(trajet.heure_depart)}</span>
                <span>Arrivée : ${formatTime(trajet.heure_arrive)}</span>
              </div>
            </div>
            <div class="ecoRowPop">
              ${trajet.trajet_ecologique === 'Oui' ? `
                <span class="trajetEcologique">
                  <img src="${BASE_URL}/assets/images/trajet-ecologique.svg" alt="Trajet écologique">
                  Trajet écologique
                </span>` : `<span class="trajetEcologique"></span>`}
            </div>
          </div>
          <div class="col3Pop">
            <div class="infoPop">
              <span class="creditPop">${trajet.prix} crédit${trajet.prix > 1 ? 's' : ''}</span>
              <span class="placePop">${trajet.nombre_place} place${trajet.nombre_place > 1 ? 's' : ''} disponible${trajet.nombre_place > 1 ? 's' : ''}</span>
            </div>
            <a class="linkCovoit" href="${BASE_URL}/pages/pageCovoiturageIndividuelle.php?id=${trajet.id}&passengers=${currentPassengers}">Détails...</a>
          </div>
        </div>
      `;
    } else {
      // Affichage mobile
      content = `
        <div class="container">
          <div class="col1Pop">
            <div class="topRowPop">
              <div class="backRowPop">
                <img src="${BASE_URL}/assets/images/profil.svg" class="userPop" alt="">
                <div class="infoPopMobile">
                  <span class="textNote">
                    ${trajet.note_conducteur !== null ? '★ ' + parseFloat(trajet.note_conducteur).toFixed(1) + '/5' : '--/5'}
                  </span>
                  <span class="namePop">${trajet.username}</span>
                </div>
              </div>
              <span>${formatDate(trajet.date_depart)}</span>
            </div>
          </div>
          <div class="col2Pop">
            <div class="ContainerlineWithText">
              <div class="townPop">
                <span>${extraireVille(trajet.adresse_depart)}</span>
                <span>${extraireVille(trajet.adresse_arrive)}</span>
              </div>
              <div class="lineWithText">
                <hr class="hrCovoit">
                <span>${calculerDureeAvecDates(trajet.date_depart, trajet.heure_depart, trajet.date_arrive, trajet.heure_arrive)}</span>
                <hr class="hrCovoit">
              </div>
              <div class="HourPop">
                <span>Départ : ${formatTime(trajet.heure_depart)}</span>
                <span>Arrivée : ${formatTime(trajet.heure_arrive)}</span>
              </div>
            </div>
            <div class="ecoRowPop">
              ${trajet.trajet_ecologique === 'Oui' ? `
                <span class="trajetEcologique">
                  <img src="${BASE_URL}/assets/images/trajet-ecologique.svg" alt="Trajet écologique">
                  Trajet écolo
                </span>` : `<span class="trajetEcologique"></span>`}
            </div>
          </div>
          <div class="col3Pop">
            <div class="infoPop">
              <div class="rowInfoPop">  
                <span class="creditPop">${trajet.prix} crédit${trajet.prix > 1 ? 's' : ''}</span>
                <span class="placePop">${trajet.nombre_place} place${trajet.nombre_place > 1 ? 's' : ''} disponible${trajet.nombre_place > 1 ? 's' : ''}</span>
              </div>
              <a class="linkCovoit" href="${BASE_URL}/pages/pageCovoiturageIndividuelle.php?id=${trajet.id}&passengers=${currentPassengers}">Détails...</a>
            </div>
          </div>
        </div>
      `;
    }

    trajetDiv.innerHTML = content;
    container.appendChild(trajetDiv);
  });
}


    renderTrajets();
    window.addEventListener("resize", renderTrajets);
  }

  function updatePaginationControls() {
    document.getElementById('prevPage').disabled = currentPage <= 1;
    document.getElementById('nextPage').disabled = currentPage >= totalPages;
    document.getElementById('pageInfo').textContent = `Page ${currentPage} sur ${totalPages}`;
  }

  document.getElementById('prevPage').addEventListener('click', () => {
    if (currentPage > 1) {
      currentPage--;
      displayCurrentPage();
      updatePaginationControls();
    }
  });

  document.getElementById('nextPage').addEventListener('click', () => {
    if (currentPage < totalPages) {
      currentPage++;
      displayCurrentPage();
      updatePaginationControls();
    }
  });

  const params = getInitialParams();
  const buttonApply = document.querySelectorAll(".buttonApply");
  
  buttonApply.forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      loadTrajets(true);
    });
  });

  function loadTrajets(useFilters = false) {
    const container = document.getElementById("col3Covoit");
    container.innerHTML = "<div class='loading'>Chargement en cours...</div>";

    const filters = useFilters ? getFilters() : {};
    const queryString = buildQuery(params, filters);
    const endpoint = useFilters ? `${BASE_URL}/actions/filters.php` : `${BASE_URL}/actions/rechercheTrajet.php`;
    const url = `${endpoint}?${queryString}&_=${Date.now()}`;

    console.log("URL de la requête:", url);

    fetch(url)
      .then(response => {
        if (!response.ok) throw new Error(`Erreur HTTP: ${response.status}`);
        return response.json().catch(() => { throw new Error("Réponse JSON invalide"); });
      })
      .then(data => {
        console.log("Données reçues:", data);
        if (!Array.isArray(data.trajets)) throw new Error("Format de données incorrect");

        if (data.trajets.length === 0) {
          if (data.suggestion) {
            container.innerHTML = `
              <div class="message-container">
                <p class="no-results">
                  Aucun covoiturage trouvé à cette date.<br>
                  <strong>Suggestion :</strong> covoiturage disponible le <strong>${formatDate(data.suggestion.date)}</strong> de <strong>${extraireVille(data.suggestion.depart)}</strong> à <strong>${extraireVille(data.suggestion.arrivee)}</strong>.<br>
                  <button id="goToSuggestion" class="voirTrajet">Voir ce trajet</button>
                </p>
              </div>
            `;
            document.getElementById("goToSuggestion").addEventListener("click", () => {
              params.date = data.suggestion.date;
              loadTrajets(false);
            });
          } else {
            container.innerHTML = `<div class="message-container"><p class='no-results'>Aucun covoiturage trouvé pour vos critères.</p></div>`;
          }
          document.querySelector('.pagination-controls').style.display = 'none';
          return;
        } else {
          afficherTrajets(data.trajets, params.passengers);
          const paginationControls = document.querySelector('.pagination-controls');
          if (data.trajets.length > itemsPerPage) {
            paginationControls.style.display = 'flex';
          } else {
            paginationControls.style.display = 'none';
          }
        }
      })
      .catch(error => {
        console.error("Erreur complète:", error);
        let message = "Erreur lors du chargement des trajets.<br>" + error.message + "<br>Veuillez réessayer.";
        if (error.message.includes("403")) {
          message = `Accès interdit.<br>Les chauffeurs ne peuvent pas effectuer de recherche de trajets.`;
        }
        container.innerHTML = `<p class="error-message">${message}</p>`;
        const pagination = document.querySelector('.pagination-controls');
        if (pagination) pagination.style.display = 'none';
      });
  }

  loadTrajets(false);

  const filtersForm = document.getElementById("filtersForm");
  if (filtersForm) {
    filtersForm.addEventListener("submit", function(e) {
      e.preventDefault();
      loadTrajets(true);
    });
  }

  document.getElementById("searchForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const departure = document.getElementById("departure").value.trim();
    const destination = document.getElementById("destination").value.trim();
    const date = document.getElementById("date").value;
    const passengers = document.getElementById("passengers").value || 1;

    if (!departure || !destination || !date) {
      alert("Veuillez remplir tous les champs.");
      return;
    }

    params.departure = departure;
    params.destination = destination;
    params.date = date;
    params.passengers = passengers;

    loadTrajets(false);
  });
});