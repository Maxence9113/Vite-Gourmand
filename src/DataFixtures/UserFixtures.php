<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Utilisateur de test classique
        $user = new User();
        $user->setEmail('test@test.fr');
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setRoles(['ROLE_USER']);
        // Mot de passe: Test1234!@
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'Test1234!@');
        $user->setPassword($hashedPassword);

        $manager->persist($user);

        // Utilisateur admin de test
        $admin = new User();
        $admin->setEmail('admin@test.fr');
        $admin->setFirstname('Admin');
        $admin->setLastname('Test');
        $admin->setRoles(['ROLE_ADMIN']);
        // Mot de passe: Admin1234!@
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'Admin1234!@');
        $admin->setPassword($hashedPassword);

        $manager->persist($admin);

        $manager->flush();
    }
}
