# CSS et JavaScript Frontend

## Gestion des assets avec AssetMapper

L'application utilise **Symfony AssetMapper** au lieu de Webpack ou Vite.js. Cela signifie :
- **Pas de Node.js** ni de `node_modules/`
- **Pas de build step** en developpement
- Les fichiers JS/CSS sont servis directement par Symfony
- Les librairies externes sont telecharges via `importmap:require`

### Configuration : importmap.php

Le fichier `importmap.php` a la racine declare tous les modules JavaScript et CSS disponibles :

```php
return [
    // Point d'entree principal (charge dans tous les templates)
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],

    // Modules JS importes a la demande
    'admin-recipe-illustrations' => [
        'path' => './assets/admin-recipe-illustrations.js',
        'entrypoint' => false,
    ],
    'table-search' => [
        'path' => './assets/table-search.js',
        'entrypoint' => false,
    ],
    // ...

    // CSS supplementaires (charges comme entrypoints)
    'admin-dashboard-styles' => [
        'path' => './assets/styles/admin/dashboard.css',
        'type' => 'css',
        'entrypoint' => true,
    ],
    // ...

    // Librairies externes
    '@hotwired/stimulus' => ['version' => '3.2.2'],
    '@hotwired/turbo' => ['version' => '7.3.0'],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
];
```

### Difference entre `entrypoint: true` et `false`

- **`entrypoint: true`** : Le module est charge automatiquement via `{{ importmap('app') }}` dans le template de base
- **`entrypoint: false`** : Le module doit etre importe manuellement dans le template qui en a besoin

### Charger les assets dans un template

```twig
{# base.html.twig - Charge TOUS les entrypoints #}
{{ importmap('app') }}

{# Dans un template specifique - Charger un module supplementaire #}
{% block javascripts %}
    {{ parent() }}
    {{ importmap('admin-recipe-illustrations') }}
{% endblock %}
```

---

## Fichiers JavaScript

### app.js - Point d'entree principal

**Fichier** : `assets/app.js`

C'est le fichier JavaScript principal charge sur **toutes les pages**. Il contient :

1. **Import des styles CSS** :
   ```javascript
   import './styles/app.css';
   ```

2. **Menu mobile (burger)** :
   - Toggle de la classe `.active` sur la navbar
   - Fermeture du menu au clic sur un lien

3. **Systeme d'icones SVG** :
   - Contient plus de 100 icones Feather sous forme de SVG inline
   - Fonction `replaceSvgIcons()` qui remplace les `<i data-icon="nom">` par le SVG correspondant
   - Appele au chargement de la page

4. **Initialisation des filtres de menu** :
   - Gradient du slider de prix
   - Event listeners sur les filtres (fallback si le JS AJAX ne charge pas)

---

### menu-filter-ajax.js - Filtrage AJAX des menus

**Fichier** : `assets/menu-filter-ajax.js`

Gere le filtrage dynamique des menus **sans rechargement de page**.

**Fonctionnement** :
1. Ecoute les changements sur les filtres (theme, regime, prix, personnes)
2. Collecte les valeurs de tous les filtres
3. Envoie une requete `fetch()` vers `/api/menus/filter`
4. Recoit les menus filtres en JSON
5. Reconstruit les cartes de menus dans le DOM
6. Affiche un spinner pendant le chargement

**Debouncing** : Les sliders et champs texte utilisent un debounce de **300ms** pour eviter d'envoyer trop de requetes pendant que l'utilisateur ajuste les valeurs.

```javascript
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}
```

Voir [AJAX_FILTERING.md](AJAX_FILTERING.md) pour la documentation complete.

---

### admin-recipe-illustrations.js - Upload d'illustrations

**Fichier** : `assets/admin-recipe-illustrations.js`

Gere l'upload dynamique d'illustrations de recettes dans le formulaire admin.

**Fonctionnalites** :
- Ajout/suppression dynamique de champs de fichier (CollectionType Symfony)
- Preview de l'image avant upload
- Gestion du texte alternatif

---

### confirmation-dialog.js - Dialogs de confirmation

**Fichier** : `assets/confirmation-dialog.js`

Affiche une modale de confirmation avant les actions destructives (suppression).

```javascript
// Utilisation dans le HTML
<button data-confirm="Etes-vous sur de vouloir supprimer ce menu ?">
    Supprimer
</button>
```

Empeche l'envoi du formulaire si l'utilisateur clique "Annuler".

---

### password-toggle.js - Afficher/masquer le mot de passe

**Fichier** : `assets/password-toggle.js`

Ajoute un bouton oeil a cote des champs de mot de passe pour basculer entre le mode texte et le mode masque.

---

### password-validator.js - Validation en temps reel

**Fichier** : `assets/password-validator.js`

Valide les criteres de mot de passe **en temps reel** pendant la saisie :
- Minimum 10 caracteres
- Au moins une majuscule
- Au moins une minuscule
- Au moins un chiffre
- Au moins un caractere special

