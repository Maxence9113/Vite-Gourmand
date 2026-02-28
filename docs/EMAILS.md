# Systeme d'emails

## Vue d'ensemble

L'application envoie des emails a differents moments du parcours utilisateur. Tous les envois passent par le service `EmailService` qui utilise **Symfony Mailer**.

## Configuration

### Transport SMTP

Configure dans `.env` :
```
MAILER_DSN=smtp://user:password@smtp.example.com:587
COMPANY_EMAIL=contact@vitegourmand.fr
COMPANY_NAME=Vite & Gourmand
```

### Service EmailService

**Fichier** : `src/Service/EmailService.php`

```php
public function __construct(
    private MailerInterface $mailer,
    private string $companyEmail,  // Injecte depuis .env
    private string $companyName    // Injecte depuis .env
)
```

Configuration dans `config/services.yaml` :
```yaml
App\Service\EmailService:
    arguments:
        $companyEmail: '%env(COMPANY_EMAIL)%'
        $companyName: '%env(COMPANY_NAME)%'
```

---

## Liste des emails envoyes

### 1. Email de bienvenue

**Quand** : A l'inscription d'un nouvel utilisateur
**Template** : `templates/emails/welcome.html.twig`
**Destinataire** : Le nouvel utilisateur
**Methode** : `sendWelcomeEmail(User $user)`

Contenu : Message de bienvenue avec le prenom de l'utilisateur.

---

### 2. Email de contact (notification)

**Quand** : Un visiteur soumet le formulaire de contact
**Template** : `templates/emails/contact.html.twig`
**Destinataire** : L'entreprise (`COMPANY_EMAIL`)
**Methode** : `sendContactEmail(string $senderName, string $senderEmail, string $subject, string $message)`

Contenu : Nom, email, sujet et message du visiteur.

---

### 3. Email de contact (confirmation)

**Quand** : Un visiteur soumet le formulaire de contact
**Template** : `templates/emails/contact_confirmation.html.twig`
**Destinataire** : Le visiteur
**Methode** : `sendContactConfirmation(string $senderEmail, string $senderName)`

Contenu : Confirmation que le message a bien ete recu.

---

### 4. Confirmation de commande

**Quand** : Une commande est creee par le client
**Template** : `templates/emails/order_confirmation.html.twig`
**Destinataire** : Le client
**Methode** : `sendOrderConfirmation(Order $order)`

Contenu :
- Numero de commande
- Resume : menu, nombre de personnes, prix
- Adresse et date de livraison
- Informations de contact du restaurant

---

### 5. Commande validee

**Quand** : Un employe valide (accepte) une commande
**Template** : `templates/emails/order_validated.html.twig`
**Destinataire** : Le client
**Methode** : `sendOrderValidated(Order $order)`

Contenu : Notification que la commande a ete acceptee.

---

### 6. Commande terminee

**Quand** : Une commande passe au statut COMPLETED
**Template** : `templates/emails/order_completed.html.twig`
**Destinataire** : Le client
**Methode** : `sendOrderCompleted(Order $order)`

Contenu : Notification de fin + invitation a laisser un avis.

---

### 7. Reset de mot de passe

**Quand** : L'utilisateur demande une reinitialisation de mot de passe
**Template** : `templates/emails/password_reset.html.twig`
**Destinataire** : L'utilisateur
**Methode** : `sendPasswordResetEmail(User $user, string $resetUrl)`

Contenu : Lien de reinitialisation (valable pour une duree limitee).

---

### 8. Compte employe cree

**Quand** : Un administrateur cree un compte employe
**Template** : `templates/emails/employee_account_created.html.twig`
**Destinataire** : Le nouvel employe
**Methode** : `sendEmployeeAccountCreated(User $employee)`

Contenu : Notification de creation du compte avec instructions de connexion.

---

### 9. Rappel retour materiel

**Quand** : Rappel automatique avant la date limite de retour
**Template** : `templates/emails/material_return_reminder.html.twig`
**Destinataire** : Le client
**Methode** : `sendMaterialReturnReminder(Order $order)`

Contenu : Rappel de la date limite de retour du materiel emprunte.

---

## Templates d'emails

### Heritage

Tous les emails utilisent un layout commun :

```
emails/base_email.html.twig     # Layout de base (structure HTML email)
├── emails/_email_styles.html.twig  # Styles CSS inline
├── emails/welcome.html.twig
├── emails/contact.html.twig
├── emails/order_confirmation.html.twig
└── ...
```

### Pourquoi des styles inline ?

Les clients email (Gmail, Outlook, etc.) ne supportent pas les feuilles de style CSS externes. Les styles doivent etre **inline** (directement dans les attributs `style=""` des elements HTML). Le fichier `_email_styles.html.twig` definit les styles communs.

---

## Gestion des erreurs

L'envoi d'emails **ne bloque pas** les operations principales :

```php
// Exemple dans RegisterController.php
try {
    $this->emailService->sendWelcomeEmail($user);
} catch (\Exception $e) {
    // On log l'erreur mais on continue
    // L'inscription est reussie meme si l'email echoue
}
```

C'est un choix de design : mieux vaut creer le compte et ne pas envoyer l'email, plutot que de faire echouer l'inscription a cause d'un probleme SMTP.

---

## Tableau recapitulatif

| Email | Declencheur | De | A | Template |
|---|---|---|---|---|
| Bienvenue | Inscription | Entreprise | Utilisateur | `welcome.html.twig` |
| Contact (notif) | Formulaire contact | Visiteur | Entreprise | `contact.html.twig` |
| Contact (confirm) | Formulaire contact | Entreprise | Visiteur | `contact_confirmation.html.twig` |
| Commande creee | Nouvelle commande | Entreprise | Client | `order_confirmation.html.twig` |
| Commande validee | Statut → VALIDATED | Entreprise | Client | `order_validated.html.twig` |
| Commande terminee | Statut → COMPLETED | Entreprise | Client | `order_completed.html.twig` |
| Reset password | Demande de reset | Entreprise | Utilisateur | `password_reset.html.twig` |
| Compte employe | Creation par admin | Entreprise | Employe | `employee_account_created.html.twig` |
| Rappel materiel | Avant date limite | Entreprise | Client | `material_return_reminder.html.twig` |

---

## Voir aussi

- [SERVICES.md](SERVICES.md) - EmailService
- [ORDERS.md](ORDERS.md) - Emails lies aux commandes
- [SECURITY.md](SECURITY.md) - Email de reset de mot de passe
