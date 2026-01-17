# Architecture CSS - Vite & Gourmand

## 📁 Structure des fichiers

```
assets/styles/
├── app.css                          # Point d'entrée principal (imports)
├── critical.css                     # CSS critique inline (anti-FOUC)
│
├── base/                            # Fondations
│   ├── _variables.css              # Variables CSS (charte Figma)
│   ├── _reset.css                  # Reset CSS & normalisation
│   └── _typography.css             # Styles typographiques
│
├── layout/                          # Structure & layout
│   └── _container.css              # Container, grids, flexbox
│
├── components/                      # Composants réutilisables
│   ├── _buttons.css                # Boutons (primary, secondary, outline)
│   ├── _badges.css                 # Badges (thèmes, régimes, statuts)
│   ├── _cards.css                  # Cards (menu, team, testimonial)
│   └── _forms.css                  # Formulaires (inputs, selects, checkbox)
│
└── utilities/                       # Classes utilitaires
    └── _utilities.css              # Marges, padding, display, etc.
```

## 🎨 Charte graphique

### Couleurs
- **Bordeaux** : `#8B0000` (var(--bordeaux)) - Couleur signature
- **Or** : `#D4AF37` (var(--gold)) - Accents premium
- **Beige** : `#F5F5DC` (var(--beige)) - Fond secondaire
- **Vert** : `#228B22` (var(--green)) - Succès, végétarien

### Typographie
- **Headings** : Playfair Display (serif) - var(--font-heading)
- **Body** : Open Sans (sans-serif) - var(--font-body)

### Espacements (système 8px)
- `--spacing-xs` : 8px
- `--spacing-sm` : 16px
- `--spacing-md` : 24px
- `--spacing-lg` : 32px
- `--spacing-xl` : 48px
- `--spacing-2xl` : 80px

## 🧩 Utilisation des composants

### Boutons

```html
<!-- Bouton principal -->
<button class="btn btn-primary">Commander</button>

<!-- Bouton secondaire (doré) -->
<button class="btn btn-secondary">Découvrir</button>

<!-- Bouton outline -->
<button class="btn btn-outline">Annuler</button>

<!-- Tailles -->
<button class="btn btn-primary btn-sm">Petit</button>
<button class="btn btn-primary btn-lg">Grand</button>
```

### Badges

```html
<!-- Badge thème (doré) -->
<span class="badge badge-theme">Noël</span>

<!-- Badge végétarien (vert) -->
<span class="badge badge-vegetarian">Végétarien</span>

<!-- Badge classique (bordeaux) -->
<span class="badge badge-classic">Classique</span>
```

### Cards

```html
<!-- Card Menu -->
<div class="menu-card">
    <div class="menu-card-image">
        <img src="..." alt="Menu">
        <div class="menu-card-badges">
            <span class="badge badge-theme">Noël</span>
        </div>
    </div>
    <div class="menu-card-content">
        <h3 class="menu-card-title">Menu Prestige</h3>
        <p class="menu-card-description">Description...</p>
        <div class="menu-card-footer">
            <div class="menu-card-price">
                <small>À partir de</small>
                <span class="price-value">120 €</span>
            </div>
            <a href="#" class="btn btn-view-details">Voir</a>
        </div>
    </div>
</div>
```

### Formulaires

```html
<!-- Groupe de formulaire -->
<div class="form-group">
    <label class="form-label" for="email">Email</label>
    <input type="email" id="email" class="form-control" placeholder="votre@email.com">
</div>

<!-- Select -->
<div class="form-group">
    <label class="form-label" for="theme">Thème</label>
    <select id="theme" class="form-control">
        <option>Choisir...</option>
    </select>
</div>
```

## 📐 Système de layout

### Container

```html
<!-- Container centré (max 1280px) -->
<div class="container">
    <!-- Contenu -->
</div>

<!-- Container fluide (pleine largeur) -->
<div class="container-fluid">
    <!-- Contenu -->
</div>
```

