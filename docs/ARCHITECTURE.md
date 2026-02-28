# Architecture de l'application Vite & Gourmand

## Vue d'ensemble

Vite & Gourmand est une application web de **gestion de traiteur/restauration** construite avec **Symfony 6.4**. Elle permet aux clients de consulter des menus, passer des commandes de traiteur, et laisser des avis. Les employés et administrateurs gèrent les menus, recettes, commandes et opérations via un back-office.

## Stack technique

| Composant | Technologie | Version |
|---|---|---|
| **Framework PHP** | Symfony | 6.4 |
| **PHP** | PHP | 8.1+ |
| **Base de données relationnelle** | MariaDB / MySQL | - |
| **Base de données NoSQL** | MongoDB | - |
| **ORM** | Doctrine ORM | - |
| **ODM MongoDB** | Doctrine MongoDB ODM | - |
| **Template engine** | Twig | - |
| **Gestion des assets** | Symfony AssetMapper | - |
| **JavaScript** | Vanilla JS + Hotwired Stimulus/Turbo | - |
| **CSS** | CSS natif (pas de framework CSS) | - |

### Pourquoi ces choix ?

- **Symfony 6.4** : Framework PHP robuste avec un écosystème mature, adapté aux projets professionnels
- **AssetMapper** (au lieu de Webpack/Vite.js) : Pas besoin de Node.js, les assets sont compilés nativement par Symfony. Plus simple à déployer
- **Pas de Bootstrap/Tailwind** : CSS écrit à la main pour une meilleure compréhension et un contrôle total sur le design
- **MongoDB** : Utilisé uniquement pour les statistiques de commandes (démonstration d'une base NoSQL)
- **MariaDB** : Base de données relationnelle principale pour toutes les données métier

## Structure des dossiers

```
ViteGourmand/
├── assets/                     # Code frontend (JS + CSS)
│   ├── app.js                  # Point d'entrée principal JavaScript
│   ├── bootstrap.js            # Configuration Stimulus
│   ├── styles/                 # Feuilles de style CSS organisées
│   │   ├── app.css             # Feuille principale (importe les autres)
│   │   ├── critical.css        # CSS critique (chargé en priorité)
│   │   ├── base/               # Reset, typographie, variables CSS
│   │   ├── components/         # Boutons, formulaires, cartes, badges, etc.
│   │   ├── layout/             # Conteneurs, footer, sections
│   │   ├── pages/              # Styles spécifiques par page
│   │   ├── admin/              # Styles du back-office
│   │   └── utilities/          # Classes utilitaires
│   └── [autres fichiers JS]    # Modules JS spécifiques (filtres, upload, etc.)
│
├── config/                     # Configuration Symfony
│   ├── packages/               # Configuration par bundle
│   │   ├── doctrine.yaml       # ORM MariaDB
│   │   ├── doctrine_mongodb.yaml # ODM MongoDB
│   │   ├── security.yaml       # Authentification et autorisation
│   │   ├── twig.yaml           # Moteur de templates
│   │   ├── mailer.yaml         # Service d'emails
│   │   └── ...
│   ├── routes.yaml             # Auto-découverte des routes
│   └── services.yaml           # Injection de dépendances
│
├── docs/                       # Documentation du projet (tu es ici !)
│
├── fixtures/                   # Données de test (images pour les fixtures)
│   └── images/                 # Images utilisées par les DataFixtures
│
├── migrations/                 # Migrations de base de données
│
├── public/                     # Racine web (accessible publiquement)
│   ├── index.php               # Point d'entrée Symfony
│   └── uploads/                # Fichiers uploadés
│       ├── recipe_illustrations/   # Images de recettes
│       └── menu_illustrations/     # Images de menus
│
├── src/                        # Code source PHP
│   ├── Command/                # Commandes Symfony (CLI)
│   ├── Controller/             # Contrôleurs (routes HTTP)
│   │   ├── Admin/              # Back-office (ROLE_EMPLOYEE+)
│   │   ├── Api/                # Endpoints JSON (AJAX)
│   │   └── Public/             # Pages publiques
│   ├── DataFixtures/           # Générateurs de données de test
│   ├── Document/               # Documents MongoDB
│   ├── Entity/                 # Entités Doctrine (modèles)
│   ├── Enum/                   # Enums PHP 8.1
│   ├── Form/                   # Formulaires Symfony
│   ├── Repository/             # Requêtes base de données
│   ├── Security/               # Vérifications d'authentification
│   ├── Service/                # Logique métier
│   ├── Twig/                   # Extensions Twig personnalisées
│   └── Validator/              # Validateurs personnalisés
│
├── templates/                  # Templates Twig (vues)
│   ├── base.html.twig          # Layout principal
│   ├── _partials/              # Composants réutilisables (navbar, footer)
│   ├── admin/                  # Templates du back-office
│   ├── account/                # Espace utilisateur
│   ├── emails/                 # Templates d'emails
│   ├── menu/                   # Catalogue de menus
│   ├── order/                  # Commandes
│   └── ...
│
├── tests/                      # Tests PHPUnit
├── var/                        # Cache et logs (auto-généré)
├── vendor/                     # Dépendances Composer (auto-généré)
│
├── .env                        # Variables d'environnement par défaut
├── composer.json               # Dépendances PHP
└── importmap.php               # Configuration AssetMapper
```

## Architecture MVC

L'application suit le pattern **MVC** (Model-View-Controller) de Symfony :

```
                    ┌─────────────┐
   Navigateur ────> │  Controller │ ────> Response (HTML/JSON)
                    └──────┬──────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
              v            v            v
        ┌──────────┐ ┌──────────┐ ┌──────────┐
        │  Entity  │ │ Service  │ │   Form   │
        │ (Model)  │ │ (Logic)  │ │ (Input)  │
        └────┬─────┘ └──────────┘ └──────────┘
             │
             v
        ┌──────────┐     ┌──────────┐
        │Repository│ ──> │ Database │
        └──────────┘     └──────────┘

              ┌──────────┐
              │   Twig   │ ──> HTML (View)
              │ Template │
              └──────────┘
```

### Flux d'une requête typique

1. **Le navigateur** envoie une requête HTTP (ex: `GET /menus`)
2. **Le routeur Symfony** trouve le contrôleur correspondant (via les attributs `#[Route]`)
3. **Le contrôleur** :
   - Utilise les **repositories** pour lire les données en base
   - Utilise les **services** pour exécuter la logique métier
   - Utilise les **formulaires** pour traiter les entrées utilisateur
4. **Le contrôleur** passe les données au **template Twig**
5. **Twig** génère le HTML et le renvoie au navigateur

## Les 3 zones de l'application

### 1. Zone publique (tout le monde)

- **Pages d'information** : Accueil, contact, mentions légales, CGV, politique de confidentialité
- **Catalogue de menus** : Liste filtrable avec AJAX, détail d'un menu
- **Avis clients** : Liste des avis validés
- **Inscription / Connexion** : Création de compte et authentification

### 2. Espace utilisateur (ROLE_USER)

- **Mon compte** : Profil, mot de passe, adresses de livraison
- **Mes commandes** : Liste, détail, création, annulation
- **Laisser un avis** : Sur une commande terminée

### 3. Back-office admin (ROLE_EMPLOYEE / ROLE_ADMIN)

- **Dashboard** : Vue d'ensemble avec statistiques
- **Gestion des menus** : CRUD complet avec upload d'images
- **Gestion des recettes** : CRUD avec illustrations multiples
- **Gestion des commandes** : Changement de statut, annulation, suivi matériel
- **Gestion des avis** : Modération (validation/rejet)
- **Statistiques** : Graphiques et KPIs (données MongoDB)
- **Gestion des utilisateurs** : (ROLE_ADMIN uniquement) Création employés, rôles

## Concepts Symfony importants utilisés

### Injection de dépendances (services.yaml)

Symfony injecte automatiquement les services nécessaires dans les constructeurs et méthodes des contrôleurs.

```php
// Le contrôleur reçoit automatiquement les bonnes instances
public function index(MenuRepository $menuRepository): Response
{
    $menus = $menuRepository->findAll();
    // ...
}
```

**Fichier** : `config/services.yaml` - définit les paramètres et la configuration des services.

### Attributs de route (#[Route])

Les routes sont déclarées directement sur les méthodes des contrôleurs :

```php
#[Route('/menus', name: 'app_menu_catalog')]
public function catalog(): Response { ... }
```

Symfony les découvre automatiquement grâce à `config/routes.yaml`.

### AssetMapper (importmap.php)

Au lieu de Webpack/Vite.js, l'application utilise l'**AssetMapper** de Symfony :
- Le fichier `importmap.php` déclare les modules JavaScript et CSS disponibles
- Les modules sont importés dans les templates avec `{{ importmap('app') }}`
- Les librairies externes (Stimulus, Turbo) sont téléchargées automatiquement
- Pas besoin de `npm install` ni de `node_modules/`

### Doctrine ORM

L'ORM Doctrine traduit les classes PHP (entités) en tables de base de données :
- **Entités** (`src/Entity/`) : Classes PHP annotées avec `#[ORM\...]`
- **Repositories** (`src/Repository/`) : Requêtes personnalisées
- **Migrations** (`migrations/`) : Scripts de modification du schéma

### Doctrine MongoDB ODM

Similaire à l'ORM mais pour MongoDB :
- **Documents** (`src/Document/`) : Classes PHP annotées avec `#[ODM\...]`
- Utilisé uniquement pour les statistiques (`OrderStats`)

## Environnements

| Environnement | Fichier | Usage |
|---|---|---|
| **dev** | `.env` | Développement local (debug activé) |
| **test** | `.env.test` | Tests PHPUnit |
| **prod** | `.env.local` | Production (non versionné) |

### Variables d'environnement requises

```
APP_SECRET=...                    # Clé secrète Symfony (sécurité CSRF, etc.)
DATABASE_URL=mysql://...          # Connexion MariaDB
MONGODB_URI=mongodb://...         # Connexion MongoDB
MONGODB_DB=...                    # Nom de la base MongoDB
MAILER_DSN=smtp://...             # Serveur SMTP pour les emails
COMPANY_EMAIL=...                 # Email de l'entreprise
COMPANY_NAME=...                  # Nom de l'entreprise
OPENROUTESERVICE_API_KEY=...      # Clé API pour le calcul de distance
```

## Voir aussi

- [ENTITIES.md](ENTITIES.md) - Modèle de données complet
- [CONTROLLERS_ROUTES.md](CONTROLLERS_ROUTES.md) - Tous les contrôleurs et routes
- [SERVICES.md](SERVICES.md) - Logique métier
- [ORDERS.md](ORDERS.md) - Système de commandes
- [SECURITY.md](SECURITY.md) - Sécurité et authentification
- [ASSETS.md](ASSETS.md) - CSS et JavaScript
- [DATABASE.md](DATABASE.md) - Base de données et fixtures
- [INDEX.md](INDEX.md) - Index complet de la documentation
