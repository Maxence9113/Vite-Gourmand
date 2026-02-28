# Templates Twig et Frontend

## Qu'est-ce que Twig ?

**Twig** est le moteur de templates de Symfony. Il permet de generer du HTML en melangeant du HTML statique avec des variables et de la logique simple.

### Syntaxe de base

```twig
{# Ceci est un commentaire Twig #}

{# Afficher une variable #}
{{ variable }}

{# Logique (conditions, boucles) #}
{% if condition %}...{% endif %}
{% for item in items %}...{% endfor %}

{# Filtre : transformer une valeur #}
{{ prix / 100 | number_format(2, ',', ' ') }}
```

---

## Heritage de templates (extends)

Twig utilise un systeme d'**heritage** : un template enfant etend un template parent et remplit les "blocs" definis par le parent.

### Schema d'heritage

```
base.html.twig (layout principal)
├── _partials/_navbar.html.twig (inclus)
├── _partials/_footer.html.twig (inclus)
│
├── home/index.html.twig (page d'accueil)
├── menu/catalog.html.twig (catalogue)
├── login/index.html.twig (connexion)
├── ...
│
└── admin/base_admin.html.twig (layout admin, etend base.html.twig)
    ├── admin/index.html.twig (dashboard)
    ├── admin/menus/index.html.twig (liste menus)
    ├── admin/orders/show.html.twig (detail commande)
    └── ...

emails/base_email.html.twig (layout email, independant)
├── emails/welcome.html.twig
├── emails/order_confirmation.html.twig
└── ...
```

---

## Layout principal : base.html.twig

**Fichier** : `templates/base.html.twig`

Ce template est le **squelette HTML** de toutes les pages publiques et utilisateur.

### Structure

```twig
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{% block title %}Vite & Gourmand{% endblock %}</title>

    {# CSS critique inline pour un chargement rapide #}
    <style>{% block critical_css %}...{% endblock %}</style>

    {# CSS et JS via AssetMapper #}
    {% block stylesheets %}
        {{ importmap('app') }}
    {% endblock %}
</head>
<body>
    {# Barre de navigation #}
    {% include '_partials/_navbar.html.twig' %}

    {# Messages flash (succes, erreur, info) #}
    {% for type, messages in app.flashes %}
        {% for message in messages %}
            <div class="flash flash-{{ type }}">{{ message }}</div>
        {% endfor %}
    {% endfor %}

    {# Contenu de la page (rempli par les templates enfants) #}
    {% block body %}{% endblock %}

    {# Pied de page #}
    {% include '_partials/_footer.html.twig' %}

    {# JavaScript supplementaire #}
    {% block javascripts %}{% endblock %}
</body>
</html>
```

### Blocs disponibles

| Bloc | Usage |
|---|---|
| `title` | Titre de la page (`<title>`) |
| `critical_css` | CSS inline pour le rendu initial |
| `stylesheets` | Feuilles de style additionnelles |
| `body` | Contenu principal de la page |
| `javascripts` | Scripts JavaScript additionnels |

### Utilisation dans un template enfant

```twig
{# templates/menu/catalog.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Nos Menus - Vite & Gourmand{% endblock %}

{% block body %}
    <h1>Nos Menus</h1>
    {# Contenu de la page ici #}
{% endblock %}

{% block javascripts %}
    {{ parent() }}
    {# Scripts specifiques a cette page #}
    <script type="module" src="{{ asset('menu-filter-ajax.js') }}"></script>
{% endblock %}
```

---

## Layout admin : base_admin.html.twig

**Fichier** : `templates/admin/base_admin.html.twig`

Ce template **etend** `base.html.twig` et ajoute une barre laterale (sidebar) de navigation pour le back-office.

### Structure

```twig
{% extends 'base.html.twig' %}

{% block body %}
<div class="admin-layout">
    {# Sidebar avec liens de navigation admin #}
    <aside class="admin-sidebar">
        <nav>
            <a href="{{ path('admin_dashboard') }}">Dashboard</a>
            <a href="{{ path('admin_menu_index') }}">Menus</a>
            <a href="{{ path('admin_recipe_index') }}">Recettes</a>
            <a href="{{ path('admin_orders_index') }}">Commandes</a>
            {# ... autres liens #}

            {% if is_granted('ROLE_ADMIN') %}
                <a href="{{ path('admin_users_index') }}">Utilisateurs</a>
            {% endif %}
        </nav>
    </aside>

    {# Contenu admin #}
    <main class="admin-content">
        {% block admin_body %}{% endblock %}
    </main>
</div>
{% endblock %}
```

### Usage dans un template admin

```twig
{# templates/admin/menus/index.html.twig #}
{% extends 'admin/base_admin.html.twig' %}

{% block admin_body %}
    <h1>Gestion des menus</h1>
    {# Contenu #}
{% endblock %}
```

---

## Organisation des templates

### Pages publiques

| Template | Route | Description |
|---|---|---|
| `home/index.html.twig` | `/` | Page d'accueil avec derniers avis |
| `menu/catalog.html.twig` | `/menus` | Catalogue avec filtres AJAX |
| `menu/show.html.twig` | `/menus/{id}` | Detail d'un menu |
| `login/index.html.twig` | `/connexion` | Formulaire de connexion |
| `register/index.html.twig` | `/inscription` | Formulaire d'inscription |
| `contact/index.html.twig` | `/contact` | Formulaire de contact |
| `review/index.html.twig` | `/avis` | Liste des avis valides |
| `review/new.html.twig` | `/avis/nouveau/{id}` | Laisser un avis |
| `password_reset/request.html.twig` | `/password-reset/request` | Demande de reset |
| `password_reset/reset.html.twig` | `/password-reset/reset/{token}` | Reset mot de passe |
| `legal/cgv.html.twig` | `/conditions-generales-vente` | CGV |
| `legal/privacy.html.twig` | `/politique-confidentialite` | Politique de confidentialite |
| `legal/mentions.html.twig` | `/mentions-legales` | Mentions legales |

