// Gestion de l'ajout dynamique de véhicules pour les chauffeurs

document.addEventListener("DOMContentLoaded", () => {
  const ajoutBtn = document.querySelector(".ajout-vehicule");
  const vehiculesContainer = document.querySelectorAll(".vehicules-container");

  // Gestion de l'ajout d'un nouveau véhicule
  ajoutBtn.addEventListener("click", (event) => {
    event.preventDefault();

    // Création d'un nouveau bloc véhicule
    const nouveauVehicule = document.createElement("div");
    nouveauVehicule.className = "bloc-vehicule";
    nouveauVehicule.innerHTML = `
      <div class="container1">
        <div class="text-container">
          <div class="div1">
            <label class="text-label">Plaque d'immatriculation</label>
            <input type="text" class="label1 input1" name="plaque_immatriculation[]" placeholder="GR-156-RF" required>
          </div>
          <div class="div1">
            <label class="text-label">Date première immatriculation</label>
            <input type="date" class="label2 input1" name="date_immatriculation[]" required>
          </div>
          <div class="container-align">
            <div class="div2">
              <label class="text-label2">Marque</label>
              <input type="text" class="label3 input2" name="marque[]" placeholder="Tesla" required>
            </div>
            <div class="div2">
              <label class="text-label2">Modèle</label>
              <input type="text" class="label4 input2" name="modele[]" placeholder="Model Y" required>
            </div>
            <div class="div2">
              <label class="text-label2">Couleur</label>
              <input type="text" class="label5 input2" name="couleur[]" placeholder="Bleu" required>
            </div>
          </div>
        </div>
      </div>
      <div class="container2">
        <div class="text-container2">
          <div class="number">
            <label class="text-label2">Nombre de places</label>
            <input type="number" class="label6 input3" name="capacite[]" placeholder="5" min="2" max="8" required>
          </div>
          <div>
            <select name="energie[]" id="energie" class="input4" required>
              <option value="" disabled selected>Type d'énergie</option>
              <option value="essence">Essence</option>
              <option value="diesel">Diesel</option>
              <option value="electrique">Électrique</option>
              <option value="hybride">Hybride</option>
              <option value="autre">Autre</option>
            </select>
          </div>
        </div>
      </div>
      <div class="surContainerDiv4">
        <div class="containerDiv4">
          <div class="div4">
            <button type="button" class="removeVehicule">Supprimer ce véhicule</button>
          </div>
        </div>
      </div>
    `;

    // Ajout du nouveau véhicule dans chaque container
    vehiculesContainer.forEach(container => {
      container.appendChild(nouveauVehicule);
    });

    // Gestion de la suppression du véhicule
    const removeBtn = nouveauVehicule.querySelector(".removeVehicule");
    removeBtn.addEventListener("click", () => {
      nouveauVehicule.remove();
    });
  });
});