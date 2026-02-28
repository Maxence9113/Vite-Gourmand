# Services - Logique metier

## Qu'est-ce qu'un service dans Symfony ?

Un **service** est une classe PHP qui encapsule de la logique metier reutilisable. Au lieu de mettre toute la logique dans les controleurs (ce qui les rendrait trop gros), on la deplace dans des services.

Symfony injecte automatiquement les services la ou on en a besoin grace a l'**autowiring** (injection de dependances automatique).

```php
// Symfony injecte automatiquement OrderManager dans le controleur
public function newOrder(OrderManager $orderManager): Response
{
    $orderManager->createOrder($order, $menu, $user, $address);
}
```

Les services sont configures dans `config/services.yaml`.

---

## OrderManager - Gestion des commandes

**Fichier** : `src/Service/OrderManager.php`

C'est le service **le plus important** de l'application. Il centralise toute la logique de creation et de gestion des commandes.

### Dependances injectees

```php
public function __construct(
    private EntityManagerInterface $entityManager,
    private OpenRouteService $openRouteService,    // Calcul de distance
    private OrderStatsService $orderStatsService,  // Sauvegarde stats MongoDB
    private EmailService $emailService,            // Envoi d'emails
    private LoggerInterface $logger                // Journalisation
)
```

### Methode principale : createOrder()

```php
public function createOrder(Order $order, Menu $menu, User $user, Address $address): void
```

**Etapes de la creation** :
1. **Copie des infos client** : Prenom, nom, email, telephone (snapshot)
2. **Copie des infos menu** : Nom du menu, prix par personne
3. **Format de l'adresse** : Concatenation rue + code postal + ville
4. **Calcul de la distance** : Via l'API OpenRouteService
5. **Calcul du sous-total** : `prix_par_personne x nombre_de_personnes`
6. **Calcul de la reduction** : 10% si >= 5 personnes de plus que le minimum
7. **Calcul des frais de livraison** : 5 euros (Bordeaux) ou 5 + 0.59/km
8. **Calcul du prix total** : Sous-total + livraison - reduction
9. **Gestion du pret de materiel** : Date limite de retour = livraison + 10 jours
10. **Decrementation du stock** du menu si applicable
11. **Initialisation** : Numero de commande, statut PENDING, dates
12. **Sauvegarde** en base MariaDB
13. **Sauvegarde des stats** dans MongoDB
14. **Envoi d'un email** de confirmation

### Autres methodes

```php
// Recupere les commandes d'un utilisateur
getUserOrders(User $user): array

// Verifie si la date de livraison est valide (>= 48h)
isValidDeliveryDate(\DateTimeImmutable $date): bool
```

---

## OrderStatusValidator - Validation des transitions de statut

**Fichier** : `src/Service/OrderStatusValidator.php`

Ce service verifie si un changement de statut est autorise **avant** de l'appliquer.

### Utilisation

```php
$validator = $this->orderStatusValidator
    ->validateStatusChange($order, OrderStatus::VALIDATED);

if (!$validator->isValid()) {
    $errorMessage = $validator->getErrorMessage();
    // Afficher l'erreur
}
```

### Regles verifiees

1. **Transition autorisee** : Verifie que le nouveau statut fait partie des `getNextStatuses()` du statut actuel
2. **Regles metier** :
   - Impossible de passer a `COMPLETED` si du materiel est prete mais pas retourne
   - Impossible de passer a `WAITING_MATERIAL_RETURN` si la commande n'a pas de pret de materiel

---

## OrderStatisticsService - Statistiques (MariaDB)

**Fichier** : `src/Service/OrderStatisticsService.php`

Service de calcul de statistiques basees sur les donnees MariaDB. Utilise pour le dashboard admin.

### Methodes

```php
// Retourne le nombre de commandes par statut
getQuickStats(): array
// Retourne: ['total' => 50, 'pending' => 5, 'validated' => 8, ...]

// Nombre de commandes actives (ni completees, ni annulees)
getActiveOrdersCount(): int

// Revenu total des commandes completees
getTotalRevenue(): float

// Nombre de retours de materiel en retard
getOverdueMaterialReturnsCount(): int
```

