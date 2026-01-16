# Guide des Composants - Vite & Gourmand

## 🎨 Aperçu rapide des composants disponibles

### 🔘 Boutons

| Classe | Usage | Exemple |
|--------|-------|---------|
| `.btn .btn-primary` | Action principale | Commander, Réserver |
| `.btn .btn-secondary` | Action secondaire doré | Découvrir, En savoir plus |
| `.btn .btn-outline` | Action tertiaire | Annuler, Retour |
| `.btn .btn-success` | Validation | Confirmer, Valider |
| `.btn .btn-danger` | Suppression | Supprimer, Annuler commande |
| `.btn .btn-sm` | Petit bouton | Actions secondaires |
| `.btn .btn-lg` | Grand bouton | CTA principal |
| `.btn .btn-block` | Pleine largeur | Formulaires mobiles |

**Boutons spéciaux :**
- `.btn-user` : Bouton compte utilisateur (header)
- `.btn-account` : Bouton profil avec avatar
- `.btn-admin` : Bouton administration (doré)
- `.btn-footer` : Bouton dans le footer
- `.btn-view-details` : "Voir détails" catalogue

### 🏷️ Badges

| Classe | Couleur | Usage |
|--------|---------|-------|
| `.badge .badge-theme` | Doré | Thèmes de menu (Noël, Pâques) |
| `.badge .badge-bordeaux` | Bordeaux | Mise en avant |
| `.badge .badge-vegetarian` | Vert | Végétarien, Vegan |
| `.badge .badge-classic` | Bordeaux | Menu classique |
| `.badge .badge-success` | Vert | Succès, Livré |
| `.badge .badge-warning` | Orange | En attente, En cours |
| `.badge .badge-danger` | Rouge | Erreur, Annulé |
| `.badge .badge-info` | Bleu | Information |

**Badges de statut commande :**
- `.badge-confirmed` : Commande confirmée (doré)
- `.badge-delivered` : Commande livrée (vert)
- `.badge-pending` : En attente (orange)
- `.badge-cancelled` : Annulée (rouge)

### 🃏 Cards

#### Card de base
```html
<div class="card">
    <!-- Contenu -->
</div>
```
- Effet hover : élévation
- Shadow medium
- Border-radius 12px

#### Card Menu (Design Figma)
```html
<div class="menu-card">
    <div class="menu-card-image">
        <img src="..." alt="Menu">
        <div class="menu-card-badges">
            <span class="badge badge-theme">Noël</span>
        </div>
    </div>
    <div class="menu-card-content">
        <h3 class="menu-card-title">Menu Prestige</h3>
        <p class="menu-card-description">Description du menu...</p>
        <div class="menu-card-tags">
            <span class="badge badge-vegetarian">Végétarien</span>
        </div>
        <div class="menu-card-info">
            <span>👥 10 personnes</span>
            <span>🍽️ 4 plats</span>
        </div>
        <div class="menu-card-footer">
            <div class="menu-card-price">
                <small>À partir de</small>
                <span class="price-value">120 €</span>
            </div>
            <a href="#" class="btn btn-view-details">Voir détails</a>
        </div>
    </div>
</div>
```

#### Card Statistique
```html
<div class="stat-card">
    <div class="stat-icon">🏆</div>
    <div class="stat-number">25</div>
    <p>Années d'expérience</p>
</div>
```

#### Card Équipe
```html
<div class="team-card">
    <img src="..." alt="Membre" class="team-image">
    <div class="team-overlay"></div>
    <!-- Info membre -->
</div>
```

#### Card Témoignage
```html
<div class="testimonial-card">
    <div class="testimonial-stars">
        <span class="star-icon">⭐</span>
        <span class="star-icon">⭐</span>
        <span class="star-icon">⭐</span>
        <span class="star-icon">⭐</span>
        <span class="star-icon">⭐</span>
    </div>
    <p class="testimonial-text">"Excellent service..."</p>
    <p class="testimonial-author">Marie Dupont</p>
    <p class="testimonial-event">Mariage - Juin 2024</p>
</div>
```

### 📝 Formulaires

#### Input de base
```html
<div class="form-group">
    <label class="form-label" for="nom">Nom</label>
    <input type="text" id="nom" class="form-control" placeholder="Votre nom">
</div>
```

#### Select
```html
<div class="form-group">
    <label class="form-label" for="theme">Thème</label>
    <select id="theme" class="form-control">
        <option value="">Choisir un thème</option>
        <option value="noel">Noël</option>
        <option value="paques">Pâques</option>
    </select>
</div>
```

#### Textarea
```html
<div class="form-group">
    <label class="form-label" for="message">Message</label>
    <textarea id="message" class="form-control" placeholder="Votre message"></textarea>
</div>
```

#### Checkbox
```html
<div class="form-check">
    <input type="checkbox" id="rgpd" class="form-check-input">
    <label class="form-check-label" for="rgpd">J'accepte les CGU</label>
</div>
```

#### Radio
```html
<div class="form-check">
    <input type="radio" id="livraison" name="service" class="form-check-input">
    <label class="form-check-label" for="livraison">Livraison</label>
</div>
```

#### États
- `.form-control.is-invalid` : Erreur de validation
- `.form-control:disabled` : Désactivé
- `.form-error` : Message d'erreur
- `.form-help` : Texte d'aide

#### Filtres catalogue (Style Figma)
```html
<div class="filter-item">
    <label class="form-label">Recherche</label>
    <input type="text" class="filter-input" placeholder="Rechercher...">
</div>

<div class="filter-item">
    <label class="form-label">Thème</label>
    <select class="filter-select">
        <option>Tous les thèmes</option>
    </select>
</div>

<!-- Slider de prix -->
<div class="filter-item">
    <label class="form-label">Prix maximum</label>
    <input type="range" class="price-slider" min="0" max="500" value="250">
</div>

<!-- Boutons filtres régime -->
<button class="regime-filter-btn active">Tous</button>
<button class="regime-filter-btn">Végétarien</button>
<button class="regime-filter-btn">Classique</button>
```

