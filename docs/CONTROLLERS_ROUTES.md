# Controleurs et Routes

## Vue d'ensemble

L'application utilise le **routing par attributs** de Symfony : chaque route est declaree directement sur la methode du controleur avec `#[Route('...')]`. Symfony decouvre automatiquement toutes les routes grace a la configuration dans `config/routes.yaml`.

Les controleurs sont organises en 3 dossiers :
- **`Admin/`** : Back-office (necessite `ROLE_EMPLOYEE` ou `ROLE_ADMIN`)
- **`Api/`** : Endpoints JSON pour les requetes AJAX
- **`Public/`** : Pages accessibles a tous ou aux utilisateurs connectes

---

## Controleurs Admin

Tous les controleurs admin sont proteges par `ROLE_EMPLOYEE` au minimum (voir `security.yaml`).

### AdminController - Dashboard

**Fichier** : `src/Controller/Admin/AdminController.php`
**Route de base** : `/admin`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `index()` | `GET /admin` | `admin_dashboard` | Dashboard avec statistiques |

Le dashboard affiche :
- Nombre total de recettes, categories, allergenes, utilisateurs, themes, regimes, menus
- Nombre de commandes en attente (ni `COMPLETED` ni `CANCELLED`)
- Les 10 dernieres commandes en attente

---

### MenuController (Admin) - Gestion des menus

**Fichier** : `src/Controller/Admin/MenuController.php`
**Route de base** : `/admin/menus`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `index()` | `GET /admin/menus` | `admin_menu_index` | Liste de tous les menus |
| `new()` | `GET/POST /admin/menus/new` | `admin_menu_new` | Creer un nouveau menu |
| `edit()` | `GET/POST /admin/menus/{id}/edit` | `admin_menu_edit` | Modifier un menu |
| `delete()` | `POST /admin/menus/{id}/delete` | `admin_menu_delete` | Supprimer un menu |

**Logique cle** :
- A la creation/edition, les recettes sont organisees par categorie (entrees, plats, fromages, desserts)
- Upload d'image avec `MenuFileUploader`
- Suppression du fichier image a la suppression du menu

---

### RecipeController (Admin) - Gestion des recettes

**Fichier** : `src/Controller/Admin/RecipeController.php`
**Route de base** : `/admin/recipes`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `index()` | `GET /admin/recipes` | `admin_recipe_index` | Liste des recettes |
| `new()` | `GET/POST /admin/recipes/new` | `admin_recipe_new` | Creer une recette |
| `edit()` | `GET/POST /admin/recipes/{id}/edit` | `admin_recipe_edit` | Modifier une recette |
| `delete()` | `POST /admin/recipes/{id}/delete` | `admin_recipe_delete` | Supprimer une recette |

**Logique cle** :
- Upload de **multiples illustrations** (via `RecipeFileUploader`)
- Chaque illustration a un texte alternatif pour l'accessibilite
- Suppression des fichiers images lors de la suppression de la recette

---

### UserController (Admin) - Gestion des utilisateurs

**Fichier** : `src/Controller/Admin/UserController.php`
**Route de base** : `/admin/users`

| Methode | Route | Nom | Acces | Description |
|---|---|---|---|---|
| `index()` | `GET /admin/users` | `admin_users_index` | EMPLOYEE | Liste des utilisateurs |
| `createEmployee()` | `GET/POST /admin/users/create-employee` | `admin_users_create_employee` | **ADMIN** | Creer un employe |
| `edit()` | `GET/POST /admin/users/{id}/edit` | `admin_users_edit` | EMPLOYEE | Modifier un utilisateur |
| `toggleStatus()` | `POST /admin/users/{id}/toggle-status` | `admin_users_toggle_status` | **ADMIN** | Activer/desactiver un compte |
| `changeRole()` | `POST /admin/users/{id}/change-role` | `admin_users_change_role` | **ADMIN** | Changer le role |

**Regles de securite** :
- Un employe ne peut voir que les clients (pas les autres employes)
- Impossible de desactiver un compte ROLE_ADMIN
- Impossible de modifier le role d'un ROLE_ADMIN
- Creation d'employe : envoie un email de notification

---

### OrderAdminController - Gestion des commandes

**Fichier** : `src/Controller/Admin/OrderAdminController.php`
**Route de base** : `/admin/orders`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `index()` | `GET /admin/orders` | `admin_orders_index` | Liste avec filtres |
| `show()` | `GET /admin/orders/{id}` | `admin_orders_show` | Detail d'une commande |
| `changeStatus()` | `POST /admin/orders/{id}/change-status` | `admin_orders_change_status` | Changer le statut |
| `markMaterialReturned()` | `POST /admin/orders/{id}/mark-material-returned` | `admin_orders_mark_material_returned` | Marquer materiel retourne |
| `cancel()` | `POST /admin/orders/{id}/cancel` | `admin_orders_cancel` | Annuler une commande |