---

## OrderStatsService - Statistiques (MongoDB)

**Fichier** : `src/Service/OrderStatsService.php`

Ce service **sauvegarde des copies** des donnees de commande dans MongoDB pour alimenter les graphiques du back-office.

### Pourquoi MongoDB ?

MongoDB est utilise ici pour **demontrer l'utilisation d'une base NoSQL** dans un projet Symfony. Les donnees sont denormalisees (copiees) depuis MariaDB pour faciliter les requetes d'agregation (totaux par theme, par mois, etc.).

### Methodes

```php
// Sauvegarde ou met a jour les stats d'une commande
saveOrderStats(Order $order): void

// Supprime les stats d'une commande
deleteOrderStats(int $orderId): void
```

### Fonctionnement

1. Quand une commande est creee ou son statut change, `saveOrderStats()` est appele
2. Le service cherche si des stats existent deja pour cette commande (par `orderId`)
3. Si oui : mise a jour du `totalPrice`
4. Si non : creation d'un nouveau document `OrderStats`
5. Si une erreur MongoDB survient, elle est loguee mais **ne bloque pas** l'application (les stats ne sont pas critiques)

**Attention** : Les prix sont convertis de centimes (MariaDB) vers euros (MongoDB) lors de la sauvegarde.

---

## OrderFilterService - Filtrage des commandes

**Fichier** : `src/Service/OrderFilterService.php`

Filtre les commandes dans le back-office admin selon plusieurs criteres.

```php
filterOrders(?string $status, ?string $search, ?string $dateFrom, ?string $dateTo, ?string $sortBy): array
```

---

## EmailService - Envoi d'emails

**Fichier** : `src/Service/EmailService.php`

Service centralisant tous les envois d'emails de l'application via Symfony Mailer.

### Dependances

```php
public function __construct(
    private MailerInterface $mailer,
    private string $companyEmail,  // Depuis .env : COMPANY_EMAIL
    private string $companyName    // Depuis .env : COMPANY_NAME
)
```

### Methodes disponibles

| Methode | Quand | Template Twig |
|---|---|---|
| `sendContactEmail()` | Formulaire de contact | `emails/contact.html.twig` |
| `sendContactConfirmation()` | Confirmation au visiteur | `emails/contact_confirmation.html.twig` |
| `sendWelcomeEmail()` | Inscription | `emails/welcome.html.twig` |
| `sendOrderConfirmation()` | Commande creee | `emails/order_confirmation.html.twig` |
| `sendOrderValidated()` | Commande validee | `emails/order_validated.html.twig` |
| `sendOrderCompleted()` | Commande terminee | `emails/order_completed.html.twig` |
| `sendPasswordResetEmail()` | Demande de reset | `emails/password_reset.html.twig` |
| `sendEmployeeAccountCreated()` | Creation employe | `emails/employee_account_created.html.twig` |
| `sendMaterialReturnReminder()` | Rappel retour materiel | `emails/material_return_reminder.html.twig` |

Voir [EMAILS.md](EMAILS.md) pour plus de details.

---

## OpenRouteService - Calcul de distance

**Fichier** : `src/Service/OpenRouteService.php`

Service qui appelle l'API externe [OpenRouteService](https://openrouteservice.org/) pour calculer la distance entre le restaurant et l'adresse de livraison.

### Utilisation

```php
$distanceKm = $this->openRouteService->calculateDistance($addressString);
```

### Fonctionnement

1. Geocode l'adresse de livraison (texte -> coordonnees GPS)
2. Calcule la distance routiere entre le restaurant (coordonnees fixes) et l'adresse
3. Retourne la distance en kilometres

**Cle API** : Configuree dans `.env` (`OPENROUTESERVICE_API_KEY`) et injectee via `services.yaml`.

---

## FileUploader - Upload de fichiers

**Fichier** : `src/Service/FileUploader.php`

Service generique pour uploader des fichiers de maniere securisee.

