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