**Logique cle** :
- Filtrage par statut, recherche textuelle, date, tri
- Validation des transitions de statut via `OrderStatusValidator`
- Envoi d'emails lors de certains changements de statut
- Sauvegarde dans MongoDB via `OrderStatsService`

---

### OrderStatsController - Statistiques

**Fichier** : `src/Controller/Admin/OrderStatsController.php`
**Route de base** : `/admin/stats`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `index()` | `GET /admin/stats` | `admin_order_stats` | Page de statistiques |

Affiche des graphiques et KPIs bases sur les donnees MongoDB :
- Filtrage par theme, menu, periode
- KPIs globaux : nombre de commandes, CA, panier moyen, etc.

---

### ReviewController (Admin) - Moderation des avis

**Fichier** : `src/Controller/Admin/ReviewController.php`
**Route de base** : `/admin/avis`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `index()` | `GET /admin/avis` | `admin_review_index` | Liste des avis |
| `validate()` | `POST /admin/avis/{id}/valider` | `admin_review_validate` | Approuver un avis |
| `reject()` | `POST /admin/avis/{id}/rejeter` | `admin_review_reject` | Rejeter un avis |
| `delete()` | `POST /admin/avis/{id}/supprimer` | `admin_review_delete` | Supprimer un avis |

---

### OpeningScheduleController - Horaires d'ouverture

**Fichier** : `src/Controller/Admin/OpeningScheduleController.php`
**Route de base** : `/admin/horaires` | **Acces** : `ROLE_ADMIN`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `index()` | `GET /admin/horaires` | `admin_opening_schedule_index` | Voir les horaires |
| `new()` | `GET/POST /admin/horaires/nouveau` | `admin_opening_schedule_new` | Ajouter un jour |
| `bulkEdit()` | `GET/POST /admin/horaires/edition-masse` | `admin_opening_schedule_bulk_edit` | Modifier tous les jours |
| `edit()` | `GET/POST /admin/horaires/{id}/modifier` | `admin_opening_schedule_edit` | Modifier un jour |
| `delete()` | `POST /admin/horaires/{id}` | `admin_opening_schedule_delete` | Supprimer un jour |
| `initialize()` | `POST /admin/horaires/initialiser` | `admin_opening_schedule_initialize` | Reinitialiser |

L'initialisation cree les horaires par defaut : Lun-Ven 9h-18h, Sam 10h-16h, Dim ferme.

---

### AbstractCrudController - Controleur de base

**Fichier** : `src/Controller/Admin/AbstractCrudController.php`

Controleur abstrait qui fournit des operations CRUD reutilisables. Utilise par :
- `AllergenController`
- `CategoryController`
- `DietetaryController`
- `ThemeController`

Ces 4 controleurs partagent le meme pattern : liste, creation, edition, suppression avec protection contre la suppression si l'entite est utilisee.

---

## Controleurs publics

### HomeController - Page d'accueil

**Fichier** : `src/Controller/Public/HomeController.php`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `index()` | `GET /` | `app_home` | Accueil avec les 4 derniers avis valides |

---

### MenuController (Public) - Catalogue

**Fichier** : `src/Controller/Public/MenuController.php`
**Route de base** : `/menus`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `catalog()` | `GET /menus` | `app_menu_catalog` | Catalogue avec filtres |
| `show()` | `GET /menus/{id}` | `app_menu_show` | Detail d'un menu |

**Filtres disponibles** sur le catalogue :
- Theme (`?theme=1`)
- Regimes alimentaires (`?dietetary[]=1&dietetary[]=2`)
- Allergenes a exclure (`?allergen[]=1`)
- Prix min/max en euros (`?price_min=20&price_max=50`)
- Nombre minimum de personnes (`?nb_person_min=10`)

Les prix sont convertis de euros vers centimes avant la requete en BDD.

---

### OrderController - Commandes utilisateur

**Fichier** : `src/Controller/Public/OrderController.php`
**Acces** : `ROLE_USER`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `index()` | `GET /compte/commandes` | `app_account_orders` | Mes commandes |
| `new()` | `GET/POST /commande/nouvelle/{id}` | `app_order_new` | Passer commande |
| `show()` | `GET /compte/commandes/{id}` | `app_order_show` | Detail commande |
| `cancel()` | `POST /compte/commandes/{id}/annuler` | `app_order_cancel` | Annuler commande |
| `calculateDeliveryCost()` | `GET /api/commande/calculer-frais-livraison/{addressId}` | `app_order_delivery_cost` | API frais de livraison |

**Flux de creation de commande** :
1. Verification que l'utilisateur a au moins une adresse
2. Affichage du formulaire (menu pre-selectionne)
3. Validation : nombre de personnes >= minimum du menu
4. Validation : date de livraison >= 48h a l'avance
5. Calcul des frais de livraison (via OpenRouteService)
6. Creation de la commande via `OrderManager`

