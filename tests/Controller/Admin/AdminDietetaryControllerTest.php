<?php

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use App\Entity\Dietetary;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels du contrôleur d'administration des régimes alimentaires
 */
class AdminDietetaryControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->loginAsAdmin();
    }

    private function loginAsAdmin(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $adminUser = $userRepository->findOneBy(['email' => 'admin@test.com']);

        if (!$adminUser) {
            $adminUser = new User();
            $adminUser->setEmail('admin@test.com');
            $adminUser->setFirstname('Admin');
            $adminUser->setLastname('Test');
            $adminUser->setRoles(['ROLE_ADMIN']);
            $adminUser->setPassword('password');

            $this->entityManager->persist($adminUser);
            $this->entityManager->flush();
        }

        $this->client->loginUser($adminUser);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testDietetariesListPageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/admin/dietetaries');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
    }

    public function testNewDietetaryPageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/admin/dietetaries/new');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="dietetary[name]"]');
    }

    public function testCreateDietetary(): void
    {
        $crawler = $this->client->request('GET', '/admin/dietetaries/new');
        $form = $crawler->selectButton('Créer le régime alimentaire')->form();

        $form['dietetary[name]'] = 'Nouveau Régime Test';

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/dietetaries');

        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Le régime alimentaire "Nouveau Régime Test" a été créé avec succès');

        $dietetary = $this->entityManager->getRepository(Dietetary::class)
            ->findOneBy(['name' => 'Nouveau Régime Test']);

        $this->assertNotNull($dietetary);
        $this->assertEquals('Nouveau Régime Test', $dietetary->getName());
    }

    public function testEditDietetary(): void
    {
        $dietetary = new Dietetary();
        $dietetary->setName('Régime à modifier');

        $this->entityManager->persist($dietetary);
        $this->entityManager->flush();

        $dietetaryId = $dietetary->getId();

        $crawler = $this->client->request('GET', '/admin/dietetaries/' . $dietetaryId . '/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Modifier le régime alimentaire')->form();
        $this->assertEquals('Régime à modifier', $form['dietetary[name]']->getValue());

        $form['dietetary[name]'] = 'Régime modifié';
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/dietetaries');
        $this->entityManager->clear();

        $updatedDietetary = $this->entityManager->getRepository(Dietetary::class)->find($dietetaryId);
        $this->assertNotNull($updatedDietetary);
        $this->assertEquals('Régime modifié', $updatedDietetary->getName());
    }

    public function testDeleteDietetary(): void
    {
        $dietetary = new Dietetary();
        $dietetary->setName('Régime à supprimer');

        $this->entityManager->persist($dietetary);
        $this->entityManager->flush();

        $dietetaryId = $dietetary->getId();

        $this->client->request('POST', '/admin/dietetaries/' . $dietetaryId . '/delete');
        $this->assertResponseRedirects('/admin/dietetaries');

        $deletedDietetary = $this->entityManager->getRepository(Dietetary::class)->find($dietetaryId);
        $this->assertNull($deletedDietetary);
    }
}
