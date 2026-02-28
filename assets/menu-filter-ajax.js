/**
 * Filtrage AJAX des menus sans rechargement de page
 *
 * Ce script permet de filtrer dynamiquement les menus affichés sur la page catalogue
 * en envoyant des requêtes AJAX à l'API /api/menus/filter
 *
 * Fonctionnalités :
 * - Écoute les changements sur tous les filtres (prix, thème, régime, nb personnes)
 * - Envoie une requête AJAX vers l'API backend
 * - Met à jour le DOM avec les résultats sans recharger la page
 * - Affiche un loader pendant le chargement
 */

/**
 * Point d'entrée : initialise le filtrage AJAX quand le DOM est prêt
 */
// Indiquer que le module AJAX est chargé pour éviter les conflits avec app.js
window.MenuFilterAjaxLoaded = true;

// Flag pour éviter les initialisations multiples
let initialized = false;

document.addEventListener('DOMContentLoaded', function() {
    // Si déjà initialisé, ne rien faire
    if (initialized) {
        return;
    }
    initialized = true;

    // Désactiver Turbo Drive pour éviter qu'il interfère avec notre AJAX
    if (window.Turbo) {
        Turbo.session.drive = false;
    }

    // Récupération des éléments du DOM
    const filterForm = document.getElementById('filterForm');
    const menuGrid = document.getElementById('menuGrid');
    const priceSlider = document.querySelector('input[name="price_max"]');
    const themeSelect = document.querySelector('select[name="theme"]');
    const nbPersonInput = document.querySelector('input[name="nb_person_min"]');
    const regimeButtons = document.querySelectorAll('.regime-filter-btn[data-dietetary]');

    // Vérifier que les éléments existent
    if (!filterForm || !menuGrid) {
        return;
    }

    /**
     * Empêcher la soumission normale du formulaire
     * Cela évite le rechargement de la page
     */
    filterForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Annule la soumission normale
    });

    /**
     * Collecte tous les paramètres de filtrage depuis le formulaire
     * @returns {URLSearchParams} Paramètres à envoyer à l'API
     */
    function collectFilters() {
        const params = new URLSearchParams();

        // Prix maximum (en euros)
        if (priceSlider && priceSlider.value) {
            params.append('price_max', priceSlider.value);
        }

        // Thème sélectionné
        if (themeSelect && themeSelect.value) {
            params.append('theme', themeSelect.value);
        }

        // Nombre de personnes minimum
        if (nbPersonInput && nbPersonInput.value) {
            params.append('nb_person_min', nbPersonInput.value);
        }

        // Régimes alimentaires sélectionnés (peut être multiple)
        const selectedDietetaries = document.querySelectorAll('input[name="dietetary[]"]:checked');
        selectedDietetaries.forEach(checkbox => {
            params.append('dietetary[]', checkbox.value);
        });

        return params;
    }

    /**
     * Affiche un loader pendant le chargement
     */
    function showLoader() {
        menuGrid.innerHTML = `
            <div class="menu-loader">
                <div class="spinner"></div>
                <p>Chargement des menus...</p>
            </div>
        `;
    }

    /**
     * Génère le HTML pour une carte de menu
     * @param {Object} menu - Données du menu depuis l'API
     * @returns {string} HTML de la carte
     */
    function generateMenuCard(menu) {
        // Vérifier la disponibilité du menu
        const isAvailable = menu.stock === null || menu.stock >= menu.nbPersonMin;
        const unavailableClass = isAvailable ? '' : ' menu-card-unavailable';

        // Badge indisponible si nécessaire
        const unavailableBadge = isAvailable ? '' : '<span class="badge badge-unavailable">Indisponible</span>';

        // Générer les badges de régimes alimentaires
        let dietetaryBadges = '';
        if (menu.dietetaries && menu.dietetaries.length > 0) {
            menu.dietetaries.forEach(dietetary => {
                const badgeClass = ['Végétarien', 'Vegan'].includes(dietetary.name)
                    ? 'badge-vegetarian'
                    : 'badge-classic';
                dietetaryBadges += `<span class="badge ${badgeClass}">${dietetary.name}</span>`;
            });
        } else {
            dietetaryBadges = '<span class="badge badge-classic">Classique</span>';
        }

        // Convertir le prix de centimes en euros
        const priceInEuros = (menu.pricePerPerson / 100).toLocaleString('fr-FR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });

        // Construire l'URL de la page détail du menu
        const detailUrl = `/menus/${menu.id}`;

        // Retourner le HTML complet de la carte
        return `
            <div class="menu-card${unavailableClass}">
                <!-- Image avec gradient overlay - cliquable -->
                <a href="${detailUrl}" class="menu-card-image">
                    <img src="${menu.illustration}"
                         alt="${menu.textAlt || menu.name}">
                    <div class="menu-card-badges">
                        <span class="badge badge-theme">${menu.theme.name}</span>
                        ${unavailableBadge}
                        ${dietetaryBadges}
                    </div>
                </a>

                <!-- Contenu -->
                <div class="menu-card-content">
                    <h3 class="menu-card-title">${menu.name}</h3>
                    <p class="menu-card-description">
                        ${menu.description}
                    </p>

                    <!-- Infos pratiques -->
                    <div class="menu-card-info">
                        <span>
                            <span data-icon="users" data-icon-width="16" data-icon-height="16" class="icon-gold"></span>
                            À partir de ${menu.nbPersonMin} pers.
                        </span>
                    </div>

                    <!-- Prix et CTA -->
                    <div class="menu-card-footer">
                        <div class="menu-card-price">
                            <small>À partir de</small>
                            <span class="price-value">${priceInEuros}€</span>
                        </div>
                        <a href="${detailUrl}" class="btn-view-details">
                            <span data-icon="eye" data-icon-width="16" data-icon-height="16"></span>
                            <span class="btn-text-full">Voir détails</span>
                            <span class="btn-text-short">Voir</span>
                        </a>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Affiche un message quand aucun menu n'est trouvé
     */
    function showNoResultsMessage() {
        menuGrid.innerHTML = `
            <div class="no-menu-message">
                <span data-icon="info" data-icon-width="24" data-icon-height="24"></span>
                <h5>Aucun menu trouvé</h5>
                <p>Essayez de modifier vos critères de recherche</p>
            </div>
        `;
    }

    /**
     * Met à jour le compteur de résultats
     * @param {number} count - Nombre de menus affichés
     */
    function updateResultsCount(count) {
        const resultsCountElement = document.querySelector('.menu-results-count p');
        if (resultsCountElement) {
            const plural = count > 1 ? 's' : '';
            const pluralDisponible = count > 1 ? 's' : '';
            resultsCountElement.textContent =
                `${count} menu${plural} disponible${pluralDisponible}`;
        }
    }

    /**
     * Envoie une requête AJAX pour filtrer les menus
     * C'est la fonction principale qui orchestre tout le processus
     */
    async function filterMenus() {
        // 1. Afficher le loader
        showLoader();

        // 2. Collecter les paramètres de filtrage
        const params = collectFilters();

        // 3. Construire l'URL de l'API avec les paramètres
        const apiUrl = `/api/menus/filter?${params.toString()}`;

        try {
            // 4. Envoyer la requête AJAX vers l'API
            const response = await fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest' // Indique que c'est une requête AJAX
                }
            });

            // 5. Vérifier que la réponse est OK (status 200)
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // 6. Parser la réponse JSON
            const data = await response.json();

            // 7. Vérifier que la réponse est un succès
            if (!data.success) {
                throw new Error(data.error || 'Erreur inconnue');
            }

            // 8. Vider la grille actuelle
            menuGrid.innerHTML = '';

            // 9. Si aucun résultat, afficher le message
            if (data.menus.length === 0) {
                showNoResultsMessage();
                updateResultsCount(0);
                return;
            }

            // 10. Générer et afficher les cartes de menus
            data.menus.forEach(menu => {
                menuGrid.innerHTML += generateMenuCard(menu);
            });

            // 11. Réinitialiser les icônes (si vous utilisez un système d'icônes)
            if (window.initIcons) {
                window.initIcons();
            }

            // 12. Mettre à jour le compteur de résultats
            updateResultsCount(data.count);

        } catch (error) {
            // En cas d'erreur, afficher un message d'erreur
            menuGrid.innerHTML = `
                <div class="no-menu-message">
                    <span data-icon="alert-circle" data-icon-width="24" data-icon-height="24"></span>
                    <h5>Erreur de chargement</h5>
                    <p>Une erreur est survenue lors du chargement des menus. Veuillez réessayer.</p>
                </div>
            `;
        }
    }

    /**
     * Débounce : retarde l'exécution d'une fonction jusqu'à ce que
     * l'utilisateur arrête de taper/changer pendant un certain temps
     * Évite de faire trop de requêtes pendant que l'utilisateur ajuste le slider
     *
     * @param {Function} func - Fonction à retarder
     * @param {number} wait - Temps d'attente en millisecondes
     * @returns {Function} Fonction debounced
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Créer une version debounced de filterMenus (attend 300ms)
    const debouncedFilter = debounce(filterMenus, 300);

    /**
     * Fonction pour mettre à jour le gradient du slider de prix
     */
    function updatePriceSlider(slider) {
        const min = slider.min || 0;
        const max = slider.max || 100;
        const value = slider.value;

        // Calculer le pourcentage
        const percentage = ((value - min) / (max - min)) * 100;

        // Appliquer le gradient
        slider.style.background = `linear-gradient(to right, var(--bordeaux) 0%, var(--bordeaux) ${percentage}%, #e5e5e5 ${percentage}%, #e5e5e5 100%)`;
    }

    /**
     * Attacher les événements aux différents filtres
     */

    // Prix slider : utiliser debounce car il peut changer rapidement
    if (priceSlider) {
        // Initialiser le gradient au chargement
        updatePriceSlider(priceSlider);

        // Mettre à jour le gradient pendant le drag
        priceSlider.addEventListener('input', function(e) {
            updatePriceSlider(e.target);
            debouncedFilter();
        });
    }

    // Thème : filtrer immédiatement au changement
    if (themeSelect) {
        themeSelect.addEventListener('change', filterMenus);
    }

    // Nombre de personnes : utiliser debounce car l'utilisateur tape
    if (nbPersonInput) {
        nbPersonInput.addEventListener('input', debouncedFilter);
    }

    // Boutons de régimes alimentaires : gérer le toggle des boutons
    regimeButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Trouver la checkbox associée
            const dietetaryId = this.getAttribute('data-dietetary');
            const checkbox = document.getElementById(`dietetary_${dietetaryId}`);

            if (checkbox) {
                // Toggle la checkbox
                checkbox.checked = !checkbox.checked;

                // Toggle la classe active du bouton
                this.classList.toggle('active');

                // Filtrer immédiatement
                filterMenus();
            }
        });
    });

    // Bouton "Tous" pour les régimes : décocher tous les régimes
    window.clearDietetaryFilters = function(button) {
        // Décocher toutes les checkboxes
        document.querySelectorAll('input[name="dietetary[]"]').forEach(checkbox => {
            checkbox.checked = false;
        });

        // Retirer la classe active de tous les boutons de régime
        regimeButtons.forEach(btn => {
            btn.classList.remove('active');
        });

        // Activer le bouton "Tous"
        button.classList.add('active');

        // Filtrer
        filterMenus();
    };
});
