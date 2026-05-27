# Documentation Vite & Gourmand - Index

## A propos

Cette documentation couvre l'integralite de l'application **Vite & Gourmand**, un systeme de gestion de traiteur construit avec Symfony 6.4.

---

## Sommaire

### Architecture et structure

- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Vue d'ensemble de l'application
  - Stack technique (Symfony, MariaDB, MongoDB, AssetMapper)
  - Structure des dossiers
  - Pattern MVC
  - Flux d'une requete
  - Les 3 zones de l'application (publique, utilisateur, admin)
  - Concepts Symfony importants (injection de dependances, routing, ORM)
  - Environnements et variables d'environnement

### Modele de donnees

- **[ENTITIES.md](ENTITIES.md)** - Entites et relations
  - Diagramme des relations
  - Toutes les entites detaillees (User, Order, Menu, Recipe, etc.)
  - Champs, types, descriptions
  - Relations entre entites
  - Methodes metier (calcul de prix, changement de statut, etc.)
  - Enums (OrderStatus, DayOfWeek)
  - Document MongoDB (OrderStats)
  - Note importante : les prix sont en centimes

### Controleurs et routes

- **[CONTROLLERS_ROUTES.md](CONTROLLERS_ROUTES.md)** - Toutes les routes HTTP
  - Controleurs admin (dashboard, menus, recettes, commandes, utilisateurs, avis, stats, horaires)
  - Controleurs publics (accueil, catalogue, commandes, compte, inscription, connexion, contact)
  - Controleurs API (filtrage AJAX des menus, autocomplete d'adresses)
  - Tableau complet : methode, route, nom, description

### Logique metier

- **[SERVICES.md](SERVICES.md)** - Services et logique metier
  - OrderManager (creation de commande)
  - OrderStatusValidator (validation des transitions)
  - OrderStatisticsService (stats MariaDB)
  - OrderStatsService (stats MongoDB)
  - EmailService (envoi d'emails)
  - OpenRouteService (calcul de distance)
  - FileUploader (upload securise)
  - OpeningScheduleManager (horaires)
  - AddressManager (adresses)
  - Configuration services.yaml

### Commandes

- **[ORDERS.md](ORDERS.md)** - Systeme de commandes complet
  - Diagramme du cycle de vie
  - Description de chaque statut
  - Creation d'une commande (cote client)
  - Calcul du prix (sous-total, livraison, reduction)
  - Gestion des commandes (cote admin)
  - Gestion du materiel (pret et retour)
  - Historique des statuts
  - Tous les fichiers impliques

### Formulaires

- **[FORMS_VALIDATION.md](FORMS_VALIDATION.md)** - Formulaires et validation
  - Comment fonctionnent les formulaires Symfony
  - Tous les formulaires detailles
  - Validation des entites
  - Validateur personnalise (ValidDeliveryDateTime - regle des 48h)
  - Theme de formulaire personnalise
  - Protection CSRF

### Securite

- **[SECURITY.md](SECURITY.md)** - Securite et authentification
  - Les 3 roles (User, Employee, Admin)
  - Configuration security.yaml detaillee
  - UserChecker (verification compte actif)
  - Verification des droits dans le code
  - Hashage des mots de passe
  - Protection CSRF
  - Reset de mot de passe
  - Comptes de test

- **[GESTION_DES_ROLES.md](GESTION_DES_ROLES.md)** - Documentation roles (existante)
  - Vue d'ensemble des roles
  - Hierarchie et controles d'acces
  - Creation et gestion des comptes
  - Comptes de test

### Frontend

- **[TEMPLATES.md](TEMPLATES.md)** - Templates Twig
  - Syntaxe de base Twig
  - Heritage de templates (extends/blocks)
  - Layout principal et layout admin
  - Organisation complete des templates
  - Messages flash
  - Fonctions Twig utiles

- **[ASSETS.md](ASSETS.md)** - CSS et JavaScript
  - AssetMapper et importmap.php
  - Fichiers JavaScript detailles (app.js, filtres AJAX, upload, etc.)
  - Organisation du CSS
  - Variables CSS
  - Responsive design
  - Hotwired Turbo et Stimulus

- **[AJAX_FILTERING.md](AJAX_FILTERING.md)** - Filtrage AJAX (existante)
  - Architecture backend/frontend
  - API REST
  - Debouncing
  - Tests manuels et automatiques

### Base de donnees

- **[DATABASE.md](DATABASE.md)** - Base de donnees et fixtures
  - Configuration MariaDB (Doctrine ORM)
  - Configuration MongoDB (Doctrine ODM)
  - Migration
  - DataFixtures detaillees
  - Ordre de chargement
  - Commandes CLI utiles

### Emails

- **[EMAILS.md](EMAILS.md)** - Systeme d'emails
  - Configuration SMTP
  - Liste complete des 9 emails envoyes
  - Templates d'emails
  - Gestion des erreurs

---

## Fichiers de documentation

```
docs/
├── INDEX.md                    ← Tu es ici
├── ARCHITECTURE.md             # Vue d'ensemble
├── ENTITIES.md                 # Modele de donnees
├── CONTROLLERS_ROUTES.md       # Routes HTTP
├── SERVICES.md                 # Logique metier
├── ORDERS.md                   # Systeme de commandes
├── FORMS_VALIDATION.md         # Formulaires
├── SECURITY.md                 # Securite
├── TEMPLATES.md                # Templates Twig
├── ASSETS.md                   # CSS et JavaScript
├── EMAILS.md                   # Systeme d'emails
├── DATABASE.md                 # BDD et fixtures
├── TESTS.md                    # Suite de tests
├── GESTION_DES_ROLES.md        # Roles (existant)
└── AJAX_FILTERING.md           # Filtrage AJAX (existant)
```

---

## Commandes de reference rapide

### Developpement

```bash
# Lancer le serveur de developpement
symfony serve

# Vider le cache
php bin/console cache:clear

# Voir toutes les routes
php bin/console debug:router

# Voir tous les services
php bin/console debug:container

# Voir les assets
php bin/console debug:asset-map
```

### Base de donnees

```bash
# Creer la BDD
php bin/console doctrine:database:create

# Executer les migrations
php bin/console doctrine:migrations:migrate

# Charger les fixtures
php bin/console doctrine:fixtures:load

# Creer un admin
php bin/console app:create-admin
```

### Tests

- **[TESTS.md](TESTS.md)** - Suite de tests complète
  - Configuration PHPUnit et DAMA Doctrine Test Bundle
  - Tests Entity (unitaires et intégration)
  - Tests Service (mocks, logique métier)
  - Tests Repository (MySQL et MongoDB)
  - Tests Form, Security et Controller (fonctionnels)
  - Techniques utilisées (mocking, WebTestCase, transactions)

```bash
# Lancer les tests
php bin/phpunit

# Lancer un test specifique
php bin/phpunit tests/Controller/MenuControllerTest.php
```