### Espace utilisateur

| Template | Route | Description |
|---|---|---|
| `account/index.html.twig` | `/compte` | Dashboard du compte |
| `account/edit.html.twig` | `/compte/modifier-profil` | Modifier profil |
| `account/password.html.twig` | `/compte/modifier-mot-de-passe` | Changer mot de passe |
| `account/addresses/index.html.twig` | `/compte/adresses` | Liste des adresses |
| `account/addresses/form.html.twig` | `/compte/adresses/nouvelle` | Ajouter/modifier adresse |
| `order/index.html.twig` | `/compte/commandes` | Mes commandes |
| `order/new.html.twig` | `/commande/nouvelle/{id}` | Passer commande |
| `order/show.html.twig` | `/compte/commandes/{id}` | Detail commande |

### Back-office admin

| Template | Route | Description |
|---|---|---|
| `admin/index.html.twig` | `/admin` | Dashboard |
| `admin/menus/index.html.twig` | `/admin/menus` | Liste des menus |
| `admin/menus/form.html.twig` | `/admin/menus/new` | Creer/modifier menu |
| `admin/recipes/index.html.twig` | `/admin/recipes` | Liste des recettes |
| `admin/recipes/form.html.twig` | `/admin/recipes/new` | Creer/modifier recette |
| `admin/orders/index.html.twig` | `/admin/orders` | Liste des commandes |
| `admin/orders/show.html.twig` | `/admin/orders/{id}` | Detail + gestion |
| `admin/orders/_status_badge.html.twig` | - | Partial : badge de statut |
| `admin/reviews/index.html.twig` | `/admin/avis` | Moderation des avis |
| `admin/user/index.html.twig` | `/admin/users` | Liste utilisateurs |
| `admin/user/edit.html.twig` | `/admin/users/{id}/edit` | Modifier utilisateur |
| `admin/user/create_employee.html.twig` | `/admin/users/create-employee` | Creer employe |
| `admin/order_stats/index.html.twig` | `/admin/stats` | Statistiques |
| `admin/opening_schedule/index.html.twig` | `/admin/horaires` | Horaires |

### Composants reutilisables

| Template | Utilise par | Description |
|---|---|---|
| `_partials/_navbar.html.twig` | `base.html.twig` | Barre de navigation |
| `_partials/_footer.html.twig` | `base.html.twig` | Pied de page |
| `components/opening_hours.html.twig` | Plusieurs pages | Affichage des horaires |
| `forms/custom_form_theme.html.twig` | Tous les formulaires | Theme de formulaire personnalise |

---

## Messages flash

Les messages flash sont des messages temporaires affiches a l'utilisateur apres une action (creation, modification, erreur...).

### Cote controleur

```php
$this->addFlash('success', 'Le menu a ete cree avec succes !');
$this->addFlash('error', 'Une erreur est survenue.');
$this->addFlash('info', 'Votre avis sera publie apres validation.');
```

### Cote template (base.html.twig)

```twig
{% for type, messages in app.flashes %}
    {% for message in messages %}
        <div class="flash flash-{{ type }}">{{ message }}</div>
    {% endfor %}
{% endfor %}
```

Types disponibles : `success` (vert), `error` (rouge), `info` (bleu), `warning` (orange).

---

## Fonctions Twig utiles

### path() - Generer une URL

```twig
{# Lien vers une route nommee #}
<a href="{{ path('app_menu_catalog') }}">Voir les menus</a>

{# Avec un parametre #}
<a href="{{ path('app_menu_show', {id: menu.id}) }}">{{ menu.name }}</a>
```

### asset() - Fichiers statiques

```twig
{# Image uploadee #}
<img src="{{ asset(menu.illustration) }}" alt="{{ menu.textAlt }}">

{# Fichier JS/CSS #}
<script src="{{ asset('table-search.js') }}"></script>
```

### importmap() - Charger les modules JS

```twig
{# Charge le point d'entree principal (app.js + CSS) #}
{{ importmap('app') }}
```

### is_granted() - Verification des droits

```twig
{% if is_granted('ROLE_ADMIN') %}
    <a href="{{ path('admin_users_index') }}">Gerer les utilisateurs</a>
{% endif %}
```

### app.user - Utilisateur connecte

```twig
{% if app.user %}
    Bonjour {{ app.user.firstname }} !
{% else %}
    <a href="{{ path('app_login') }}">Se connecter</a>
{% endif %}
```

---

## Extension Twig personnalisee

### OpeningScheduleExtension

**Fichier** : `src/Twig/OpeningScheduleExtension.php`

Ajoute un filtre Twig pour formater les horaires d'ouverture.

```twig
{# Utilisation dans un template #}
{{ schedule | format_opening_hours }}
```

---

## Voir aussi

- [ASSETS.md](ASSETS.md) - CSS et JavaScript
- [FORMS_VALIDATION.md](FORMS_VALIDATION.md) - Formulaires
- [ARCHITECTURE.md](ARCHITECTURE.md) - Vue d'ensemble
