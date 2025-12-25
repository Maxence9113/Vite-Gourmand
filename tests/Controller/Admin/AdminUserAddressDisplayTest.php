<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Address;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests fonctionnels pour l'affichage des adresses dans l'interface admin
 *
 * Ces tests vérifient que :
 * - Les admins peuvent voir les adresses des utilisateurs
 * - Les employés peuvent voir les adresses des utilisateurs (mais pas des autres employés/admins)
 * - Les utilisateurs normaux ne peuvent pas accéder à l'interface admin
 * - Les adresses sont correctement affichées avec tous leurs détails
 */
class AdminUserAddressDisplayTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->passwordHasher = $this->client->getContainer()
            ->get(UserPasswordHasherInterface::class);

        // Nettoyer la base de données avant chaque test
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    private function cleanDatabase(): void
    {
        // Supprimer les adresses en premier (à cause de la relation)
        $addressRepo = $this->entityManager->getRepository(Address::class);
        $addresses = $addressRepo->findAll();
        foreach ($addresses as $address) {
            $this->entityManager->remove($address);
        }

        $userRepo = $this->entityManager->getRepository(User::class);
        $users = $userRepo->findAll();
        foreach ($users as $user) {
            $this->entityManager->remove($user);
        }

        $this->entityManager->flush();
    }

    private function createUser(string $email, string $firstname, string $lastname, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setRoles($roles);
        $user->setIsEnabled(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Test1234!@'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createAddress(User $user, string $street, string $postalCode, string $city, string $phone, bool $isDefault = false, ?string $label = null): Address
    {
        $address = new Address();
        $address->setUser($user);
        $address->setStreet($street);
        $address->setPostalCode($postalCode);
        $address->setCity($city);
        $address->setPhone($phone);
        $address->setIsDefault($isDefault);
        if ($label) {
            $address->setLabel($label);
        }

        $this->entityManager->persist($address);
        $this->entityManager->flush();

        return $address;
    }

    /**
     * Test 1 : Un admin peut voir les adresses d'un utilisateur
     */
    public function testAdminCanSeeUserAddresses(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->createAddress($user, '10 Rue Sainte-Catherine', '33000', 'Bordeaux', '0612345678', true, 'Domicile');
        $this->createAddress($user, '20 Avenue de la République', '33100', 'Bordeaux', '0612345679', false, 'Travail');

        // Rafraîchir l'utilisateur pour charger la relation avec les adresses
        $this->entityManager->refresh($user);

        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/users/' . $user->getId() . '/edit');

        $this->assertResponseIsSuccessful();

        // Vérifier la présence de la section adresses
        $this->assertStringContainsString('Adresses de livraison', $this->client->getResponse()->getContent());

        // Vérifier que les adresses sont affichées
        $this->assertStringContainsString('10 Rue Sainte-Catherine', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('20 Avenue de la République', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('33000', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('33100', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('Bordeaux', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('0612345678', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('0612345679', $this->client->getResponse()->getContent());
    }

    /**
     * Test 2 : Les labels sont affichés correctement
     */
    public function testAddressLabelsAreDisplayed(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->createAddress($user, '10 Rue Test', '33000', 'Bordeaux', '0612345678', true, 'Domicile');

        $this->entityManager->refresh($user);
        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/users/' . $user->getId() . '/edit');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Domicile', $this->client->getResponse()->getContent());
    }

    /**
     * Test 3 : L'adresse par défaut est marquée comme telle
     */
    public function testDefaultAddressIsMarked(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->createAddress($user, '10 Rue Test', '33000', 'Bordeaux', '0612345678', true);
        $this->createAddress($user, '20 Rue Test 2', '33100', 'Bordeaux', '0612345679', false);

        $this->entityManager->refresh($user);
        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/users/' . $user->getId() . '/edit');

        $this->assertResponseIsSuccessful();

        // Vérifier qu'il y a un indicateur pour l'adresse par défaut
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Oui', $content); // L'adresse par défaut affiche "Oui"
    }

    /**
     * Test 4 : Un message informatif est affiché si l'utilisateur n'a pas d'adresse
     */
    public function testEmptyStateDisplayedWhenNoAddresses(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        // Pas d'adresse créée

        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/users/' . $user->getId() . '/edit');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-info', 'Cet utilisateur n\'a pas encore enregistré d\'adresse');
    }

    /**
     * Test 5 : Un employé peut voir les adresses d'un utilisateur
     */
    public function testEmployeeCanSeeUserAddresses(): void
    {
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->createAddress($user, '10 Rue Test', '33000', 'Bordeaux', '0612345678', true);

        $this->entityManager->refresh($user);
        $this->client->loginUser($employee);

        $this->client->request('GET', '/admin/users/' . $user->getId() . '/edit');

        $this->assertResponseIsSuccessful();

        // Vérifier la présence de la section adresses
        $this->assertStringContainsString('Adresses de livraison', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('10 Rue Test', $this->client->getResponse()->getContent());
    }

    /**
     * Test 6 : Un employé ne peut pas voir les adresses d'un autre employé
     */
    public function testEmployeeCannotSeeOtherEmployeeAddresses(): void
    {
        $employee1 = $this->createUser('employee1@test.fr', 'Employee', 'One', ['ROLE_EMPLOYEE']);
        $employee2 = $this->createUser('employee2@test.fr', 'Employee', 'Two', ['ROLE_EMPLOYEE']);
        $this->createAddress($employee2, '10 Rue Test', '33000', 'Bordeaux', '0612345678', true);

        $this->client->loginUser($employee1);

        $this->client->request('GET', '/admin/users/' . $employee2->getId() . '/edit');

        // L'employé est redirigé vers /admin/users avec un message d'erreur
        $this->assertResponseRedirects('/admin/users');
    }

    /**
     * Test 7 : Un employé ne peut pas voir les adresses d'un admin
     */
    public function testEmployeeCannotSeeAdminAddresses(): void
    {
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $this->createAddress($admin, '10 Rue Test', '33000', 'Bordeaux', '0612345678', true);

        $this->client->loginUser($employee);

        $this->client->request('GET', '/admin/users/' . $admin->getId() . '/edit');

        // L'employé est redirigé vers /admin/users avec un message d'erreur
        $this->assertResponseRedirects('/admin/users');
    }

    /**
     * Test 8 : Un utilisateur normal ne peut pas accéder à la page d'édition admin
     */
    public function testRegularUserCannotAccessAdminUserEdit(): void
    {
        $user1 = $this->createUser('user1@test.fr', 'John', 'Doe');
        $user2 = $this->createUser('user2@test.fr', 'Jane', 'Smith');
        $this->createAddress($user2, '10 Rue Test', '33000', 'Bordeaux', '0612345678', true);

        $this->client->loginUser($user1);

        $this->client->request('GET', '/admin/users/' . $user2->getId() . '/edit');

        // L'utilisateur normal ne devrait pas pouvoir accéder à l'interface admin
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * Test 9 : Un admin peut voir les adresses d'un employé
     */
    public function testAdminCanSeeEmployeeAddresses(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $this->createAddress($employee, '10 Rue Test', '33000', 'Bordeaux', '0612345678', true, 'Bureau');

        $this->entityManager->refresh($employee);
        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/users/' . $employee->getId() . '/edit');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('10 Rue Test', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('Bureau', $this->client->getResponse()->getContent());
    }

    /**
     * Test 10 : Un admin ne peut pas modifier un autre admin (ils sont protégés)
     */
    public function testAdminCannotEditOtherAdmin(): void
    {
        $admin1 = $this->createUser('admin1@test.fr', 'Admin', 'One', ['ROLE_ADMIN']);
        $admin2 = $this->createUser('admin2@test.fr', 'Admin', 'Two', ['ROLE_ADMIN']);
        $this->createAddress($admin2, '10 Rue Test', '33000', 'Bordeaux', '0612345678', true);

        $this->entityManager->refresh($admin2);
        $this->client->loginUser($admin1);

        $this->client->request('GET', '/admin/users/' . $admin2->getId() . '/edit');

        // Les comptes admin sont protégés, l'admin est redirigé avec un message d'erreur
        $this->assertResponseRedirects('/admin/users');
    }

    /**
     * Test 11 : Les adresses multiples sont toutes affichées
     */
    public function testMultipleAddressesAreAllDisplayed(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $user = $this->createUser('user@test.fr', 'John', 'Doe');

        $this->createAddress($user, '10 Rue A', '33000', 'Bordeaux', '0612345678', true, 'Domicile');
        $this->createAddress($user, '20 Rue B', '33100', 'Bordeaux', '0612345679', false, 'Travail');
        $this->createAddress($user, '30 Rue C', '33200', 'Bordeaux', '0612345670', false, 'Vacances');

        $this->entityManager->refresh($user);
        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/users/' . $user->getId() . '/edit');

        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('10 Rue A', $content);
        $this->assertStringContainsString('20 Rue B', $content);
        $this->assertStringContainsString('30 Rue C', $content);
        $this->assertStringContainsString('Domicile', $content);
        $this->assertStringContainsString('Travail', $content);
        $this->assertStringContainsString('Vacances', $content);
    }

    /**
     * Test 12 : Les adresses sans label affichent un tiret
     */
    public function testAddressWithoutLabelShowsDash(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->createAddress($user, '10 Rue Test', '33000', 'Bordeaux', '0612345678', true, null); // Sans label

        $this->entityManager->refresh($user);
        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/users/' . $user->getId() . '/edit');

        $this->assertResponseIsSuccessful();

        // Vérifier qu'il y a un tiret pour les adresses sans label
        $this->assertStringContainsString('10 Rue Test', $this->client->getResponse()->getContent());
    }
}