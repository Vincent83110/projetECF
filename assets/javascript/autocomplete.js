// Système d'autocomplétion pour les champs d'adresse
function setupAutocomplete(inputId, suggestionsId) {
    const input = document.getElementById(inputId);
    const suggestions = document.getElementById(suggestionsId);

    suggestions.style.display = 'none';

    input.addEventListener('input', function () {
        const query = input.value;
        if (query.length < 3) {
            suggestions.innerHTML = '';
            suggestions.style.display = 'none';
            return;
        }

        // Appel à l'API de géocodage
        fetch(`https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(query)}&limit=5`)
            .then(response => response.json())
            .then(data => {
                suggestions.innerHTML = '';
                if (!data.features.length) {
                    suggestions.style.display = 'none';
                    return;
                }

                data.features.forEach(feature => {
                    const item = document.createElement('li');
                    item.textContent = feature.properties.label;
                    item.addEventListener('mousedown', function () {
                        input.value = feature.properties.label;
                        suggestions.innerHTML = '';
                        suggestions.style.display = 'none';
                    });

                    suggestions.appendChild(item);
                });

                suggestions.style.display = 'block';
            });
    });

    document.addEventListener('click', function (e) {
        if (!suggestions.contains(e.target) && e.target !== input) {
            suggestions.innerHTML = '';
            suggestions.style.display = 'none';
        }
    });
}

// Initialisation de l'autocomplétion au chargement
window.addEventListener('DOMContentLoaded', function () {
    setupAutocomplete('departure', 'suggestions-departure');
    setupAutocomplete('destination', 'suggestions-destination');
});