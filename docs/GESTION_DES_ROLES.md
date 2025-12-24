# Gestion des rôles

## Vue d'ensemble

L'application implémente un système de gestion des rôles basé sur 3 niveaux hiérarchiques :

### Rôles disponibles

1. **ROLE_USER** (Utilisateur)
   - Rôle par défaut attribué à tous les utilisateurs lors de l'inscription
   - Permissions :
     - Consulter les menus
     - Passer des commandes
     - Gérer son profil
     - Consulter et modifier ses commandes (avant acceptation)
     - Laisser des avis après livraison

2. **ROLE_EMPLOYEE** (Employé)
   - Rôle créé uniquement par l'administrateur
   - Hérite de toutes les permissions de ROLE_USER
   - Permissions supplémentaires :
     - Gérer les menus (créer, modifier, supprimer)
     - Gérer les plats (créer, modifier, supprimer)
     - Gérer les horaires
     - Gérer les commandes (accepter, modifier statut, annuler avec justification)
     - Valider ou refuser les avis clients
     - Filtrer les commandes par statut ou client

3. **ROLE_ADMIN** (Administrateur)
   - Rôle créé uniquement via les fixtures (non créable depuis l'interface)
   - Hérite de toutes les permissions de ROLE_EMPLOYEE et ROLE_USER
   - Permissions supplémentaires :
     - Créer des comptes employés
     - Désactiver/activer les comptes (employés et utilisateurs)
     - Changer les rôles (promouvoir utilisateur en employé ou rétrograder)
     - Visualiser les statistiques et le chiffre d'affaires
     - **Ne peut pas être désactivé ou modifié par l'application**

## Hiérarchie des rôles

```yaml
role_hierarchy:
    ROLE_EMPLOYEE: ROLE_USER
    ROLE_ADMIN: [ROLE_EMPLOYEE, ROLE_USER]
```

Cela signifie qu'un ADMIN a automatiquement les droits d'EMPLOYEE et d'USER.

## Contrôles d'accès

Les routes sont protégées dans `config/packages/security.yaml` :

```yaml
access_control:
    - { path: ^/admin/users, roles: ROLE_ADMIN }      # Gestion des utilisateurs réservée aux admins
    - { path: ^/admin, roles: ROLE_EMPLOYEE }         # Zone admin accessible aux employés et admins
    - { path: ^/account, roles: ROLE_USER }           # Espace utilisateur
    - { path: ^/order, roles: ROLE_USER }             # Commandes
```

## Création de comptes

### Utilisateur (ROLE_USER)
- **Auto-inscription** via le formulaire d'inscription public
- Reçoit automatiquement ROLE_USER
- Activation immédiate du compte

### Employé (ROLE_EMPLOYEE)
- **Création par l'administrateur** uniquement
- Accès : `/admin/users/create-employee`
- L'administrateur doit fournir :
  - Email (qui servira d'identifiant)
  - Prénom
  - Nom
  - Mot de passe (10 caractères min avec majuscule, minuscule, chiffre, caractère spécial)
- Le mot de passe n'est PAS envoyé par email (transmission sécurisée hors application)

### Administrateur (ROLE_ADMIN)
- **Création uniquement via fixtures**
- Impossible de créer un compte admin depuis l'interface
- Compte principal : `jose@vitegourmand.fr` / `Admin1234!@`

## Gestion des comptes

### Désactivation de compte
- L'administrateur peut désactiver un compte employé ou utilisateur
- Un compte désactivé ne peut plus se connecter
- Les comptes administrateurs **ne peuvent pas être désactivés**
- Cas d'usage : départ d'un employé

### Changement de rôle
- L'administrateur peut :
  - Promouvoir un utilisateur en employé
  - Rétrograder un employé en utilisateur
- Les comptes administrateurs **ne peuvent pas être modifiés**

## Sécurité

### Protection des comptes administrateurs
- Impossible de désactiver un compte ROLE_ADMIN
- Impossible de modifier le rôle d'un compte ROLE_ADMIN
- Protections implémentées dans le contrôleur

### Validation des mots de passe
Pour les comptes employés, le mot de passe doit respecter :
- Minimum 10 caractères
- Au moins une majuscule
- Au moins une minuscule
- Au moins un chiffre
- Au moins un caractère spécial

## Utilisation dans le code

### Vérifier les permissions dans un contrôleur

```php
// Vérifier si l'utilisateur a un rôle spécifique
if ($this->isGranted('ROLE_ADMIN')) {
    // Code réservé aux admins
}

// Ou via annotation
#[IsGranted('ROLE_ADMIN')]
public function adminOnlyAction() { }
```

### Vérifier les permissions dans Twig

```twig
{% if is_granted('ROLE_ADMIN') %}
    <a href="{{ path('admin_users_index') }}">Gérer les utilisateurs</a>
{% endif %}
```

## Comptes de test

### Administrateur
- Email: `jose@vitegourmand.fr`
- Mot de passe: `Admin1234!@`

### Employé
- Email: `julie@vitegourmand.fr`
- Mot de passe: `Employee123!@`

### Utilisateur
- Email: `user@test.fr`
- Mot de passe: `User1234!@`

## Routes principales

- `/admin/users` - Liste des utilisateurs (ROLE_ADMIN)
- `/admin/users/create-employee` - Créer un employé (ROLE_ADMIN)
- `/admin` - Dashboard admin (ROLE_EMPLOYEE)
- `/account` - Espace utilisateur (ROLE_USER)

## Conformité au cahier des charges

✅ Création automatique du rôle "utilisateur" à l'inscription
✅ Création de comptes employés par l'administrateur
✅ Email de notification (à implémenter)
✅ Impossible de créer un administrateur depuis l'interface
✅ Désactivation des comptes employés
✅ Compte administrateur créé uniquement en fixtures
✅ Hiérarchie des rôles respectée
✅ Contrôles d'accès par rôle en place
