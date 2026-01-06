<?php

namespace App\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'test:mongodb',
    description: 'Teste la connexion à MongoDB',
)]
class TestMongodbCommand extends Command
{
    public function __construct(
        private DocumentManager $documentManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->section('Test de connexion MongoDB');

            // Test de connexion
            $client = $this->documentManager->getClient();
            $databases = $client->listDatabases();

            $io->success('✅ Connexion MongoDB réussie !');

            // Affichage des informations
            $io->table(
                ['Paramètre', 'Valeur'],
                [
                    ['Base de données', $this->documentManager->getConfiguration()->getDefaultDB()],
                    ['Connexion', 'OK'],
                ]
            );

            // Test d'écriture/lecture
            $database = $client->selectDatabase('vite_gourmand');
            $testCollection = $database->selectCollection('_connection_test');
            $testCollection->insertOne(['test' => true, 'timestamp' => new \DateTime()]);
            $count = $testCollection->countDocuments();
            $testCollection->drop();

            $io->info("Test d'écriture/lecture : OK ($count document(s) testé(s))");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur de connexion MongoDB : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
