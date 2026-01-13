<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un utilisateur administrateur',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Email de l\'utilisateur', 'jose@vitegourmand.fr')
            ->addOption('firstname', null, InputOption::VALUE_OPTIONAL, 'Prénom', 'José')
            ->addOption('lastname', null, InputOption::VALUE_OPTIONAL, 'Nom', 'Martinez')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Mot de passe', 'Admin1234!@')
            ->addOption('role', null, InputOption::VALUE_OPTIONAL, 'Rôle de l\'utilisateur (ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_USER)', 'ROLE_ADMIN')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getOption('email');
        $firstname = $input->getOption('firstname');
        $lastname = $input->getOption('lastname');
        $password = $input->getOption('password');
        $role = $input->getOption('role');

        // Vérifier si un utilisateur avec cet email existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existingUser) {
            $io->error('Un utilisateur avec cet email existe déjà !');
            return Command::FAILURE;
        }

        // Créer l'utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setRoles([$role]);
        $user->setIsEnabled(true);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Utilisateur créé avec succès !');
        $io->table(
            ['Propriété', 'Valeur'],
            [
                ['Email', $email],
                ['Prénom', $firstname],
                ['Nom', $lastname],
                ['Rôle', $role],
            ]
        );

        return Command::SUCCESS;
    }
}