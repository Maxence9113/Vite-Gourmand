import './bootstrap.js';


// IMPORTER BOOTSTRAP CSS (IMPORTANT!)
import 'bootstrap/dist/css/bootstrap.min.css';

// Votre CSS personnalisé (APRÈS Bootstrap pour pouvoir l'override)
import './styles/app.css';

// IMPORTER BOOTSTRAP JS
import * as bootstrap from 'bootstrap';

// ✅ Vérification du chargement
console.log('✅ Bootstrap chargé');

// Rendre Bootstrap disponible globalement (optionnel mais utile)
window.bootstrap = bootstrap;

// Importer les icons Data-feather
import feather from 'feather-icons';

// Fonction pour initialiser les icônes Feather
function initFeatherIcons() {
    feather.replace();
}

// Remplacer les icônes au chargement initial de la page
document.addEventListener('DOMContentLoaded', initFeatherIcons);

// Désactiver Turbo complètement pour éviter les conflits d'import maps
import * as Turbo from '@hotwired/turbo';
Turbo.session.drive = false; // Désactive la navigation Turbo
Turbo.setFormMode("off"); // Désactive Turbo pour TOUS les formulaires

// Gestion des filtres du catalogue de menus
function initMenuFilters() {
    const filterForm = document.getElementById('filterForm');
    const resetButton = document.getElementById('resetFilters');
    const viewGridBtn = document.getElementById('viewGrid');
    const viewListBtn = document.getElementById('viewList');
    const menuGrid = document.getElementById('menuGrid');

    if (filterForm) {
        // Auto-submit du formulaire lors du changement de filtre
        const filterInputs = filterForm.querySelectorAll('input, select');
        filterInputs.forEach(input => {
            input.addEventListener('change', () => {
                filterForm.submit();
            });
        });

        // Bouton reset
        if (resetButton) {
            resetButton.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = filterForm.action;
            });
        }
    }

    // Gestion de la vue grille/liste
    if (viewGridBtn && viewListBtn && menuGrid) {
        viewGridBtn.addEventListener('change', () => {
            menuGrid.classList.remove('list-view');
            menuGrid.classList.add('row', 'g-4');
            document.querySelectorAll('.menu-card-wrapper').forEach(card => {
                card.classList.remove('col-12');
                card.classList.add('col-md-6', 'col-lg-4');
            });
        });

        viewListBtn.addEventListener('change', () => {
            menuGrid.classList.add('list-view');
            menuGrid.classList.remove('row', 'g-4');
            document.querySelectorAll('.menu-card-wrapper').forEach(card => {
                card.classList.remove('col-md-6', 'col-lg-4');
                card.classList.add('col-12');
            });
        });
    }
}

// Initialiser au chargement
document.addEventListener('DOMContentLoaded', initMenuFilters);
