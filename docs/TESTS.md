# Tests - Vite & Gourmand

## Configuration

- **Framework**: PHPUnit 11.5
- **Extension**: DAMA Doctrine Test Bundle (transactions auto-rollback)
- **Config**: `phpunit.dist.xml`
- **Bootstrap**: `tests/bootstrap.php`
- **Namespace**: `App\Tests\`

```bash
# Lancer tous les tests
php bin/phpunit

# Lancer un fichier spécifique
php bin/phpunit tests/Entity/OrderTest.php

# Lancer avec couverture de code
php bin/phpunit --coverage-html coverage/

# Lancer un groupe de tests
php bin/phpunit tests/Controller/
```

---

## Vue d'ensemble

36 fichiers de tests couvrant 3 niveaux :

| Type | Description | Classe de base |
|------|-------------|----------------|
| **Unit** | Tests isolés, sans BDD | `TestCase` |
| **Integration** | Tests avec BDD réelle | `KernelTestCase` |
| **Functional** | Tests HTTP complets | `WebTestCase` |

---

## Tests Entity

### AddressTest.php

Tests unitaires (`TestCase`) — 9 tests

- Getters/setters des propriétés (rue, code postal, ville, téléphone, label)
- Relation bidirectionnelle `User ↔ Address`
- `isDefault` est `false` par défaut
- Modification d'une adresse existante

### UserStatusTest.php

Tests d'intégration (`KernelTestCase`) — 8 tests

- Nouveaux utilisateurs sont activés par défaut
- Désactivation et réactivation d'un compte
- Persistance du statut en base de données
- Modifications du statut

### ReviewTest.php

Tests unitaires avec Symfony Validator — 8 tests

- Note obligatoire, valeur entre 1 et 5
- Getters/setters
- `isValidated` est `false` par défaut

### MenuTest.php

Tests unitaires (`TestCase`) — 12 tests

- `getStock()` / `setStock()`
- `isAvailable()` selon le stock (0 = indisponible)
- `decrementStock()` avec quantité personnalisable
- Le stock ne peut pas descendre en dessous de 0

### OrderTest.php

Tests unitaires (`TestCase`) — 45 tests

**Initialisation**
- Numéro de commande au format `ORD-YYYYMMDD-XXXXX`

**Calcul du prix**
- Sous-total : prix × nombre de personnes
- Frais de livraison : 5€ pour Bordeaux, 5€ + 0.59€/km sinon
- Réduction 10% si 5+ personnes au-delà du minimum requis
- Prix total (sous-total + livraison - réduction)

**Changements de statut**
- `PENDING → VALIDATED → COMPLETED`
- `PENDING / VALIDATED / PREPARING → CANCELLED`
- Chaque transition met à jour `acceptedAt`, `completedAt`, `cancelledAt`

**Règles métier**
- `canBeCancelled()` : uniquement `PENDING`, `VALIDATED`, `PREPARING`
- `canBeEdited()` : uniquement `PENDING`
- `canReceiveReview()` : uniquement `COMPLETED`

---

## Tests Service

### OrderManagerTest.php

Tests unitaires avec mocks — 22 tests

- Création d'une commande avec tous les paramètres (client, livraison, menu, personnes)
- Matériaux empruntés : deadline = date de livraison + 10 jours
- Calcul complet du prix (Bordeaux et hors Bordeaux)
- Validation de la date de livraison (minimum 48h)
- Changements de statut (validation, annulation, complétion)
- Persistance : `persist()` + `flush()` + envoi email
- **Mocks** : `EmailService`, `OpenRouteService`, `OpeningScheduleManager`

### OrderManagerStockTest.php

Tests d'intégration avec BDD — 5 tests

- Décrémentation du stock lors de la création d'une commande
- Menus à stock limité vs stock illimité (`null`)
- Exception si stock épuisé ou insuffisant
- Décrémenter jusqu'à 0 rend le menu indisponible

### OrderStatsServiceTest.php

Tests unitaires avec mocks MongoDB — 6 tests

- Sauvegarde des statistiques dans MongoDB
- Création d'un nouveau document et mise à jour d'un existant
- Erreurs MongoDB silencieuses (log sans exception)
- Suppression d'une statistique
- **Mocks** : `DocumentManager`, `Logger`

### EmailServiceTest.php

Tests fonctionnels (mode test, pas d'envoi réel) — 7 tests

Vérifie qu'aucune exception n'est levée pour chaque type d'email :
- Contact, bienvenue, confirmation de commande
- Validation de commande, complétion
- Rappel retour de matériel, création d'un employé

### FileUploaderTest.php

Tests unitaires (`TestCase`) — 7 tests

- Upload dans un répertoire temporaire
- Noms de fichiers uniques et slugifiés via `SluggerInterface`
- Suppression de fichiers
- Exception si le répertoire n'est pas accessible

---

## Tests Repository

### ReviewRepositoryTest.php

Tests d'intégration (`KernelTestCase`) — 6 tests

- Récupération des avis validés vs en attente
- Tri par date
- Limite de résultats
- Sauvegarde et suppression
- Mise à jour du statut de validation

### OrderStatsRepositoryTest.php

Tests d'intégration MongoDB — 16 tests

Requêtes d'agrégation testées :
- Nombre de commandes par menu (avec filtres date)
- Chiffre d'affaires par menu
- Statistiques globales (total commandes, CA total, moyenne personnes)
- Noms de menus et thèmes distincts
- Nombre de commandes par thème
- CA par thème

Utilise 4 documents MongoDB créés en fixture avec des dates différentes.

---

## Tests Form

### ContactTypeTest.php

Tests unitaires — 7 tests

- Présence des champs : nom, email, sujet, message
- Validations : longueur minimale (nom, sujet, message), format email
- Comportement avec champs vides

---

## Tests Security

### RoleHierarchyTest.php

Tests fonctionnels (`WebTestCase`) — 14 tests

Hiérarchie : `ROLE_USER → ROLE_EMPLOYEE → ROLE_ADMIN`

| Rôle | Accès |
|------|-------|
| `ROLE_USER` | `/compte` uniquement |
| `ROLE_EMPLOYEE` | `/admin`, `/admin/users`, mais pas la création d'employé |
| `ROLE_ADMIN` | Tout, y compris `/admin/users/create-employee` |

Vérifie les codes HTTP 200 (accès autorisé) et 403 (accès refusé).

### DisabledAccountAuthenticationTest.php

Tests fonctionnels (`WebTestCase`) — 6 tests

- Comptes désactivés ne peuvent pas se connecter
- Message d'erreur spécifique : "compte a été désactivé"
- Message différent pour mauvais mot de passe
- Réactivation d'un compte permet ensuite la connexion

---

## Tests Controller

Tous héritent de `WebTestCase` et testent les requêtes HTTP complètes.

### SecurityControllerTest.php — 8 tests

- Page de connexion accessible (GET `/login`)
- Connexion réussie avec identifiants valides
- Connexion échouée (mauvais mot de passe, email inexistant, champs vides)
- Déconnexion fonctionnelle
- Utilisateur déjà connecté redirigé depuis `/login`
- Présence du champ "Se souvenir de moi"

### OrderControllerTest.php — 12 tests

- Listage des commandes : nécessite authentification
- Formulaire de nouvelle commande : présence des champs attendus
- Création valide d'une commande
- Création échoue si moins que le minimum de personnes
- Création échoue si date < 48h
- Redirection si pas d'adresse de livraison enregistrée
- Consultation : accessible au propriétaire uniquement (403 pour les autres)
- Annulation : succès, token CSRF invalide, commande non annulable, accès interdit

### RegisterControllerTest.php

- Inscription avec données valides
- Erreurs de validation (email invalide, mot de passe trop court, etc.)
- Email déjà utilisé

### PasswordResetControllerTest.php

- Formulaire de demande de réinitialisation
- Token valide/invalide/expiré
- Changement de mot de passe réussi

### ReviewControllerTest.php

- Soumission d'un avis (commande `COMPLETED` uniquement)
- Un avis par commande
- Validation de la note (1-5)

### ContactControllerTest.php

- Envoi du formulaire de contact
- Validations des champs
- Message flash de confirmation

### AccountEditControllerTest.php

- Modification des informations du compte
- Validations

### AccountPasswordControllerTest.php

- Changement de mot de passe
- Vérification de l'ancien mot de passe

### AddressControllerTest.php

- Ajout, modification, suppression d'adresse
- Définition de l'adresse par défaut

### Controllers Admin (9 fichiers)

Tests des pages d'administration pour chaque section :
- Menus, recettes, catégories, thèmes, allergènes
- Commandes, utilisateurs, avis, horaires, statistiques

---

## Techniques utilisées

### Mocking

```php
// Créer un mock
$emailService = $this->createMock(EmailService::class);

// Définir un comportement
$emailService->method('sendOrderConfirmation')
    ->willReturn(true);

// Vérifier qu'une méthode est appelée
$emailService->expects($this->once())
    ->method('sendOrderConfirmation')
    ->with($this->isInstanceOf(Order::class));
```

### DAMA Doctrine Test Bundle

Les tests d'intégration sont enveloppés dans une transaction qui est annulée après chaque test. La base de données reste propre sans nécessiter de fixtures supplémentaires.

### WebTestCase

```php
$client = static::createClient();

// Connexion programmatique
$client->loginUser($user);

// Requête HTTP
$client->request('POST', '/commandes/new', [
    'order_form' => [...],
]);

// Assertions
$this->assertResponseIsSuccessful();
$this->assertResponseRedirects('/commandes');
```

---

## Récapitulatif

| Catégorie | Fichiers | Type |
|-----------|----------|------|
| Entity | 5 | Unit + Integration |
| Service | 5 | Unit + Integration |
| Repository | 2 | Integration |
| Form | 1 | Unit |
| Security | 2 | Functional |
| Controller | 18 | Functional |
| **Total** | **36** | |