### Grilles

```html
<!-- Grid responsive -->
<div class="grid grid-cols-3 gap-lg">
    <div>Colonne 1</div>
    <div>Colonne 2</div>
    <div>Colonne 3</div>
</div>

<!-- Grid auto-fit -->
<div class="grid grid-auto-fit gap-md">
    <div>Item 1</div>
    <div>Item 2</div>
    <div>Item 3</div>
</div>
```

### Flexbox

```html
<div class="flex items-center justify-between">
    <div>Gauche</div>
    <div>Droite</div>
</div>
```

## 🛠️ Classes utilitaires

### Marges & Padding

```html
<!-- Marges -->
<div class="mt-3 mb-4">Marge top 24px, bottom 32px</div>
<div class="mx-auto">Centré horizontalement</div>

<!-- Padding -->
<div class="p-3">Padding 24px</div>
```

### Couleurs

```html
<!-- Fond -->
<div class="bg-bordeaux text-white">Texte blanc sur fond bordeaux</div>
<div class="bg-beige">Fond beige</div>

<!-- Texte -->
<p class="text-bordeaux">Texte bordeaux</p>
<p class="text-gold">Texte doré</p>
```

### Ombres

```html
<div class="shadow-sm">Ombre petite</div>
<div class="shadow-md">Ombre moyenne</div>
<div class="shadow-lg">Ombre grande</div>
```

### Typographie

```html
<p class="text-sm">Petit texte (14px)</p>
<p class="text-lg">Grand texte (18px)</p>
<p class="font-bold">Texte gras</p>
<p class="text-center">Texte centré</p>
```

## 🎯 Bonnes pratiques

### 1. Utiliser les variables CSS
```css
/* ✅ Bon */
.mon-composant {
    color: var(--bordeaux);
    padding: var(--spacing-md);
}

/* ❌ Mauvais */
.mon-composant {
    color: #8B0000;
    padding: 24px;
}
```

### 2. Préférer les composants aux utilities
```html
<!-- ✅ Bon - Composant réutilisable -->
<button class="btn btn-primary">Commander</button>

<!-- ❌ Mauvais - Utilities en vrac -->
<button class="bg-bordeaux text-white p-3 border-radius-sm">Commander</button>
```

### 3. Respecter la hiérarchie
1. **Base** : Variables, reset, typographie
2. **Layout** : Structure (container, grid)
3. **Components** : Composants réutilisables
4. **Utilities** : Classes d'ajustement final

### 4. Nommer les classes de manière descriptive
```css
/* ✅ Bon */
.menu-card-title { }
.btn-primary { }

/* ❌ Mauvais */
.mc-t { }
.btn1 { }
```

## 📱 Responsive Design

### Breakpoints
- **Mobile** : < 768px
- **Tablet** : 768px - 1023px
- **Desktop** : ≥ 1024px

### Classes responsive
```html
<!-- Masqué sur mobile -->
<div class="mobile-hidden">Visible uniquement sur desktop</div>

<!-- Visible uniquement sur mobile -->
<div class="mobile-only">Visible uniquement sur mobile</div>
```

## ⚡ Performance

### CSS Critique
Le fichier `critical.css` contient les styles essentiels chargés inline pour éviter le FOUC :
- Variables
- Reset de base
- Layout principal (header, footer, container)
- Styles critiques above-the-fold

### Import
Le fichier `app.css` importe tous les modules. Il est chargé de manière asynchrone après le CSS critique.

## 🔄 Migration depuis l'ancien CSS

L'ancien `app.css` a été sauvegardé dans `app.css.backup`. Migrer progressivement les styles legacy vers les modules appropriés.

## 📚 Références

- **Charte graphique** : `/Vite Gourmand FIGMA/CHARTE_GRAPHIQUE.md`
- **Maquettes** : `/Vite Gourmand FIGMA/src/`
- **Polices** : Google Fonts (Playfair Display + Open Sans)
