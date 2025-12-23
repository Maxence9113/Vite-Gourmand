<?php

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use App\Entity\Allergen;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels du contrôleur d'administration des allergènes
 */
class AdminAllergenControllerTest extends WebTestCase
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

    public function testAllergensListPageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/admin/allergens');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
    }

    public function testNewAllergenPageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/admin/allergens/new');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="allergen[name]"]');
    }

    public function testCreateAllergen(): void
    {
        $crawler = $this->client->request('GET', '/admin/allergens/new');
        $form = $crawler->selectButton('Créer l\'allergène')->form();

        $form['allergen[name]'] = 'Nouvel Allergène Test';

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/allergens');

        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'L\'allergène "Nouvel Allergène Test" a été créé avec succès');

        $allergen = $this->entityManager->getRepository(Allergen::class)
            ->findOneBy(['name' => 'Nouvel Allergène Test']);

        $this->assertNotNull($allergen);
        $this->assertEquals('Nouvel Allergène Test', $allergen->getName());
    }

    public function testEditAllergen(): void
    {
        $allergen = new Allergen();
        $allergen->setName('Allergène à modifier');

        $this->entityManager->persist($allergen);
        $this->entityManager->flush();

        $allergenId = $allergen->getId();

        $crawler = $this->client->request('GET', '/admin/allergens/' . $allergenId . '/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Modifier l\'allergène')->form();
        $this->assertEquals('Allergène à modifier', $form['allergen[name]']->getValue());

        $form['allergen[name]'] = 'Allergène modifié';
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/allergens');
        $this->entityManager->clear();

        $updatedAllergen = $this->entityManager->getRepository(Allergen::class)->find($allergenId);
        $this->assertNotNull($updatedAllergen);
        $this->assertEquals('Allergène modifié', $updatedAllergen->getName());
    }

    public function testDeleteAllergen(): void
    {
        $allergen = new Allergen();
        $allergen->setName('Allergène à supprimer');

        $this->entityManager->persist($allergen);
        $this->entityManager->flush();

        $allergenId = $allergen->getId();

        $this->client->request('POST', '/admin/allergens/' . $allergenId . '/delete');
        $this->assertResponseRedirects('/admin/allergens');

        $deletedAllergen = $this->entityManager->getRepository(Allergen::class)->find($allergenId);
        $this->assertNull($deletedAllergen);
    }
}