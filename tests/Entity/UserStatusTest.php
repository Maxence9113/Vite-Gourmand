<?php

namespace App\Tests\Entity;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests pour l'activation/désactivation des comptes utilisateurs
 *
 * Ces tests vérifient que :
 * - Le champ isEnabled fonctionne correctement
 * - Les comptes peuvent être activés/désactivés
 * - La valeur par défaut est 'actif' (true)
 */
class UserStatusTest extends KernelTestCase
{
    private $entityManager;
    private $passwordHasher;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()
            ->get('doctrine')
            ->getManager();

        $this->passwordHasher = self::getContainer()
            ->get(UserPasswordHasherInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    /**
     * Test 1 : Un nouvel utilisateur est actif par défaut
     */
    public function testNewUserIsEnabledByDefault(): void
    {
        $user = new User();
        $user->setEmail('test@test.fr');
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('password');

        // Ne pas appeler setIsEnabled(), vérifier la valeur par défaut
        $this->assertTrue($user->isEnabled(), 'New user should be enabled by default');
    }

    /**
     * Test 2 : Un utilisateur peut être désactivé
     */
    public function testUserCanBeDisabled(): void
    {
        $user = new User();
        $user->setEmail('test@test.fr');
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('password');
        $user->setIsEnabled(false);

        $this->assertFalse($user->isEnabled());
    }

    /**
     * Test 3 : Un utilisateur peut être réactivé
     */
    public function testUserCanBeReenabled(): void
    {
        $user = new User();
        $user->setEmail('test@test.fr');
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('password');
        $user->setIsEnabled(false);

        $this->assertFalse($user->isEnabled());

        $user->setIsEnabled(true);

        $this->assertTrue($user->isEnabled());
    }

    /**
     * Test 4 : Le statut est persisté en base de données
     */
    public function testUserStatusIsPersistedInDatabase(): void
    {
        $user = new User();
        $user->setEmail('persistent@test.fr');
        $user->setFirstname('Persistent');
        $user->setLastname('Test');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Test1234!@'));
        $user->setIsEnabled(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $userId = $user->getId();

        // Vider le cache Doctrine
        $this->entityManager->clear();

        // Récupérer l'utilisateur depuis la base
        $userRepo = $this->entityManager->getRepository(User::class);
        $retrievedUser = $userRepo->find($userId);

        $this->assertNotNull($retrievedUser);
        $this->assertFalse($retrievedUser->isEnabled(), 'User status should be persisted in database');

        // Nettoyage
        $this->entityManager->remove($retrievedUser);
        $this->entityManager->flush();
    }

    /**
     * Test 5 : Modification du statut est persistée
     */
    public function testStatusChangeIsPersisted(): void
    {
        $user = new User();
        $user->setEmail('changeable@test.fr');
        $user->setFirstname('Changeable');
        $user->setLastname('Test');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Test1234!@'));
        $user->setIsEnabled(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $userId = $user->getId();

        // Désactiver l'utilisateur
        $user->setIsEnabled(false);
        $this->entityManager->flush();

        // Vider le cache
        $this->entityManager->clear();

        // Récupérer à nouveau
        $userRepo = $this->entityManager->getRepository(User::class);
        $retrievedUser = $userRepo->find($userId);

        $this->assertFalse($retrievedUser->isEnabled(), 'Status change should be persisted');

        // Nettoyage
        $this->entityManager->remove($retrievedUser);
        $this->entityManager->flush();
    }

    /**
     * Test 6 : Plusieurs utilisateurs avec différents statuts
     */
    public function testMultipleUsersWithDifferentStatuses(): void
    {
        $activeUser = new User();
        $activeUser->setEmail('active@test.fr');
        $activeUser->setFirstname('Active');
        $activeUser->setLastname('User');
        $activeUser->setRoles(['ROLE_USER']);
        $activeUser->setPassword($this->passwordHasher->hashPassword($activeUser, 'Test1234!@'));
        $activeUser->setIsEnabled(true);

        $inactiveUser = new User();
        $inactiveUser->setEmail('inactive@test.fr');
        $inactiveUser->setFirstname('Inactive');
        $inactiveUser->setLastname('User');
        $inactiveUser->setRoles(['ROLE_USER']);
        $inactiveUser->setPassword($this->passwordHasher->hashPassword($inactiveUser, 'Test1234!@'));
        $inactiveUser->setIsEnabled(false);

        $this->entityManager->persist($activeUser);
        $this->entityManager->persist($inactiveUser);
        $this->entityManager->flush();

        $activeId = $activeUser->getId();
        $inactiveId = $inactiveUser->getId();

        // Vider le cache
        $this->entityManager->clear();

        // Récupérer les utilisateurs
        $userRepo = $this->entityManager->getRepository(User::class);
        $retrievedActive = $userRepo->find($activeId);
        $retrievedInactive = $userRepo->find($inactiveId);

        $this->assertTrue($retrievedActive->isEnabled());
        $this->assertFalse($retrievedInactive->isEnabled());

        // Nettoyage
        $this->entityManager->remove($retrievedActive);
        $this->entityManager->remove($retrievedInactive);
        $this->entityManager->flush();
    }

    /**
     * Test 7 : Les employés peuvent être désactivés
     */
    public function testEmployeeCanBeDisabled(): void
    {
        $employee = new User();
        $employee->setEmail('employee@test.fr');
        $employee->setFirstname('Employee');
        $employee->setLastname('Test');
        $employee->setRoles(['ROLE_EMPLOYEE']);
        $employee->setPassword($this->passwordHasher->hashPassword($employee, 'Test1234!@'));
        $employee->setIsEnabled(false);

        $this->entityManager->persist($employee);
        $this->entityManager->flush();

        $this->assertFalse($employee->isEnabled());
        $this->assertContains('ROLE_EMPLOYEE', $employee->getRoles());

        // Nettoyage
        $this->entityManager->remove($employee);
        $this->entityManager->flush();
    }

    /**
     * Test 8 : Le statut d'un utilisateur est un booléen
     */
    public function testIsEnabledReturnsBoolean(): void
    {
        $user = new User();
        $user->setEmail('test@test.fr');
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('password');

        $this->assertIsBool($user->isEnabled());
    }
}