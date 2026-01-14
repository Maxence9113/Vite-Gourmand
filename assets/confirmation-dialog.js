/**
 * Gestion des dialogues de confirmation
 * Remplace les confirm() natifs par des dialogues stylisés
 */

/**
 * Initialise les confirmations de suppression sur tous les formulaires marqués
 * Cherche les formulaires avec data-confirm="message"
 */
function initConfirmationDialogs() {
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form[data-confirm]');

        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const message = form.getAttribute('data-confirm');
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    });
}

/**
 * Ajoute une confirmation à un formulaire spécifique
 * @param {string} formId - ID du formulaire
 * @param {string} message - Message de confirmation
 */
function addConfirmation(formId, message) {
    const form = document.getElementById(formId);

    if (!form) {
        console.warn(`[ConfirmationDialog] Form with id "${formId}" not found`);
        return;
    }

    form.addEventListener('submit', function(e) {
        if (!confirm(message)) {
            e.preventDefault();
        }
    });
}

// Initialisation automatique
initConfirmationDialogs();

// Export pour utilisation globale
window.addConfirmation = addConfirmation;
window.initConfirmationDialogs = initConfirmationDialogs;