### 📦 Layout

#### Container
```html
<!-- Container centré (1280px max) -->
<div class="container">
    <!-- Contenu -->
</div>

<!-- Container fluide -->
<div class="container-fluid">
    <!-- Contenu -->
</div>
```

#### Grilles
```html
<!-- 3 colonnes égales -->
<div class="grid grid-cols-3 gap-lg">
    <div>Col 1</div>
    <div>Col 2</div>
    <div>Col 3</div>
</div>

<!-- Auto-fit responsive -->
<div class="grid grid-auto-fit gap-md">
    <div>Item 1</div>
    <div>Item 2</div>
    <div>Item 3</div>
</div>

<!-- Grid avec gaps personnalisés -->
<div class="grid grid-cols-2 gap-xs">...</div>  <!-- Gap 8px -->
<div class="grid grid-cols-2 gap-sm">...</div>  <!-- Gap 16px -->
<div class="grid grid-cols-2 gap-md">...</div>  <!-- Gap 24px -->
<div class="grid grid-cols-2 gap-lg">...</div>  <!-- Gap 32px -->
<div class="grid grid-cols-2 gap-xl">...</div>  <!-- Gap 48px -->
```

#### Flexbox
```html
<div class="flex items-center justify-between">
    <div>Gauche</div>
    <div>Droite</div>
</div>

<div class="flex flex-col items-center">
    <div>Item 1</div>
    <div>Item 2</div>
</div>
```

#### Sections
```html
<!-- Section standard (80px padding desktop, 48px mobile) -->
<section class="section">
    <div class="container">
        <!-- Contenu -->
    </div>
</section>

<!-- Section small (48px padding desktop, 32px mobile) -->
<section class="section-sm">
    <div class="container">
        <!-- Contenu -->
    </div>
</section>
```

### 🚨 Alerts

```html
<!-- Succès -->
<div class="alert alert-success">
    Commande confirmée avec succès !
</div>

<!-- Danger -->
<div class="alert alert-danger">
    Une erreur est survenue.
</div>

<!-- Warning -->
<div class="alert alert-warning">
    Attention, stock limité.
</div>

<!-- Info -->
<div class="alert alert-info">
    Nouvelle fonctionnalité disponible.
</div>
```

### 🎯 Éléments spéciaux

#### Séparateur doré
```html
<h2>Nos Menus</h2>
<div class="divider-gold"></div>
```

#### Hero Section
```html
<section class="hero-section" style="background-image: url('...')">
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="hero-content">
            <h1>Vite & Gourmand</h1>
            <p>Traiteur gastronomique à Bordeaux</p>
            <button class="btn btn-secondary btn-lg">Découvrir nos menus</button>
        </div>
    </div>
</section>
```

#### Menu Filters (Catalogue)
```html
<div class="menu-filters">
    <div class="filters-grid">
        <div class="filter-item">
            <label class="form-label">Recherche</label>
            <input type="text" class="filter-input" placeholder="Rechercher un menu...">
        </div>
        <!-- Autres filtres -->
    </div>
</div>
```

## 🎨 Utilities les plus utilisées

### Espacement
- `.mt-1` à `.mt-5` : Margin top
- `.mb-1` à `.mb-5` : Margin bottom
- `.mx-auto` : Centrer horizontalement
- `.p-1` à `.p-5` : Padding

### Couleurs
- `.bg-bordeaux`, `.bg-gold`, `.bg-beige` : Fonds
- `.text-bordeaux`, `.text-gold`, `.text-white` : Textes

### Affichage
- `.d-none`, `.d-block`, `.d-flex` : Display
- `.mobile-hidden`, `.mobile-only` : Responsive
- `.w-full`, `.h-full` : Width/Height 100%

### Typographie
- `.text-xs` à `.text-6xl` : Tailles
- `.font-light`, `.font-bold` : Poids
- `.text-center`, `.text-left`, `.text-right` : Alignement

### Autres
- `.shadow-sm`, `.shadow-md`, `.shadow-lg` : Ombres
- `.border-radius-sm`, `.border-radius-md` : Bordures arrondies
- `.cursor-pointer` : Curseur pointeur
- `.opacity-50`, `.opacity-75` : Opacité

## 📱 Classes Responsive

```html
<!-- Masqué sur mobile, visible sur desktop -->
<div class="mobile-hidden">Contenu desktop</div>

<!-- Visible seulement sur mobile -->
<div class="mobile-only">Contenu mobile</div>

<!-- Masqué sur tablette -->
<div class="tablet-hidden">Contenu non-tablette</div>

<!-- Grid responsive automatique -->
<div class="grid grid-cols-3">
    <!-- 1 col mobile, 2 cols tablet, 3 cols desktop -->
</div>
```

## 🔗 Combinaisons courantes

### Bouton CTA principal
```html
<button class="btn btn-primary btn-lg shadow-lg hover-lift">
    Commander maintenant
</button>
```

### Card produit avec hover
```html
<div class="card shadow-md hover-lift transition-base">
    <!-- Contenu -->
</div>
```

### Section centrée avec titre
```html
<section class="section bg-beige">
    <div class="container text-center">
        <h2 class="text-bordeaux mb-2">Nos Menus</h2>
        <div class="divider-gold"></div>
        <p class="text-dark-text mt-3 mx-auto" style="max-width: 600px;">
            Description...
        </p>
    </div>
</section>
```
