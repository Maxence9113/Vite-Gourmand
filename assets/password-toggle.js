/**
 * Toggle de visibilité des mots de passe
 * Permet d'afficher/masquer le texte d'un champ password avec une icône
 */

/**
 * Bascule la visibilité d'un champ mot de passe
 * @param {string} inputId - ID du champ input[type="password"]
 * @param {HTMLElement} button - Le bouton contenant l'icône Feather
 */
function togglePasswordVisibility(inputId, button) {
    const input = document.getElementById(inputId);

    if (!input) {
        console.warn(`[PasswordToggle] Input with id "${inputId}" not found`);
        return;
    }

    const icon = button.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.setAttribute('data-feather', 'eye-off');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.setAttribute('data-feather', 'eye');
        }
    }

    // Rafraîchir les icônes Feather si disponible
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
}

// Export pour utilisation globale
window.togglePasswordVisibility = togglePasswordVisibility;