---

### AccountController - Mon compte

**Fichier** : `src/Controller/Public/AccountController.php`
**Acces** : `ROLE_USER`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `index()` | `GET /compte` | `app_account` | Dashboard du compte |
| `edit()` | `GET/POST /compte/modifier-profil` | `app_account_edit` | Modifier le profil |
| `password()` | `GET/POST /compte/modifier-mot-de-passe` | `app_account_password` | Changer le mot de passe |

---

### AddressController - Adresses de livraison

**Fichier** : `src/Controller/Public/AddressController.php`
**Acces** : `ROLE_USER`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `index()` | `GET /compte/adresses` | `app_address_index` | Mes adresses |
| `new()` | `GET/POST /compte/adresses/nouvelle` | `app_address_new` | Ajouter une adresse |
| `edit()` | `GET/POST /compte/adresses/{id}/modifier` | `app_address_edit` | Modifier une adresse |
| `delete()` | `POST /compte/adresses/{id}/supprimer` | `app_address_delete` | Supprimer |
| `setDefault()` | `POST /compte/adresses/{id}/definir-par-defaut` | `app_address_set_default` | Definir par defaut |

---

### LoginController - Authentification

**Fichier** : `src/Controller/Public/LoginController.php`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `index()` | `GET /connexion` | `app_login` | Formulaire de connexion |
| `logout()` | `GET /deconnexion` | `app_logout` | Deconnexion |

La deconnexion est geree par Symfony (`security.yaml`), pas par le controleur.

---

### RegisterController - Inscription

**Fichier** : `src/Controller/Public/RegisterController.php`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `index()` | `GET/POST /inscription` | `app_register` | Formulaire d'inscription |

Cree un utilisateur avec `ROLE_USER`, hash le mot de passe, envoie un email de bienvenue.

---

### PasswordResetController - Reinitialisation du mot de passe

**Fichier** : `src/Controller/Public/PasswordResetController.php`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `request()` | `GET/POST /password-reset/request` | `app_password_reset_request` | Demander un reset |
| `reset()` | `GET/POST /password-reset/reset/{token}` | `app_password_reset_reset` | Reset avec token |

**Securite** : Le message de succes est toujours affiche, meme si l'email n'existe pas (pour eviter l'enumeration de comptes).

---

### ReviewController (Public) - Avis clients

**Fichier** : `src/Controller/Public/ReviewController.php`

| Methode | Route | Nom | Acces | Description |
|---|---|---|---|---|
| `index()` | `GET /avis` | `app_review_index` | Public | Liste des avis valides |
| `new()` | `GET/POST /avis/nouveau/{orderId}` | `app_review_new` | ROLE_USER | Laisser un avis |

**Regles** :
- Seules les commandes `COMPLETED` peuvent recevoir un avis
- Un seul avis par commande
- L'avis n'est pas visible publiquement tant qu'un employe ne l'a pas valide

---

### ContactController - Contact

**Fichier** : `src/Controller/Public/ContactController.php`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `index()` | `GET/POST /contact` | `app_contact` | Formulaire de contact |

Envoie 2 emails : un a l'entreprise (notification), un au visiteur (confirmation).

---

### LegalController - Pages legales

**Fichier** : `src/Controller/Public/LegalController.php`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `mentions()` | `GET /mentions-legales` | `app_mentions` | Mentions legales |
| `cgv()` | `GET /conditions-generales-vente` | `app_cgv` | CGV |
| `privacy()` | `GET /politique-confidentialite` | `app_privacy` | Politique de confidentialite |

---

## Controleurs API

### MenuApiController - Filtrage AJAX

**Fichier** : `src/Controller/Api/MenuApiController.php`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `filter()` | `GET /api/menus/filter` | `api_menu_filter` | Filtrer les menus en JSON |

Voir [AJAX_FILTERING.md](AJAX_FILTERING.md) pour la documentation complete.

---

### AddressApiController - Autocomplete d'adresses

**Fichier** : `src/Controller/Api/AddressApiController.php`

| Methode | Route | Nom | Description |
|---|---|---|---|
| `getCityFromPostalCode()` | `GET /api/adresse/ville-depuis-code-postal/{postalCode}` | `api_address_city` | Trouver une ville par code postal |

Appelle l'API gouvernementale `api-adresse.data.gouv.fr` pour trouver les villes correspondant a un code postal francais.

---

## Voir aussi

- [ARCHITECTURE.md](ARCHITECTURE.md) - Vue d'ensemble
- [SERVICES.md](SERVICES.md) - Logique metier
- [SECURITY.md](SECURITY.md) - Controles d'acces
- [AJAX_FILTERING.md](AJAX_FILTERING.md) - Filtrage AJAX