### Fonctionnement

```php
$filename = $fileUploader->uploadFile($uploadedFile);
// Retourne le nom du fichier genere (ex: "mon-image-5f3a2b1c.jpg")
```

1. Recupere le nom original du fichier
2. Le transforme en slug (supprime accents, espaces, caracteres speciaux)
3. Ajoute un identifiant unique (`uniqid()`) pour eviter les collisions
4. Deplace le fichier dans le `$targetDirectory`
5. Retourne le nom du fichier genere

### Services specialises

Deux services heritent de `FileUploader` avec un repertoire cible different :

| Service | Fichier | Repertoire cible |
|---|---|---|
| `RecipeFileUploader` | `src/Service/RecipeFileUploader.php` | `public/uploads/recipe_illustrations/` |
| `MenuFileUploader` | `src/Service/MenuFileUploader.php` | `public/uploads/menu_illustrations/` |

La configuration est dans `services.yaml` :

```yaml
App\Service\RecipeFileUploader:
    arguments:
        $targetDirectory: '%recipe_illustrations_directory%'

App\Service\MenuFileUploader:
    arguments:
        $targetDirectory: '%menu_illustrations_directory%'
```

---

## OpeningScheduleManager - Horaires d'ouverture

**Fichier** : `src/Service/OpeningScheduleManager.php`

Service pour verifier si une date/heure de livraison est valide par rapport aux horaires du restaurant.

### Methodes

```php
// Verifie si la date est pendant les heures d'ouverture
isValidDeliveryDateTime(\DateTimeImmutable $dateTime): bool

// Retourne la prochaine heure d'ouverture
getNextOpeningTime(): ?\DateTimeImmutable
```

### Regle des 48h

La commande doit etre passee **au moins 48 heures a l'avance** ET la livraison doit etre pendant les heures d'ouverture du restaurant.

---

## AddressManager - Gestion des adresses

**Fichier** : `src/Service/AddressManager.php`

### Methodes

```php
// Recupere les adresses d'un utilisateur
getUserAddresses(User $user): array

// Definit une adresse comme adresse par defaut
setDefaultAddress(Address $address, User $user): void
```

Quand une adresse est definie par defaut, toutes les autres adresses de l'utilisateur sont mises a `isDefault = false`.

---

## Configuration des services (services.yaml)

```yaml
parameters:
    recipe_illustrations_directory: '%kernel.project_dir%/public/uploads/recipe_illustrations'
    menu_illustrations_directory: '%kernel.project_dir%/public/uploads/menu_illustrations'

services:
    _defaults:
        autowire: true        # Injection automatique des dependances
        autoconfigure: true   # Auto-detection des commandes, subscribers, etc.

    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Entity/'
            - '../src/Kernel.php'

    App\Service\RecipeFileUploader:
        arguments:
            $targetDirectory: '%recipe_illustrations_directory%'

    App\Service\MenuFileUploader:
        arguments:
            $targetDirectory: '%menu_illustrations_directory%'

    App\Service\OpenRouteService:
        arguments:
            $apiKey: '%env(OPENROUTESERVICE_API_KEY)%'

    App\Service\EmailService:
        arguments:
            $companyEmail: '%env(COMPANY_EMAIL)%'
            $companyName: '%env(COMPANY_NAME)%'
```

### Comment ca marche ?

1. `autowire: true` : Symfony detecte automatiquement les dependances necessaires en analysant le constructeur
2. `autoconfigure: true` : Les classes qui implementent certaines interfaces sont automatiquement enregistrees (commandes, event listeners, etc.)
3. `App\: resource: '../src/'` : Toutes les classes dans `src/` sont enregistrees comme services
4. Les services avec des parametres speciaux (comme `$targetDirectory`) sont configures explicitement

---

## Voir aussi

- [ORDERS.md](ORDERS.md) - Cycle de vie des commandes
- [EMAILS.md](EMAILS.md) - Systeme d'emails
- [CONTROLLERS_ROUTES.md](CONTROLLERS_ROUTES.md) - Controleurs
