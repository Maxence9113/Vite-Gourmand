/**
 * Recherche en temps réel dans les tableaux
 * Filtre les lignes d'un tableau selon le texte saisi dans un champ de recherche
 */

/**
 * Initialise la recherche en temps réel sur un tableau
 * @param {string} searchInputId - ID du champ de recherche
 * @param {string} rowSelector - Sélecteur CSS des lignes à filtrer (ex: '.allergen-row')
 */
function initTableSearch(searchInputId, rowSelector) {
    const searchInput = document.getElementById(searchInputId);

    if (!searchInput) {
        console.warn(`[TableSearch] Input with id "${searchInputId}" not found`);
        return;
    }

    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll(rowSelector);

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

// Export pour utilisation globale
window.initTableSearch = initTableSearch;