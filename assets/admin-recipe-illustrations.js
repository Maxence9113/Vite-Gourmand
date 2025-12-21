/**
 * JavaScript pour gérer l'ajout et la suppression dynamique d'illustrations de recettes
 * avec preview d'image
 */

// Fonction d'initialisation
function initRecipeIllustrations() {
    // Récupérer le conteneur des illustrations
    const illustrationsList = document.getElementById('illustrations-list');

    // Si le conteneur n'existe pas, on quitte (pas sur la page des recettes)
    if (!illustrationsList) {
        return;
    }

    // Vérifier si déjà initialisé pour éviter les doublons
    if (illustrationsList.dataset.initialized === 'true') {
        console.log('Script déjà initialisé, on skip');
        return;
    }

    // Marquer comme initialisé
    illustrationsList.dataset.initialized = 'true';
    console.log('Initialisation du script illustrations');

    // Récupérer le bouton "Ajouter une illustration"
    const addButton = document.getElementById('add-illustration');

    // Si le bouton n'existe pas, on quitte
    if (!addButton) {
        return;
    }

    // Récupérer le prototype (template HTML) depuis l'attribut data-prototype
    // Ce prototype est généré par Symfony et contient __name__ comme placeholder
    const prototype = illustrationsList.dataset.prototype;

    // Compter combien d'illustrations existent déjà
    let index = parseInt(illustrationsList.dataset.index);

    /**
     * Fonction pour ajouter une nouvelle illustration
     * IMPORTANT: On utilise une fonction pour éviter d'attacher plusieurs fois l'événement
     */
    addButton.addEventListener('click', function(e) {
        // Empêcher le comportement par défaut du bouton
        e.preventDefault();

        // Debug: afficher un message dans la console
        console.log('Ajout d\'une illustration, index actuel:', index);

        // 1. Remplacer __name__ dans le prototype par l'index actuel
        // Ex: recipe[recipeIllustrations][__name__] devient recipe[recipeIllustrations][0]
        const newForm = prototype.replace(/__name__/g, index);

        // 2. Créer un élément div pour contenir le nouveau formulaire
        const illustrationItem = document.createElement('div');
        illustrationItem.classList.add('card', 'mb-3', 'illustration-item');

        // 3. Insérer le HTML du formulaire dans la carte avec une zone pour la preview
        illustrationItem.innerHTML =
            '<div class="card-body">' +
                '<div class="row">' +
                    '<div class="col-md-8">' +
                        newForm +
                    '</div>' +
                    '<div class="col-md-4">' +
                        '<div class="preview-container" style="display: none;">' +
                            '<p class="text-muted small mb-1">Aperçu :</p>' +
                            '<img src="" alt="Preview" class="img-thumbnail preview-image" style="max-width: 100%; max-height: 200px;">' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<button type="button" class="btn btn-danger btn-sm mt-2 remove-illustration">' +
                    'Supprimer cette illustration' +
                '</button>' +
            '</div>';

        // 4. Ajouter la nouvelle illustration à la liste
        illustrationsList.appendChild(illustrationItem);

        // 5. Trouver le champ de fichier qui vient d'être ajouté et attacher l'événement de preview
        const fileInput = illustrationItem.querySelector('input[type="file"]');
        if (fileInput) {
            attachPreviewEvent(fileInput, illustrationItem);
        }

        // 6. Incrémenter l'index pour la prochaine illustration
        index++;
        illustrationsList.dataset.index = index;

        // 7. Attacher l'événement de suppression au nouveau bouton
        attachRemoveEvent(illustrationItem.querySelector('.remove-illustration'));
    });

    /**
     * Fonction pour afficher un aperçu de l'image sélectionnée
     */
    function attachPreviewEvent(fileInput, container) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];

            // Si un fichier a été sélectionné et que c'est une image
            if (file && file.type.startsWith('image/')) {
                // Créer un FileReader pour lire le fichier
                const reader = new FileReader();

                reader.onload = function(e) {
                    // Trouver les éléments de preview dans le container
                    const previewContainer = container.querySelector('.preview-container');
                    const previewImage = container.querySelector('.preview-image');

                    if (previewContainer && previewImage) {
                        // Afficher l'image
                        previewImage.src = e.target.result;
                        previewContainer.style.display = 'block';
                    }
                };

                // Lire le fichier comme une URL de données (base64)
                reader.readAsDataURL(file);
            }
        });
    }

    /**
     * Fonction pour attacher l'événement de suppression à un bouton
     */
    function attachRemoveEvent(button) {
        button.addEventListener('click', function() {
            // Demander confirmation avant de supprimer
            if (confirm('Êtes-vous sûr de vouloir supprimer cette illustration ?')) {
                // Trouver l'élément parent (la carte) et le supprimer
                this.closest('.illustration-item').remove();
            }
        });
    }

    /**
     * Attacher l'événement de suppression aux boutons existants
     */
    document.querySelectorAll('.remove-illustration').forEach(function(button) {
        attachRemoveEvent(button);
    });

    /**
     * Attacher l'événement de preview aux champs de fichier existants
     */
    document.querySelectorAll('.illustration-item').forEach(function(item) {
        const fileInput = item.querySelector('input[type="file"]');
        if (fileInput) {
            attachPreviewEvent(fileInput, item);
        }
    });
}

// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', initRecipeIllustrations);

// Réinitialiser après chaque navigation Turbo (si Turbo est utilisé)
document.addEventListener('turbo:load', initRecipeIllustrations);