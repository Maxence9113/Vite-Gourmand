# Securite et Authentification

## Vue d'ensemble

La securite de l'application repose sur le **composant Security de Symfony**. La configuration se trouve dans `config/packages/security.yaml`.

---

## Les 3 roles

### Hierarchie

```yaml
# config/packages/security.yaml
role_hierarchy:
    ROLE_EMPLOYEE: ROLE_USER
    ROLE_ADMIN: [ROLE_EMPLOYEE, ROLE_USER]
```

Cela signifie :
- Un **ROLE_ADMIN** a automatiquement les droits de **ROLE_EMPLOYEE** et **ROLE_USER**
- Un **ROLE_EMPLOYEE** a automatiquement les droits de **ROLE_USER**

### ROLE_USER (Client)

| Ce qu'il peut faire | Ce qu'il ne peut pas faire |
|---|---|
| Consulter les menus | Acceder au back-office |
| Passer des commandes | Gerer les menus/recettes |
| Gerer son profil et ses adresses | Voir les commandes des autres |
| Annuler ses commandes (si PENDING) | Changer les statuts de commande |
| Laisser un avis (commande terminee) | Valider des avis |

**Cree par** : Auto-inscription sur `/inscription`

### ROLE_EMPLOYEE (Employe)

| Ce qu'il peut faire en plus | Ce qu'il ne peut pas faire |
|---|---|
| Acceder au back-office `/admin` | Creer des comptes employes |
| Gerer les menus, recettes, categories... | Desactiver des comptes |
| Gerer les commandes (changer statut) | Changer les roles |
| Valider/rejeter les avis clients | Voir les statistiques detaillees |
| Filtrer et trier les commandes | Gerer les horaires d'ouverture |

**Cree par** : Un administrateur via `/admin/users/create-employee`

### ROLE_ADMIN (Administrateur)

| Ce qu'il peut faire en plus |
|---|
| Tout ce qu'un employe peut faire |
| Creer des comptes employes |
| Activer/desactiver des comptes |
| Changer les roles (promouvoir/retrograder) |
| Gerer les horaires d'ouverture |
| Voir les statistiques completes |

**Cree par** : Uniquement via les fixtures (`src/DataFixtures/UserFixtures.php`). Impossible de creer un admin depuis l'interface.

**Protection speciale** : Un compte ROLE_ADMIN **ne peut pas etre desactive ni modifie** depuis l'interface.

---

## Configuration de la securite

### Fichier complet : `config/packages/security.yaml`

```yaml
security:
    # Hashage automatique des mots de passe (bcrypt)
    password_hashers:
        Symfony\...\PasswordAuthenticatedUserInterface: 'auto'

    # Hierarchie des roles
    role_hierarchy:
        ROLE_EMPLOYEE: ROLE_USER
        ROLE_ADMIN: [ROLE_EMPLOYEE, ROLE_USER]

    # Fournisseur d'utilisateurs : charge les User depuis la BDD via l'email
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false    # Pas de securite pour les assets et le profiler
        main:
            lazy: true
            provider: app_user_provider
            user_checker: App\Security\UserChecker  # Verification compte actif

            form_login:
                login_path: "app_login"      # Route du formulaire
                check_path: "app_login"      # Route de soumission
            logout:
                path: app_logout             # Route de deconnexion

    # Controles d'acces par URL
    access_control:
        - { path: ^/admin/users/create-employee, roles: ROLE_ADMIN }
        - { path: ^/admin/users, roles: ROLE_EMPLOYEE }
        - { path: ^/admin, roles: ROLE_EMPLOYEE }
        - { path: ^/account, roles: ROLE_USER }
        - { path: ^/order, roles: ROLE_USER }
```

### Comment ca marche ?

1. **`password_hashers`** : Quand on fait `$hasher->hashPassword($user, 'motdepasse')`, Symfony utilise automatiquement bcrypt
2. **`providers`** : Quand l'utilisateur se connecte, Symfony cherche un User en BDD par son email
3. **`user_checker`** : Avant de connecter l'utilisateur, Symfony appelle `UserChecker` pour verifier que le compte est actif
4. **`form_login`** : Definit les routes de connexion/deconnexion
5. **`access_control`** : Protege des groupes d'URL par role. La **premiere regle qui matche** est appliquee

### Ordre des regles access_control

L'ordre est important ! Symfony applique la **premiere regle** qui correspond a l'URL :

