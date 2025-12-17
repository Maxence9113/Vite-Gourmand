<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

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

        for ($i = 1; $i <= 10; $i++) {
            $fakeUser = new User();
            $fakeUser->setEmail($faker->unique()->email());
            $fakeUser->setFirstname($faker->firstName());
            $fakeUser->setLastname($faker->lastName());

            $role = $faker->randomElement(['ROLE_USER', 'ROLE_EMPLOYEE']);
            $fakeUser->setRoles([$role]);
            // Tous les faux users ont le même mot de passe : "password"
            $fakeUser->setPassword($this->passwordHasher->hashPassword($fakeUser, 'password'));
            
            $manager->persist($fakeUser);
        }

        $manager->flush();
    }
}
