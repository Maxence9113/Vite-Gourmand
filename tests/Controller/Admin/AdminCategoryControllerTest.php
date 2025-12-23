<?php

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use App\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels du contrôleur d'administration des catégories
 */
class AdminCategoryControllerTest extends WebTestCase
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

    public function testCategoriesListPageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/admin/categories');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
    }

    public function testNewCategoryPageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/admin/categories/new');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="category[name]"]');
    }

    public function testCreateCategory(): void
    {
        $crawler = $this->client->request('GET', '/admin/categories/new');
        $form = $crawler->selectButton('Créer la catégorie')->form();

        $form['category[name]'] = 'Nouvelle Catégorie Test';

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/categories');

        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'La catégorie "Nouvelle Catégorie Test" a été créée avec succès');

        $category = $this->entityManager->getRepository(Category::class)
            ->findOneBy(['name' => 'Nouvelle Catégorie Test']);

        $this->assertNotNull($category);
        $this->assertEquals('Nouvelle Catégorie Test', $category->getName());
    }

    public function testEditCategory(): void
    {
        $category = new Category();
        $category->setName('Catégorie à modifier');

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $categoryId = $category->getId();

        $crawler = $this->client->request('GET', '/admin/categories/' . $categoryId . '/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Modifier la catégorie')->form();
        $this->assertEquals('Catégorie à modifier', $form['category[name]']->getValue());

        $form['category[name]'] = 'Catégorie modifiée';
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/categories');
        $this->entityManager->clear();

        $updatedCategory = $this->entityManager->getRepository(Category::class)->find($categoryId);
        $this->assertNotNull($updatedCategory);
        $this->assertEquals('Catégorie modifiée', $updatedCategory->getName());
    }

    public function testDeleteCategory(): void
    {
        $category = new Category();
        $category->setName('Catégorie à supprimer');

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $categoryId = $category->getId();

        $this->client->request('POST', '/admin/categories/' . $categoryId . '/delete');
        $this->assertResponseRedirects('/admin/categories');

        $deletedCategory = $this->entityManager->getRepository(Category::class)->find($categoryId);
        $this->assertNull($deletedCategory);
    }
}