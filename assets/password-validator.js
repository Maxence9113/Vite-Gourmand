/**
 * Validation de complexité des mots de passe
 * Vérifie qu'un mot de passe contient les caractères requis
 */

/**
 * Valide la complexité d'un mot de passe
 * @param {string} password - Le mot de passe à valider
 * @returns {Object} - { valid: boolean, errors: string[] }
 */
function validatePasswordComplexity(password) {
    const errors = [];

    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);

    if (!hasUpperCase) {
        errors.push('au moins une majuscule');
    }
    if (!hasLowerCase) {
        errors.push('au moins une minuscule');
    }
    if (!hasNumber) {
        errors.push('au moins un chiffre');
    }
    if (!hasSpecialChar) {
        errors.push('au moins un caractère spécial (!@#$%^&*(),.?":{}|<>)');
    }

    return {
        valid: errors.length === 0,
        errors: errors
    };
}

/**
 * Initialise la validation de mot de passe sur un formulaire
 * @param {string} formId - ID du formulaire
 * @param {string} passwordInputId - ID du champ mot de passe
 * @param {string} [confirmPasswordInputId] - ID du champ confirmation (optionnel)
 */
function initPasswordValidation(formId, passwordInputId, confirmPasswordInputId = null) {
    const form = document.getElementById(formId);
    const passwordInput = document.getElementById(passwordInputId);
    const confirmPasswordInput = confirmPasswordInputId ? document.getElementById(confirmPasswordInputId) : null;

    if (!form || !passwordInput) {
        console.warn('[PasswordValidator] Form or password input not found');
        return;
    }

    form.addEventListener('submit', function(e) {
        const password = passwordInput.value;

        // Validation de la complexité
        const validation = validatePasswordComplexity(password);

        if (!validation.valid) {
            e.preventDefault();
            alert('Le mot de passe doit contenir :\n- ' + validation.errors.join('\n- '));
            return;
        }

        // Vérification de la confirmation si présente
        if (confirmPasswordInput) {
            const confirmPassword = confirmPasswordInput.value;
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return;
            }
        }
    });
}

// Export pour utilisation globale
window.validatePasswordComplexity = validatePasswordComplexity;
window.initPasswordValidation = initPasswordValidation;
