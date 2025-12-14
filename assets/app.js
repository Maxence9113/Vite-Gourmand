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

// Remplacer les icônes au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    feather.replace();
});

import * as Turbo from '@hotwired/turbo';
Turbo.setFormMode("off"); // Désactive Turbo pour TOUS les formulaires
