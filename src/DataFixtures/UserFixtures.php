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

        // Compte administrateur (José)
        $admin = new User();
        $admin->setEmail('jose@vitegourmand.fr');
        $admin->setFirstname('José');
        $admin->setLastname('Martinez');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsEnabled(true);
        // Mot de passe: Admin1234!@
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'Admin1234!@');
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

        // Compte employé (Julie)
        $employee = new User();
        $employee->setEmail('julie@vitegourmand.fr');
        $employee->setFirstname('Julie');
        $employee->setLastname('Dupont');
        $employee->setRoles(['ROLE_EMPLOYEE']);
        $employee->setIsEnabled(true);
        // Mot de passe: Employee123!@
        $hashedPassword = $this->passwordHasher->hashPassword($employee, 'Employee123!@');
        $employee->setPassword($hashedPassword);
        $manager->persist($employee);

        // Compte utilisateur de test
        $user = new User();
        $user->setEmail('user@test.fr');
        $user->setFirstname('Jean');
        $user->setLastname('Dupont');
        $user->setRoles(['ROLE_USER']);
        $user->setIsEnabled(true);
        // Mot de passe: User1234!@
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'User1234!@');
        $user->setPassword($hashedPassword);
        $manager->persist($user);

        // Générer 5 employés aléatoires
        for ($i = 1; $i <= 5; $i++) {
            $fakeEmployee = new User();
            $fakeEmployee->setEmail($faker->unique()->email());
            $fakeEmployee->setFirstname($faker->firstName());
            $fakeEmployee->setLastname($faker->lastName());
            $fakeEmployee->setRoles(['ROLE_EMPLOYEE']);
            $fakeEmployee->setIsEnabled($faker->boolean(90)); // 90% actifs
            $fakeEmployee->setPassword($this->passwordHasher->hashPassword($fakeEmployee, 'Employee123!@'));
            $manager->persist($fakeEmployee);
        }

        // Générer 10 utilisateurs aléatoires
        for ($i = 1; $i <= 10; $i++) {
            $fakeUser = new User();
            $fakeUser->setEmail($faker->unique()->email());
            $fakeUser->setFirstname($faker->firstName());
            $fakeUser->setLastname($faker->lastName());
            $fakeUser->setRoles(['ROLE_USER']);
            $fakeUser->setIsEnabled(true);
            $fakeUser->setPassword($this->passwordHasher->hashPassword($fakeUser, 'User1234!@'));
            $manager->persist($fakeUser);
        }

        $manager->flush();
    }
}