```
/admin/users/create-employee → ROLE_ADMIN     (regle 1 matche en premier)
/admin/users/edit/5          → ROLE_EMPLOYEE   (regle 2)
/admin/menus                 → ROLE_EMPLOYEE   (regle 3)
/account/profil              → ROLE_USER       (regle 4)
/commande/nouvelle/1         → ROLE_USER       (regle 5)
/menus                       → aucune regle    (accessible a tous)
```

---

## UserChecker - Verification du compte

**Fichier** : `src/Security/UserChecker.php`

Ce service est appele automatiquement par Symfony **a chaque connexion** pour verifier que le compte est actif.

```php
class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Si le compte est desactive, on empeche la connexion
        if (!$user->isEnabled()) {
            throw new CustomUserMessageAuthenticationException(
                'Votre compte a ete desactive.'
            );
        }
    }
}
```

Si `isEnabled` est `false`, l'utilisateur voit le message "Votre compte a ete desactive" sur la page de connexion.

---

## Verification des droits dans le code

### Dans un controleur

```php
// Methode 1 : Attribut sur la methode (recommande)
#[IsGranted('ROLE_ADMIN')]
public function createEmployee(): Response { ... }

// Methode 2 : Verification programmatique
if ($this->isGranted('ROLE_ADMIN')) {
    // Code reserve aux admins
}

// Methode 3 : Lever une exception si pas autorise
$this->denyAccessUnlessGranted('ROLE_EMPLOYEE');
```

### Dans un template Twig

```twig
{# Afficher un lien seulement pour les admins #}
{% if is_granted('ROLE_ADMIN') %}
    <a href="{{ path('admin_users_index') }}">Gerer les utilisateurs</a>
{% endif %}

{# Afficher un element seulement pour les employes (et admins) #}
{% if is_granted('ROLE_EMPLOYEE') %}
    <a href="{{ path('admin_dashboard') }}">Administration</a>
{% endif %}
```

---

## Hashage des mots de passe

### Inscription d'un utilisateur

```php
// src/Controller/Public/RegisterController.php
$hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
$user->setPassword($hashedPassword);
```

Symfony utilise automatiquement **bcrypt** (algorithme par defaut). Le mot de passe en clair n'est jamais stocke en base.

### Exigences de mot de passe (employes)

Pour les comptes employes, le mot de passe doit respecter :
- Minimum **10 caracteres**
- Au moins **une majuscule**
- Au moins **une minuscule**
- Au moins **un chiffre**
- Au moins **un caractere special**

Ces regles sont validees cote client (JavaScript : `assets/password-validator.js`) ET cote serveur.

---

## Protection CSRF

Les formulaires Symfony incluent automatiquement un **token CSRF**. Pour les actions hors formulaire :

```php
// Verification dans le controleur
if ($this->isCsrfTokenValid('delete' . $entity->getId(), $request->request->get('_token'))) {
    // Action autorisee
}
```

```twig
{# Generation dans le template #}
<input type="hidden" name="_token" value="{{ csrf_token('delete' ~ entity.id) }}">
```

---

## Reset de mot de passe

### Flux

1. L'utilisateur demande un reset sur `/password-reset/request`
2. Un token aleatoire de **64 caracteres hex** est genere (`bin2hex(random_bytes(32))`)
3. Le token est sauvegarde dans `PasswordResetToken` avec une date d'expiration
4. Un email contenant le lien de reset est envoye
5. L'utilisateur clique sur le lien `/password-reset/reset/{token}`
6. Le token est verifie (existence, expiration, deja utilise)
7. L'utilisateur saisit son nouveau mot de passe
8. Le token est marque comme utilise

**Securite** : Le message de succes est toujours affiche meme si l'email n'existe pas, pour **empecher l'enumeration de comptes**.

---

## Comptes de test

### Administrateur
- Email : `jose@vitegourmand.fr`
- Mot de passe : `Admin1234!@`

### Employe
- Email : `julie@vitegourmand.fr`
- Mot de passe : `Employee123!@`

### Utilisateur
- Email : `user@test.fr`
- Mot de passe : `User1234!@`

---

## Voir aussi

- [GESTION_DES_ROLES.md](GESTION_DES_ROLES.md) - Documentation existante sur les roles
- [CONTROLLERS_ROUTES.md](CONTROLLERS_ROUTES.md) - Routes protegees
- [ARCHITECTURE.md](ARCHITECTURE.md) - Vue d'ensemble
