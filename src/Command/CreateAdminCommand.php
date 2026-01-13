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
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Email de l\'admin', 'jose@vitegourmand.fr')
            ->addOption('firstname', null, InputOption::VALUE_OPTIONAL, 'Prénom', 'José')
            ->addOption('lastname', null, InputOption::VALUE_OPTIONAL, 'Nom', 'Martinez')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Mot de passe', 'Admin1234!@')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getOption('email');
        $firstname = $input->getOption('firstname');
        $lastname = $input->getOption('lastname');
        $password = $input->getOption('password');

        // Vérifier si un utilisateur avec cet email existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existingUser) {
            $io->error('Un utilisateur avec cet email existe déjà !');
            return Command::FAILURE;
        }

        // Créer l'utilisateur admin
        $admin = new User();
        $admin->setEmail($email);
        $admin->setFirstname($firstname);
        $admin->setLastname($lastname);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsEnabled(true);

        $hashedPassword = $this->passwordHasher->hashPassword($admin, $password);
        $admin->setPassword($hashedPassword);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success('Utilisateur administrateur créé avec succès !');
        $io->table(
            ['Propriété', 'Valeur'],
            [
                ['Email', $email],
                ['Prénom', $firstname],
                ['Nom', $lastname],
                ['Rôle', 'ROLE_ADMIN'],
            ]
        );

        return Command::SUCCESS;
    }
}