Affiche un indicateur visuel (vert/rouge) pour chaque critere.

Utilise sur la page de creation d'employe (`/admin/users/create-employee`).

---

### table-search.js - Recherche dans les tableaux

**Fichier** : `assets/table-search.js`

Ajoute un champ de recherche au-dessus des tableaux admin pour filtrer les lignes en temps reel (cote client, pas de requete serveur).

```javascript
// Filtre les lignes d'un tableau en fonction du texte saisi
searchInput.addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
```

---

### table-sort.js - Tri des colonnes

**Fichier** : `assets/table-sort.js`

Permet de trier les colonnes d'un tableau en cliquant sur l'en-tete. Le tri se fait cote client (pas de requete serveur).

---

### bootstrap.js - Configuration Stimulus

**Fichier** : `assets/bootstrap.js`

Configure le framework **Stimulus** (de Hotwired). Stimulus permet d'ajouter du comportement JavaScript a des elements HTML via des attributs `data-controller`.

```javascript
import { startStimulusApp } from '@symfony/stimulus-bundle';
const app = startStimulusApp();
```

---

## Organisation du CSS

### Structure des fichiers

```
assets/styles/
├── app.css                    # Feuille principale (importe tout)
├── critical.css               # CSS critique (inline dans <head>)
│
├── base/                      # Fondations
│   ├── _reset.css             # Reset CSS (normalise les navigateurs)
│   ├── _typography.css        # Polices, tailles de texte
│   └── _variables.css         # Variables CSS (couleurs, espacements)
│
├── components/                # Composants reutilisables
│   ├── _buttons.css           # Styles des boutons
│   ├── _forms.css             # Styles des formulaires
│   ├── _cards.css             # Cartes (menus, recettes)
│   ├── _badges.css            # Badges de statut
│   ├── _modal.css             # Modales de confirmation
│   └── _navbar.css            # Barre de navigation
│
├── layout/                    # Structure de page
│   ├── _container.css         # Conteneur principal
│   ├── _sections.css          # Sections de page
│   └── _footer.css            # Pied de page
│
├── pages/                     # Styles par page
│   ├── admin-dashboard.css    # Dashboard admin
│   ├── admin-orders.css       # Liste commandes admin
│   ├── auth.css               # Pages connexion/inscription
│   ├── menu.css               # Catalogue de menus
│   ├── order.css              # Pages de commande
│   └── ...
│
├── admin/                     # Styles du back-office
│   ├── dashboard.css
│   ├── menu-form.css
│   ├── menus-list.css
│   ├── orders.css
│   ├── opening-schedule.css
│   ├── reviews.css
│   └── themes-list.css
│
└── utilities/                 # Classes utilitaires
    └── _utilities.css         # Marges, padding, display, etc.
```

### Variables CSS

**Fichier** : `assets/styles/base/_variables.css`

L'application utilise des **variables CSS** (custom properties) pour centraliser les couleurs et espacements :

```css
:root {
    /* Couleurs principales */
    --color-primary: #...;
    --color-secondary: #...;
    --color-success: #...;
    --color-danger: #...;
    --color-warning: #...;

    /* Espacements */
    --spacing-xs: 0.25rem;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 2rem;

    /* Typographie */
    --font-family: '...', sans-serif;
    --font-size-base: 1rem;
}
```

### CSS Critique

**Fichier** : `assets/styles/critical.css`

Ce fichier contient le CSS necessaire pour le **premier rendu** de la page. Il est insere directement dans le `<head>` HTML (inline) pour accelerer l'affichage initial (pas de requete HTTP supplementaire).

### Responsive Design

L'application est **responsive** (adaptee mobile/tablette/desktop) grace a :
- Des **media queries** dans les fichiers CSS
- Un **menu burger** pour mobile (gere par `app.js`)
- Des **grilles flexbox/grid** adaptatives

---

## Hotwired Turbo et Stimulus

### Turbo

**Turbo** (inclus via `@hotwired/turbo`) accelere la navigation en remplacant le contenu de la page sans rechargement complet du navigateur. Quand un lien est clique, Turbo :
1. Intercepte le clic
2. Fait une requete AJAX
3. Remplace le `<body>` de la page
4. Met a jour l'URL dans la barre d'adresse

Resultat : navigation plus rapide, sans "flash blanc" entre les pages.

### Stimulus

**Stimulus** (inclus via `@hotwired/stimulus`) permet d'attacher du comportement JavaScript a des elements HTML via des attributs :

```html
<div data-controller="hello">
    <input data-hello-target="name" type="text">
    <button data-action="click->hello#greet">Salut</button>
</div>
```

Les controleurs Stimulus sont dans `assets/controllers/`.

---

## Voir aussi

- [TEMPLATES.md](TEMPLATES.md) - Templates Twig
- [AJAX_FILTERING.md](AJAX_FILTERING.md) - Filtrage AJAX
- [ARCHITECTURE.md](ARCHITECTURE.md) - Vue d'ensemble
